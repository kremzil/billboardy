<?php

declare(strict_types=1);

// Run with wp --skip-plugins --skip-themes eval-file <this-file>.
// Uses disposable, uniquely prefixed tables in the connected DB. No real mail
// or Cloudflare traffic: those two external boundaries are explicitly stubbed.
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('Run this integration test through WP-CLI.');
}

require_once dirname(__DIR__) . '/billboardy-map-api.php';

global $wpdb, $checks;

use Billboardy\MapApi\Database\Schema;
use Billboardy\MapApi\Repository\InquiryLogRepository;
use Billboardy\MapApi\Rest\AdSpaceApiController;
use Billboardy\MapApi\Service\AdSpaceService;
use Billboardy\MapApi\Service\InquirySubmissionGuard;
use Billboardy\MapApi\Service\TurnstileVerifier;

function recordFor(string $email, string $message = 'Prosím o ponuku.'): array
{
    return ['source' => 'contact', 'email' => $email, 'name' => 'Test', 'note' => $message, 'details_json' => '{}', 'items_json' => '[]'];
}

if (($args[0] ?? '') === 'worker') {
    if (!preg_match('/^[a-zA-Z0-9_]+bbtest_[a-f0-9]{8}_$/', $args[1] ?? '')) {
        throw new RuntimeException('Invalid isolated test prefix.');
    }
    $wpdb->prefix = $args[1];
    $result = (new InquirySubmissionGuard(new InquiryLogRepository()))->reserve(recordFor($args[2]), '93.184.216.34');
    echo wp_json_encode(is_wp_error($result) ? ['error' => $result->get_error_code()] : $result);
    return;
}

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}

function responseStatus($response): int
{
    return is_wp_error($response) ? (int) $response->get_error_data()['status'] : $response->get_status();
}

function submit(AdSpaceApiController $controller, array $changes = [])
{
    $payload = array_merge([
        'source' => 'contact', 'name' => 'Test', 'email' => 'test@example.invalid',
        'message' => 'Prosím o ponuku.', 'turnstileToken' => 'contact', 'turnstileAction' => 'inquiry_contact',
    ], $changes);
    $request = new WP_REST_Request('POST', '/billboardy/v1/inquiries');
    $request->set_header('Content-Type', 'application/json');
    $request->set_body(wp_json_encode($payload));
    return $controller->inquiry($request);
}

function runWorkers(string $prefix, array $emails): array
{
    $processes = [];
    foreach ($emails as $email) {
        $pipes = [];
        $process = proc_open([
            PHP_BINARY, $_SERVER['argv'][0], '--path=' . ABSPATH, '--skip-plugins', '--skip-themes',
            'eval-file', __FILE__, 'worker', $prefix, $email,
        ], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start concurrency worker.');
        }
        fclose($pipes[0]);
        $processes[] = [$process, $pipes];
    }
    $results = [];
    foreach ($processes as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        check(proc_close($process) === 0, 'Concurrency worker failed: ' . $error);
        $result = json_decode($output, true);
        check(is_array($result), 'Invalid concurrency worker output: ' . $output);
        $results[] = $result;
    }
    return $results;
}

$originalPrefix = $wpdb->prefix;
$originalIp = $_SERVER['REMOTE_ADDR'] ?? null;
$testPrefix = $originalPrefix . 'bbtest_' . bin2hex(random_bytes(4)) . '_';
echo 'Isolated test prefix: ' . $testPrefix . PHP_EOL;
$realCount = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . Schema::inquiryLogTableName());
$originalVersion = get_option(Schema::VERSION_OPTION);
$keepVersion = static function ($new, $old) { return $old; };
add_filter('pre_update_option_' . Schema::VERSION_OPTION, $keepVersion, 10, 2);
$mailCalls = 0;
$mailResult = true;
$mailStub = static function () use (&$mailCalls, &$mailResult) { $mailCalls++; return $mailResult; };
add_filter('pre_wp_mail', $mailStub);
$captchaStub = static function ($pre, $requestArgs, $url) {
    if ($url !== 'https://challenges.cloudflare.com/turnstile/v0/siteverify') {
        throw new RuntimeException('Unexpected external HTTP request.');
    }
    $source = $requestArgs['body']['response'] ?? '';
    return ['response' => ['code' => 200], 'body' => wp_json_encode(['success' => in_array($source, ['contact', 'map', 'quick'], true), 'action' => 'inquiry_' . $source])];
};
add_filter('pre_http_request', $captchaStub, 10, 3);

