<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Service;

final class TurnstileVerifier
{
    private const SITEVERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function verify(string $token, string $expectedAction, string $remoteIp = ''): bool
    {
        $secret = $this->secret();

        if ($secret === '' || $token === '' || $expectedAction === '') {
            return false;
        }

        $body = [
            'secret' => $secret,
            'response' => $token,
        ];

        if ($remoteIp !== '') {
            $body['remoteip'] = $remoteIp;
        }

        $response = wp_remote_post(self::SITEVERIFY_URL, [
            'timeout' => 10,
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $result = json_decode((string) wp_remote_retrieve_body($response), true);

        return $status >= 200
            && $status < 300
            && is_array($result)
            && ($result['success'] ?? false) === true
            && hash_equals($expectedAction, (string) ($result['action'] ?? ''));
    }

    private function secret(): string
    {
        if (!defined('BILLBOARDY_TURNSTILE_SECRET')) {
            return '';
        }

        return trim((string) constant('BILLBOARDY_TURNSTILE_SECRET'));
    }
}
