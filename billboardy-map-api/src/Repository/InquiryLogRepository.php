<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Repository;

use Billboardy\MapApi\Database\Schema;

final class InquiryLogRepository
{
    /**
     * @param array<string, string> $record
     */
    public function create(array $record): int
    {
        global $wpdb;

        $now = current_time('mysql');
        $inserted = $wpdb->insert(
            Schema::inquiryLogTableName(),
            [
                'source' => $record['source'] ?? 'map',
                'status' => 'pending',
                'name' => $record['name'] ?? '',
                'email' => $record['email'] ?? '',
                'phone' => $record['phone'] ?? '',
                'company' => $record['company'] ?? '',
                'type_format' => $record['type_format'] ?? '',
                'note' => $record['note'] ?? '',
                'details_json' => $record['details_json'] ?? '',
                'items_json' => $record['items_json'] ?? '',
                'recipient_email' => $record['recipient_email'] ?? '',
                'subject' => $record['subject'] ?? '',
                'error_message' => '',
                'created_at' => $now,
                'updated_at' => $now,
                'sent_at' => null,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $inserted === false ? 0 : (int) $wpdb->insert_id;
    }

    public function markSent(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        global $wpdb;
        $now = current_time('mysql');
        $wpdb->update(
            Schema::inquiryLogTableName(),
            ['status' => 'sent', 'updated_at' => $now, 'sent_at' => $now],
            ['id' => $id],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    public function markFailed(int $id, string $errorMessage): void
    {
        if ($id <= 0) {
            return;
        }

        global $wpdb;
        $wpdb->update(
            Schema::inquiryLogTableName(),
            [
                'status' => 'failed',
                'error_message' => $errorMessage,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    public function search(string $search, string $status, string $source, int $page, int $perPage): array
    {
        global $wpdb;

        [$where, $params] = $this->whereClause($search, $status, $source);
        $table = Schema::inquiryLogTableName();
        $countSql = "SELECT COUNT(*) FROM {$table} {$where}";
        $total = (int) $wpdb->get_var($params === [] ? $countSql : $wpdb->prepare($countSql, $params));
        $offset = max(0, ($page - 1) * $perPage);
        $listParams = array_merge($params, [$perPage, $offset]);
        $listSql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d";
        $rows = $wpdb->get_results($wpdb->prepare($listSql, $listParams), ARRAY_A);

        return [
            'rows' => is_array($rows) ? $rows : [],
            'total' => $total,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(string $search, string $status, string $source): array
    {
        global $wpdb;

        [$where, $params] = $this->whereClause($search, $status, $source);
        $table = Schema::inquiryLogTableName();
        $sql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC, id DESC LIMIT 5000";
        $rows = $wpdb->get_results($params === [] ? $sql : $wpdb->prepare($sql, $params), ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    public function deleteExpired(int $retentionDays): int
    {
        global $wpdb;

        $retentionDays = max(1, $retentionDays);
        $cutoff = wp_date('Y-m-d H:i:s', time() - ($retentionDays * DAY_IN_SECONDS), wp_timezone());
        $deleted = $wpdb->query($wpdb->prepare(
            'DELETE FROM ' . Schema::inquiryLogTableName() . ' WHERE created_at < %s',
            $cutoff
        ));

        return $deleted === false ? 0 : (int) $deleted;
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    private function whereClause(string $search, string $status, string $source): array
    {
        global $wpdb;

        $clauses = ['1=1'];
        $params = [];

        if (in_array($status, ['pending', 'sent', 'failed'], true)) {
            $clauses[] = 'status = %s';
            $params[] = $status;
        }

        if (in_array($source, ['map', 'contact', 'quick'], true)) {
            $clauses[] = 'source = %s';
            $params[] = $source;
        }

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $clauses[] = '(name LIKE %s OR email LIKE %s OR phone LIKE %s OR company LIKE %s OR subject LIKE %s)';
            array_push($params, $like, $like, $like, $like, $like);
        }

        return ['WHERE ' . implode(' AND ', $clauses), $params];
    }
}