try {
    $wpdb->prefix = $testPrefix;
    Schema::install();
    $table = Schema::inquiryLogTableName();
    check($wpdb->last_error === '', 'Isolated schema migration failed.');
    check(get_option(Schema::VERSION_OPTION) === $originalVersion, 'Live schema version changed during test.');
    $logs = new InquiryLogRepository();
    $guard = new InquirySubmissionGuard($logs);
    $service = (new ReflectionClass(AdSpaceService::class))->newInstanceWithoutConstructor();
    $controller = new AdSpaceApiController($service, new TurnstileVerifier(), $logs);
    $_SERVER['REMOTE_ADDR'] = '93.184.216.34';

    check(responseStatus(submit($controller, ['turnstileToken' => ''])) === 403, 'Missing CAPTCHA must fail before reservation.');
    check(responseStatus(submit($controller, ['email' => ''])) === 400, 'Invalid input must fail before reservation.');
    check((int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}") === 0 && $mailCalls === 0, 'Rejected input created effects.');
    check(responseStatus(submit($controller)) === 200 && $mailCalls === 1, 'First valid request must send once.');
    $duplicate = submit($controller, ['email' => 'TEST@example.invalid', 'message' => '  Prosím   o ponuku.  ']);
    check(responseStatus($duplicate) === 200 && $duplicate->get_data()['data']['duplicate'] === true, 'Normalized sent duplicate must be acknowledged.');
    check($mailCalls === 1 && (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}") === 1, 'Duplicate sent or logged twice.');
    check(responseStatus(submit($controller, ['message' => 'Iná ponuka.'])) === 429, 'Email cooldown must block changed content.');

    for ($i = 2; $i <= 5; $i++) {
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET created_at = %s", wp_date('Y-m-d H:i:s', time() - 61, wp_timezone())));
        check(responseStatus(submit($controller, ['message' => 'Ponuka ' . $i])) === 200, 'Distinct request after cooldown should pass.');
    }
    $wpdb->query($wpdb->prepare("UPDATE {$table} SET created_at = %s", wp_date('Y-m-d H:i:s', time() - 61, wp_timezone())));
    check(responseStatus(submit($controller, ['message' => 'Ponuka 6'])) === 429, 'Sixth hourly request must be blocked.');
    $wpdb->query($wpdb->prepare("UPDATE {$table} SET created_at = %s", wp_date('Y-m-d H:i:s', time() - 3601, wp_timezone())));
    check(responseStatus(submit($controller)) === 200, 'Expired limits and fingerprint should allow a new request.');

    $wpdb->query("TRUNCATE TABLE {$table}");
    $mailResult = false;
    check(responseStatus(submit($controller)) === 500, 'Mail failure must be reported.');
    $mailResult = true;
    check(responseStatus(submit($controller)) === 200, 'Confirmed mail failure must permit immediate retry.');
    check($wpdb->get_var("SELECT status FROM {$table} ORDER BY id DESC LIMIT 1") === 'sent', 'Retry must be recorded sent.');

    $wpdb->query("TRUNCATE TABLE {$table}");
    $pending = $guard->reserve(recordFor('pending@example.invalid'), '93.184.216.34');
    check(!is_wp_error($pending), 'Pending reservation failed.');
    check(is_wp_error($guard->reserve(recordFor('pending@example.invalid'), '8.8.8.8')), 'Duplicate pending reservation must not be successful.');
    $logs->markSent($pending['id']);
    check($guard->reserve(recordFor('pending@example.invalid'), '8.8.8.8')['duplicate'], 'Changing IP must not bypass duplicate suppression.');

    $wpdb->query("TRUNCATE TABLE {$table}");
    foreach (['quick', 'map'] as $source) {
        $payload = ['source' => $source, 'email' => $source . '@example.invalid', 'phone' => '+421900000000', 'turnstileToken' => $source, 'turnstileAction' => 'inquiry_' . $source, 'items' => [['id' => 'a'], ['id' => 'b']]];
        check(responseStatus(submit($controller, $payload)) === 200, 'Source ' . $source . ' must remain usable.');
        $before = $mailCalls;
        $payload['items'] = array_reverse($payload['items']);
        check(responseStatus(submit($controller, $payload)) === 200 && $mailCalls === $before, 'Reordered map selection must be duplicate.');
    }

    $wpdb->query("TRUNCATE TABLE {$table}");
    $results = runWorkers($testPrefix, array_fill(0, 2, 'race@example.invalid'));
    check(count(array_filter($results, static function ($r) { return isset($r['id']) && $r['id'] > 0; })) === 1, 'Parallel identical requests reserved more than once.');
    check((int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}") === 1, 'Parallel duplicates created extra log records.');

    $wpdb->query("TRUNCATE TABLE {$table}");
    for ($i = 0; $i < 19; $i++) {
        check(!is_wp_error($guard->reserve(recordFor('ip' . $i . '@example.invalid'), '93.184.216.34')), 'IP setup reservation failed.');
    }
    $results = runWorkers($testPrefix, ['last1@example.invalid', 'last2@example.invalid']);
    check(count(array_filter($results, static function ($r) { return isset($r['id']) && $r['id'] > 0; })) === 1, 'Parallel IP limit boundary allowed more than one remaining slot.');
    check((int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}") === 20, 'IP quota must be exactly 20 reservations.');

    $before = $mailCalls;
    $wpdb->query("DROP TABLE {$table}");
    $previousSuppress = $wpdb->suppress_errors(true);
    check(responseStatus(submit($controller, ['email' => 'storage@example.invalid'])) === 503, 'Storage failure must fail closed.');
    $wpdb->suppress_errors($previousSuppress);
    check($mailCalls === $before, 'Storage failure must never send mail.');
} finally {
    // These names were created by this invocation, outside all live table names.
    $wpdb->prefix = $testPrefix;
    $wpdb->query('DROP TABLE IF EXISTS ' . Schema::inquiryLogTableName());
    $wpdb->query('DROP TABLE IF EXISTS ' . Schema::tableName());
    $wpdb->prefix = $originalPrefix;
    remove_filter('pre_update_option_' . Schema::VERSION_OPTION, $keepVersion);
    remove_filter('pre_wp_mail', $mailStub);
    remove_filter('pre_http_request', $captchaStub, 10);
    if ($originalIp === null) {
        unset($_SERVER['REMOTE_ADDR']);
    } else {
        $_SERVER['REMOTE_ADDR'] = $originalIp;
    }
}
check((int) $wpdb->get_var('SELECT COUNT(*) FROM ' . Schema::inquiryLogTableName()) === $realCount, 'Live inquiry logs changed.');
echo 'InquiryProtectionIntegrationTest: OK (' . $checks . ' checks; real MySQL, stubbed mail/CAPTCHA)' . PHP_EOL;
