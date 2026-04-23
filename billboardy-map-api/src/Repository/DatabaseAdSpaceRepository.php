<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Repository;

use Billboardy\MapApi\Database\Schema;

final class DatabaseAdSpaceRepository implements AdSpaceRepositoryInterface
{
    public function all(): array
    {
        global $wpdb;

        $table = Schema::tableName();
        $rows = $wpdb->get_results("SELECT * FROM {$table} WHERE status != 'deleted' ORDER BY id ASC", ARRAY_A);

        if (!is_array($rows)) {
            return [];
        }

        return array_map([$this, 'mapRow'], $rows);
    }

    public function find(int $sourceId): ?array
    {
        global $wpdb;

        $table = Schema::tableName();
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND status != 'deleted' LIMIT 1", $sourceId),
            ARRAY_A
        );

        return is_array($row) ? $this->mapRow($row) : null;
    }

    public function hasRows(): bool
    {
        global $wpdb;

        $table = Schema::tableName();
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        return $count > 0;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function mapQuery(array $params): array
    {
        global $wpdb;

        $table = Schema::tableName();
        $where = [
            "status = 'active'",
            'latitude IS NOT NULL',
            'longitude IS NOT NULL',
        ];
        $values = [];

        foreach (['north', 'south', 'east', 'west'] as $key) {
            if (!isset($params[$key]) || $params[$key] === '' || $params[$key] === null) {
                $params[$key] = null;
            }
        }

        if ($params['north'] !== null && $params['south'] !== null && $params['east'] !== null && $params['west'] !== null) {
            $north = (float) $params['north'];
            $south = (float) $params['south'];
            $east = (float) $params['east'];
            $west = (float) $params['west'];

            if ($north >= $south && $east >= $west) {
                $where[] = 'latitude <= %f';
                $values[] = $north;
                $where[] = 'latitude >= %f';
                $values[] = $south;
                $where[] = 'longitude <= %f';
                $values[] = $east;
                $where[] = 'longitude >= %f';
                $values[] = $west;
            }
        }

        $mediaType = isset($params['media_type']) ? strtolower(trim((string) $params['media_type'])) : '';

        if ($mediaType !== '') {
            $mediaSql = $this->mediaTypeWhereSql($mediaType, $values);

            if ($mediaSql !== '') {
                $where[] = $mediaSql;
            }
        }

        $city = isset($params['city']) ? trim((string) $params['city']) : '';

        if ($city !== '') {
            $where[] = 'city = %s';
            $values[] = $city;
        }

        $search = isset($params['search']) ? trim((string) $params['search']) : '';

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = '(code LIKE %s OR title LIKE %s OR location_label LIKE %s OR address_text LIKE %s)';
            array_push($values, $like, $like, $like, $like);
        }

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . ' ORDER BY id ASC';

        if ($values !== []) {
            $sql = $wpdb->prepare($sql, $values);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);

        if (!is_array($rows)) {
            return [];
        }

        return array_map([$this, 'mapRow'], $rows);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        $gallery = [];
        $galleryJson = (string) ($row['gallery_json'] ?? '');
        $mediaType = $this->normalizeMediaType((string) ($row['media_type'] ?? ''), (string) ($row['media_type_label'] ?? ''));
        $mediaTypeLabel = $this->mediaTypeLabel($mediaType, (string) ($row['media_type_label'] ?? ''));

        if ($galleryJson !== '') {
            $decoded = json_decode($galleryJson, true);
            $gallery = is_array($decoded) ? $decoded : [];
        }

        return [
            'ad_space_model' => true,
            'id' => 'db_' . (string) $row['id'],
            'sourceId' => (int) $row['id'],
            'code' => (string) $row['code'],
            'title' => (string) $row['title'],
            'mediaType' => $mediaType,
            'mediaTypeLabel' => $mediaTypeLabel,
            'locationLabel' => (string) $row['location_label'],
            'city' => (string) $row['city'],
            'region' => (string) $row['region'],
            'district' => (string) $row['district'],
            'addressText' => (string) $row['address_text'],
            'latitude' => $row['latitude'] === null ? null : (float) $row['latitude'],
            'longitude' => $row['longitude'] === null ? null : (float) $row['longitude'],
            'sizeLabel' => (string) $row['size_label'],
            'widthCm' => $row['width_cm'] === null ? null : (int) $row['width_cm'],
            'heightCm' => $row['height_cm'] === null ? null : (int) $row['height_cm'],
            'descriptionHtml' => (string) $row['description_html'],
            'imageUrl' => (string) ($row['thumbnail_url'] ?: $row['image_url']),
            'status' => (string) $row['status'],
            'visibility' => (string) $row['visibility'],
            'mapPinType' => $mediaType,
            'detailUrl' => null,
            'source' => (string) $row['source'],
            'excerpt' => (string) $row['excerpt'],
            'gallery' => array_values($gallery),
            'seoText' => null,
            'rawGps' => $row['latitude'] !== null && $row['longitude'] !== null ? (string) $row['latitude'] . ', ' . (string) $row['longitude'] : '',
            'rawSize' => (string) $row['size_label'],
            'rawMediaType' => (string) ($row['media_type_label'] ?: $row['media_type']),
            'isFeatured' => false,
            'updatedAt' => (string) $row['updated_at'],
        ];
    }

    private function normalizeMediaType(string $mediaType, string $mediaTypeLabel): string
    {
        $value = strtolower(trim($mediaType . ' ' . $mediaTypeLabel));

        if (strpos($value, 'billboard') !== false || preg_match('/\bblb\b/', $value)) {
            return 'billboard';
        }

        if (strpos($value, 'bigboard') !== false) {
            return 'bigboard';
        }

        if (preg_match('/\bcl[a-z0-9_+-]*\b/', $value) || strpos($value, 'city') !== false) {
            return 'citylight';
        }

        return $mediaType !== '' ? $mediaType : 'unknown';
    }

    private function mediaTypeLabel(string $mediaType, string $fallback): string
    {
        if ($mediaType === 'billboard') {
            return 'Billboard';
        }

        if ($mediaType === 'bigboard') {
            return 'Bigboard';
        }

        if ($mediaType === 'citylight') {
            return 'Citylight';
        }

        return $fallback !== '' ? $fallback : 'Neznáme';
    }

    /**
     * @param array<int, mixed> $values
     */
    private function mediaTypeWhereSql(string $mediaType, array &$values): string
    {
        if ($mediaType === 'billboard') {
            $values[] = 'billboard';
            $values[] = 'blb';
            $values[] = '%billboard%';

            return '(LOWER(media_type) = %s OR LOWER(media_type) = %s OR LOWER(media_type_label) LIKE %s)';
        }

        if ($mediaType === 'bigboard') {
            $values[] = 'bigboard';
            $values[] = '%bigboard%';

            return '(LOWER(media_type) = %s OR LOWER(media_type_label) LIKE %s)';
        }

        if ($mediaType === 'citylight') {
            $values[] = 'citylight';
            $values[] = 'cl%';
            $values[] = 'cl%';
            $values[] = '%city%';

            return '(LOWER(media_type) = %s OR LOWER(media_type) LIKE %s OR LOWER(media_type_label) LIKE %s OR LOWER(media_type_label) LIKE %s)';
        }

        $values[] = $mediaType;

        return 'LOWER(media_type) = %s';
    }
}
