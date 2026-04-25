<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Repository;

use Billboardy\MapApi\Admin\SettingsPage;
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
    public function mapPointQuery(array $params): array
    {
        global $wpdb;

        $table = Schema::tableName();
        [$where, $values] = $this->queryParts($params, [
            'active_only' => true,
            'require_coordinates' => true,
        ]);

        $sql = "SELECT id, code, title, media_type, media_type_label, location_label, latitude, longitude, size_label, image_url, thumbnail_url
            FROM {$table}
            WHERE " . implode(' AND ', $where) . ' ORDER BY id ASC';

        $rows = $wpdb->get_results($this->prepareQuery($sql, $values), ARRAY_A);

        if (!is_array($rows)) {
            return [];
        }

        return array_map([$this, 'mapPointRow'], $rows);
    }

    /**
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function pagedQuery(array $params, int $page, int $perPage): array
    {
        global $wpdb;

        $table = Schema::tableName();
        [$where, $values] = $this->queryParts($params, [
            'active_only' => true,
        ]);
        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
        $total = (int) $wpdb->get_var($this->prepareQuery($countSql, $values));

        if ($total <= 0) {
            return [
                'items' => [],
                'total' => 0,
            ];
        }

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT * FROM {$table} WHERE {$whereSql} ORDER BY id ASC LIMIT %d OFFSET %d";
        $rows = $wpdb->get_results(
            $wpdb->prepare($sql, array_merge($values, [$perPage, $offset])),
            ARRAY_A
        );

        if (!is_array($rows)) {
            return [
                'items' => [],
                'total' => $total,
            ];
        }

        return [
            'items' => array_map([$this, 'mapRow'], $rows),
            'total' => $total,
        ];
    }

    /**
     * @return array{mediaTypes: array<int, array<string, string>>, cities: array<int, array<string, string>>}
     */
    public function filterOptions(): array
    {
        global $wpdb;

        $table = Schema::tableName();
        $mediaRows = $wpdb->get_results(
            "SELECT DISTINCT media_type, media_type_label FROM {$table} WHERE status != 'deleted' AND (media_type != '' OR media_type_label != '')",
            ARRAY_A
        );
        $cityRows = $wpdb->get_col("SELECT DISTINCT city FROM {$table} WHERE status != 'deleted' AND city != ''");
        $mediaTypes = [];
        $cities = [];

        if (is_array($mediaRows)) {
            foreach ($mediaRows as $row) {
                $mediaType = $this->normalizeMediaType((string) ($row['media_type'] ?? ''), (string) ($row['media_type_label'] ?? ''));

                if ($mediaType === '') {
                    continue;
                }

                $mediaTypes[$mediaType] = [
                    'value' => $mediaType,
                    'label' => $this->mediaTypeLabel($mediaType, (string) ($row['media_type_label'] ?? '')),
                ];
            }
        }

        if (is_array($cityRows)) {
            foreach ($cityRows as $city) {
                $label = trim((string) $city);

                if ($label === '') {
                    continue;
                }

                $cities[$label] = [
                    'value' => $label,
                    'label' => $label,
                ];
            }
        }

        uasort($mediaTypes, static fn(array $a, array $b): int => strcasecmp($a['label'], $b['label']));
        uasort($cities, static fn(array $a, array $b): int => strcasecmp($a['label'], $b['label']));

        return [
            'mediaTypes' => array_values($mediaTypes),
            'cities' => array_values($cities),
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function mapQuery(array $params): array
    {
        global $wpdb;

        $table = Schema::tableName();
        [$where, $values] = $this->queryParts($params, [
            'active_only' => true,
            'require_coordinates' => true,
        ]);

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . ' ORDER BY id ASC';
        $rows = $wpdb->get_results($this->prepareQuery($sql, $values), ARRAY_A);

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

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapPointRow(array $row): array
    {
        $mediaType = $this->normalizeMediaType((string) ($row['media_type'] ?? ''), (string) ($row['media_type_label'] ?? ''));

        return [
            'id' => 'db_' . (string) $row['id'],
            'code' => (string) $row['code'],
            'title' => (string) $row['title'],
            'mediaType' => $mediaType,
            'latitude' => (float) $row['latitude'],
            'longitude' => (float) $row['longitude'],
            'imageUrl' => $this->pointImageUrl($row),
            'locationLabel' => (string) $row['location_label'],
            'sizeLabel' => (string) $row['size_label'],
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function pointImageUrl(array $row): string
    {
        $imageUrl = (string) ($row['thumbnail_url'] ?: $row['image_url']);

        if ($imageUrl !== '') {
            return $imageUrl;
        }

        static $placeholderImageUrl = null;

        if ($placeholderImageUrl === null) {
            $placeholderImageUrl = (string) SettingsPage::get()['placeholder_image_url'];
        }

        return $placeholderImageUrl;
    }

    /**
     * @param array<string, mixed> $params
     * @param array{active_only?: bool, exclude_deleted?: bool, require_coordinates?: bool} $options
     * @return array{0: array<int, string>, 1: array<int, mixed>}
     */
    private function queryParts(array $params, array $options = []): array
    {
        global $wpdb;

        $options = array_merge([
            'active_only' => true,
            'exclude_deleted' => false,
            'require_coordinates' => false,
        ], $options);
        $params = $this->normalizeBoundsParams($params);
        $where = [];
        $values = [];

        if ($options['active_only']) {
            $where[] = "status = 'active'";
        } elseif ($options['exclude_deleted']) {
            $where[] = "status != 'deleted'";
        }

        if ($options['require_coordinates']) {
            $where[] = 'latitude IS NOT NULL';
            $where[] = 'longitude IS NOT NULL';
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

        return [$where, $values];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function normalizeBoundsParams(array $params): array
    {
        foreach (['north', 'south', 'east', 'west'] as $key) {
            if (!isset($params[$key]) || $params[$key] === '' || $params[$key] === null) {
                $params[$key] = null;
            }
        }

        return $params;
    }

    /**
     * @param array<int, mixed> $values
     */
    private function prepareQuery(string $sql, array $values): string
    {
        global $wpdb;

        if ($values === []) {
            return $sql;
        }

        return $wpdb->prepare($sql, $values);
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

        if (strpos($value, 'mega') !== false) {
            return 'mega';
        }

        if (strpos($value, 'facade') !== false || strpos($value, 'fasad') !== false || strpos($value, 'fasád') !== false) {
            return 'facade';
        }

        if (strpos($value, 'plachta') !== false || strpos($value, 'banner') !== false || strpos($value, 'mesh') !== false) {
            return 'banner';
        }

        if (strpos($value, 'bridge') !== false || preg_match('/\bmost\b/u', $value) || strpos($value, 'nadjazd') !== false || strpos($value, 'podjazd') !== false) {
            return 'bridge';
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

        if ($mediaType === 'bridge') {
            return 'Most';
        }

        if ($mediaType === 'banner') {
            return 'Plachta';
        }

        if ($mediaType === 'facade') {
            return 'Fasáda';
        }

        if ($mediaType === 'mega') {
            return 'Mega plocha';
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

        if ($mediaType === 'mega' || $mediaType === 'mega-plocha') {
            $values[] = 'mega';
            $values[] = 'mega-plocha';
            $values[] = '%mega%';

            return '(LOWER(media_type) = %s OR LOWER(media_type) = %s OR LOWER(media_type_label) LIKE %s)';
        }

        if ($mediaType === 'facade' || $mediaType === 'fasada') {
            $values[] = 'facade';
            $values[] = 'fasada';
            $values[] = '%facade%';
            $values[] = '%fasad%';
            $values[] = '%fasád%';

            return '(LOWER(media_type) = %s OR LOWER(media_type) = %s OR LOWER(media_type_label) LIKE %s OR LOWER(media_type_label) LIKE %s OR LOWER(media_type_label) LIKE %s)';
        }

        if ($mediaType === 'banner' || $mediaType === 'plachta') {
            $values[] = 'banner';
            $values[] = 'plachta';
            $values[] = '%plachta%';
            $values[] = '%banner%';
            $values[] = '%mesh%';

            return '(LOWER(media_type) = %s OR LOWER(media_type) = %s OR LOWER(media_type_label) LIKE %s OR LOWER(media_type_label) LIKE %s OR LOWER(media_type_label) LIKE %s)';
        }

        if ($mediaType === 'bridge' || $mediaType === 'most') {
            $values[] = 'bridge';
            $values[] = 'most';
            $values[] = '%bridge%';
            $values[] = '%most%';
            $values[] = '%nadjazd%';
            $values[] = '%podjazd%';

            return '(LOWER(media_type) = %s OR LOWER(media_type) = %s OR LOWER(media_type_label) LIKE %s OR LOWER(media_type_label) LIKE %s OR LOWER(media_type_label) LIKE %s OR LOWER(media_type_label) LIKE %s)';
        }

        $values[] = $mediaType;

        return 'LOWER(media_type) = %s';
    }
}
