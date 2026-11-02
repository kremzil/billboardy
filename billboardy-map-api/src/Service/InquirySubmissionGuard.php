<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Service;

use Billboardy\MapApi\Repository\InquiryLogRepository;

final class InquirySubmissionGuard
{
    private const DUPLICATE_SECONDS = 900;
    private const EMAIL_COOLDOWN_SECONDS = 60;
    private const EMAIL_HOUR_LIMIT = 5;
    private const IP_TEN_MINUTE_LIMIT = 20;

    private InquiryLogRepository $logs;

    public function __construct(InquiryLogRepository $logs)
    {
        $this->logs = $logs;
    }

    /**
     * Call only after CAPTCHA and input validation. A pending log is the durable
     * reservation; mail failure releases deduplication by marking that row failed.
     *
     * @param array<string, string> $record
     * @return array{id: int, duplicate: bool}|\WP_Error
     */
    public function reserve(array $record, string $remoteIp)
    {
        $record['email'] = strtolower(trim($record['email']));
        $record['request_hash'] = $this->fingerprint($record);
        // Never trust forwarded headers. Skip unknown/private proxy addresses so
        // a hosting proxy cannot accidentally impose one quota on every visitor.
        $publicIp = filter_var($remoteIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        $record['ip_hash'] = $publicIp === false ? '' : hash_hmac('sha256', (string) inet_pton($publicIp), wp_salt('auth'));

        if (!$this->logs->acquireSubmissionLock()) {
            return $this->unavailable();
        }

        try {
            $now = time();
            $duplicate = $this->logs->recentDuplicateStatus($record['request_hash'], $this->cutoff($now, self::DUPLICATE_SECONDS));
            if ($duplicate === 'sent') {
                return ['id' => 0, 'duplicate' => true];
            }
            if ($duplicate === 'pending') {
                return new \WP_Error('billboardy_inquiry_pending', __('Váš dopyt sa už spracúva. Počkajte chvíľu a skúste to znova.', 'billboardy-map-api'), ['status' => 409, 'retry_after' => 60]);
            }

            if ($this->logs->recentCount('email', $record['email'], $this->cutoff($now, self::EMAIL_COOLDOWN_SECONDS), true) > 0) {
                return $this->limited(self::EMAIL_COOLDOWN_SECONDS);
            }
            if ($this->logs->recentCount('email', $record['email'], $this->cutoff($now, 3600)) >= self::EMAIL_HOUR_LIMIT) {
                return $this->limited(3600);
            }
            if ($record['ip_hash'] !== '' && $this->logs->recentCount('ip_hash', $record['ip_hash'], $this->cutoff($now, 600)) >= self::IP_TEN_MINUTE_LIMIT) {
                return $this->limited(600);
            }

            $id = $this->logs->create($record);
            return $id > 0 ? ['id' => $id, 'duplicate' => false] : $this->unavailable();
        } catch (\RuntimeException $error) {
            return $this->unavailable();
        } finally {
            $this->logs->releaseSubmissionLock();
        }
    }

    /** @param array<string, string> $record */
    private function fingerprint(array $record): string
    {
        $content = [];
        foreach (['source', 'name', 'email', 'phone', 'company', 'note'] as $field) {
            $content[$field] = $record[$field] ?? '';
        }
        $content['details'] = json_decode($record['details_json'] ?? '{}', true);
        $items = json_decode($record['items_json'] ?? '[]', true);
        // Map selection order and changing presentation labels do not make a new inquiry.
        $content['items'] = array_column(is_array($items) ? $items : [], 'id');
        sort($content['items'], SORT_STRING);

        return hash_hmac('sha256', (string) wp_json_encode($this->normalize($content)), wp_salt('auth'));
    }

    /** @param mixed $value @return mixed */
    private function normalize($value)
    {
        if (is_array($value)) {
            ksort($value);
            return array_map([$this, 'normalize'], $value);
        }
        return is_string($value) ? trim((string) preg_replace('/\s+/u', ' ', $value)) : $value;
    }

    private function cutoff(int $now, int $seconds): string
    {
        return wp_date('Y-m-d H:i:s', $now - $seconds, wp_timezone());
    }

    private function limited(int $retryAfter): \WP_Error
    {
        return new \WP_Error('billboardy_inquiry_rate_limited', __('Odoslali ste príliš veľa dopytov. Skúste to prosím neskôr.', 'billboardy-map-api'), ['status' => 429, 'retry_after' => $retryAfter]);
    }

    private function unavailable(): \WP_Error
    {
        return new \WP_Error('billboardy_inquiry_unavailable', __('Dopyt sa teraz nedá odoslať. Skúste to prosím o chvíľu.', 'billboardy-map-api'), ['status' => 503, 'retry_after' => 5]);
    }
}
