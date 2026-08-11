<?php

declare(strict_types=1);

use Billboardy\MapApi\Repository\InquiryLogRepository;

const ARRAY_A = 'ARRAY_A';
const DAY_IN_SECONDS = 86400;

final class FakeWpdb
{
    public string $prefix = 'wp_';
    public int $insert_id = 42;
    public array $inserts = [];
    public array $updates = [];
    public array $queries = [];

    public function insert(string $table, array $data, array $formats): int
    {
        $this->inserts[] = compact('table', 'data', 'formats');

        return 1;
    }

    public function update(string $table, array $data, array $where, array $formats, array $whereFormats): int
    {
        $this->updates[] = compact('table', 'data', 'where', 'formats', 'whereFormats');

        return 1;
    }

    public function prepare(string $query, ...$args): string
    {
        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }

        foreach ($args as $arg) {
            $replacement = is_int($arg) ? (string) $arg : "'" . addslashes((string) $arg) . "'";
            $query = (string) preg_replace('/%[sd]/', $replacement, $query, 1);
        }

        return $query;
    }

    public function query(string $query): int
    {
        $this->queries[] = $query;

        return 3;
    }
}

function current_time(string $type): string
{
    return '2026-08-11 16:45:00';
}

function wp_date(string $format, int $timestamp, $timezone): string
{
    return gmdate($format, $timestamp);
}

function wp_timezone(): DateTimeZone
{
    return new DateTimeZone('UTC');
}

require_once dirname(__DIR__) . '/src/Database/Schema.php';
require_once dirname(__DIR__) . '/src/Repository/InquiryLogRepository.php';

$wpdb = new FakeWpdb();
$repository = new InquiryLogRepository();
$id = $repository->create([
    'source' => 'contact',
    'name' => 'Ján Novák',
    'email' => 'jan@example.sk',
    'phone' => '+421900000000',
    'company' => 'Príklad s.r.o.',
    'type_format' => 'Billboard',
    'note' => 'Prosím o ponuku.',
    'recipient_email' => 'info@billboardy.sk',
    'subject' => 'Nový dopyt',
]);

assertSame(42, $id, 'created record id');
assertSame('wp_billboardy_inquiry_logs', $wpdb->inserts[0]['table'], 'log table');
assertSame('pending', $wpdb->inserts[0]['data']['status'], 'initial status');
assertSame('jan@example.sk', $wpdb->inserts[0]['data']['email'], 'stored email');

$repository->markSent($id);
assertSame('sent', $wpdb->updates[0]['data']['status'], 'sent status');
assertSame('2026-08-11 16:45:00', $wpdb->updates[0]['data']['sent_at'], 'sent timestamp');

$repository->markFailed($id, 'wp_mail returned false');
assertSame('failed', $wpdb->updates[1]['data']['status'], 'failed status');
assertSame('wp_mail returned false', $wpdb->updates[1]['data']['error_message'], 'failure reason');

assertSame(3, $repository->deleteExpired(180), 'expired row count');
assertContains('DELETE FROM wp_billboardy_inquiry_logs', $wpdb->queries[0], 'cleanup query table');
assertContains('WHERE created_at <', $wpdb->queries[0], 'cleanup cutoff');

echo "InquiryLogRepositoryTest: OK\n";

function assertSame($expected, $actual, string $case): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($case . ': values differ');
    }
}

function assertContains(string $needle, string $haystack, string $case): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($case . ': expected fragment not found');
    }
}
