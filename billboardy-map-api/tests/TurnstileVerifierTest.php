<?php

declare(strict_types=1);

use Billboardy\MapApi\Service\TurnstileVerifier;

define('BILLBOARDY_TURNSTILE_SECRET', 'test-secret');

$turnstileTestResponse = [];

function wp_remote_post(string $url, array $args): array
{
    global $turnstileTestResponse;

    if ($url !== 'https://challenges.cloudflare.com/turnstile/v0/siteverify') {
        throw new RuntimeException('Unexpected verification URL.');
    }

    if (($args['body']['secret'] ?? '') !== 'test-secret') {
        throw new RuntimeException('Secret was not sent to Siteverify.');
    }

    return $turnstileTestResponse;
}
function is_wp_error($value): bool
{
    return false;
}

function wp_remote_retrieve_response_code(array $response): int
{
    return (int) ($response['status'] ?? 0);
}

function wp_remote_retrieve_body(array $response): string
{
    return (string) ($response['body'] ?? '');
}

require_once dirname(__DIR__) . '/src/Service/TurnstileVerifier.php';

$verifier = new TurnstileVerifier();

$turnstileTestResponse = [
    'status' => 200,
    'body' => json_encode(['success' => true, 'action' => 'inquiry_contact']),
];
assertSame(true, $verifier->verify('valid-token', 'inquiry_contact', '192.0.2.10'), 'valid matching token');
assertSame(false, $verifier->verify('valid-token', 'inquiry_map', '192.0.2.10'), 'mismatched action');

$turnstileTestResponse = [
    'status' => 200,
    'body' => json_encode(['success' => false, 'action' => 'inquiry_contact']),
];
assertSame(false, $verifier->verify('rejected-token', 'inquiry_contact'), 'rejected token');
assertSame(false, $verifier->verify('', 'inquiry_contact'), 'missing token');

echo "TurnstileVerifierTest: OK\n";

function assertSame(bool $expected, bool $actual, string $case): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            '%s: expected %s, got %s',
            $case,
            $expected ? 'true' : 'false',
            $actual ? 'true' : 'false'
        ));
    }
}
