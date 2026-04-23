<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Service;

use Billboardy\MapApi\Admin\SettingsPage;
use Billboardy\MapApi\Domain\AdSpaceMapper;
use Billboardy\MapApi\Plugin;
use Billboardy\MapApi\Repository\AdSpaceRepositoryInterface;

final class AdSpaceService
{
    private AdSpaceRepositoryInterface $repository;
    private AdSpaceMapper $mapper;

    public function __construct(AdSpaceRepositoryInterface $repository, AdSpaceMapper $mapper)
    {
        $this->repository = $repository;
        $this->mapper = $mapper;
    }

    /**
     * @param array<string, mixed> $params
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, int>}
     */
    public function adSpaces(array $params): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(1, min(200, (int) ($params['per_page'] ?? 100)));
        $cacheKey = $this->cacheKey('ad_spaces', $params);

        $cached = get_transient($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $items = $this->filteredAdSpaces($params);
        $total = count($items);
        $offset = ($page - 1) * $perPage;
        $paged = array_slice($items, $offset, $perPage);

        $result = [
            'data' => array_values($paged),
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => (int) ceil($total / $perPage),
            ],
        ];

        set_transient($cacheKey, $result, $this->ttl());

        return $result;
    }

    /**
     * @param array<string, mixed> $params
     * @return array{mode: string, items: array<int, array<string, mixed>>, data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function mapPoints(array $params): array
    {
        $params = $this->normalizeMapPointParams($params);
        $cacheKey = $this->cacheKey('map_points', $params);
        $cached = get_transient($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $zoom = (int) $params['zoom'];
        $points = [];

        foreach ($this->mapPointAdSpaces($params) as $adSpace) {
            $point = $this->mapper->mapPoint($adSpace);

            if ($point !== null) {
                $point['type'] = 'point';
                $points[] = $point;
            }
        }

        $points = $this->dedupeMapPoints($points);
        $mode = $zoom >= 12 || trim((string) ($params['search'] ?? '')) !== '' ? 'points' : 'clusters';
        $items = $mode === 'points' ? $points : $this->clusterPoints($points, $zoom);

        $result = [
            'mode' => $mode,
            'items' => $items,
            'data' => $items,
            'meta' => [
                'total' => count($points),
                'returned' => count($items),
                'zoom' => $zoom,
            ],
        ];

        set_transient($cacheKey, $result, $this->ttl());

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function adSpace(string $id): ?array
    {
        $sourceId = $this->sourceIdFromApiId($id);

        if ($sourceId <= 0) {
            return null;
        }

        $cacheKey = $this->cacheKey('ad_space', ['id' => $id]);
        $cached = get_transient($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $source = $this->repository->find($sourceId);

        if ($source === null) {
            return null;
        }

        $mapped = $this->mapper->map($source);
        set_transient($cacheKey, $mapped, $this->ttl());

        return $mapped;
    }

    /**
     * @return array{mediaTypes: array<int, array<string, string>>, cities: array<int, array<string, string>>}
     */
    public function filters(): array
    {
        $cacheKey = $this->cacheKey('filters', []);
        $cached = get_transient($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $mediaTypes = [];
        $cities = [];

        foreach ($this->allMapped() as $adSpace) {
            if ($adSpace['mediaType'] !== '') {
                $mediaTypes[$adSpace['mediaType']] = [
                    'value' => $adSpace['mediaType'],
                    'label' => $adSpace['mediaTypeLabel'],
                ];
            }

            if ($adSpace['city'] !== '') {
                $cities[$adSpace['city']] = [
                    'value' => $adSpace['city'],
                    'label' => $adSpace['city'],
                ];
            }
        }

        uasort($mediaTypes, static fn(array $a, array $b): int => strcasecmp($a['label'], $b['label']));
        uasort($cities, static fn(array $a, array $b): int => strcasecmp($a['label'], $b['label']));

        $result = [
            'mediaTypes' => array_values($mediaTypes),
            'cities' => array_values($cities),
        ];

        set_transient($cacheKey, $result, $this->ttl());

        return $result;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private function filteredAdSpaces(array $params): array
    {
        $mediaType = isset($params['media_type']) ? trim((string) $params['media_type']) : '';
        $city = isset($params['city']) ? trim((string) $params['city']) : '';
        $search = isset($params['search']) ? $this->searchText((string) $params['search']) : '';
        $bounds = $this->bounds($params);

        return array_values(array_filter($this->allMapped(), function (array $adSpace) use ($mediaType, $city, $search, $bounds): bool {
            if ($adSpace['status'] !== 'active') {
                return false;
            }

            if ($bounds !== null && !$this->isInsideBounds($adSpace, $bounds)) {
                return false;
            }

            if ($mediaType !== '' && $adSpace['mediaType'] !== $mediaType) {
                return false;
            }

            if ($city !== '' && $adSpace['city'] !== $city) {
                return false;
            }

            if ($search !== '') {
                $haystack = $this->searchText(implode(' ', [
                    (string) $adSpace['code'],
                    (string) $adSpace['title'],
                    (string) $adSpace['locationLabel'],
                    (string) $adSpace['addressText'],
                ]));

                if (strpos($haystack, $search) === false) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private function mapPointAdSpaces(array $params): array
    {
        if (method_exists($this->repository, 'mapQuery')) {
            $sources = $this->repository->mapQuery($params);

            if (is_array($sources)) {
                return array_map(function (array $source): array {
                    return $this->mapper->map($source);
                }, $sources);
            }
        }

        return $this->filteredAdSpaces($params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function normalizeMapPointParams(array $params): array
    {
        $zoom = max(1, min(21, (int) ($params['zoom'] ?? 12)));
        $precision = $zoom >= 13 ? 4 : ($zoom >= 10 ? 3 : 2);

        $normalized = [
            'zoom' => $zoom,
            'media_type' => isset($params['media_type']) ? trim((string) $params['media_type']) : '',
            'city' => isset($params['city']) ? trim((string) $params['city']) : '',
            'search' => isset($params['search']) ? trim((string) $params['search']) : '',
        ];

        foreach (['north', 'south', 'east', 'west'] as $key) {
            if (!isset($params[$key]) || $params[$key] === '' || $params[$key] === null) {
                continue;
            }

            $normalized[$key] = round((float) $params[$key], $precision);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $params
     * @return array{north: float, south: float, east: float, west: float}|null
     */
    private function bounds(array $params): ?array
    {
        foreach (['north', 'south', 'east', 'west'] as $key) {
            if (!isset($params[$key]) || $params[$key] === '' || $params[$key] === null) {
                return null;
            }
        }

        $north = (float) $params['north'];
        $south = (float) $params['south'];
        $east = (float) $params['east'];
        $west = (float) $params['west'];

        if ($north < $south || $east < $west) {
            return null;
        }

        return [
            'north' => $north,
            'south' => $south,
            'east' => $east,
            'west' => $west,
        ];
    }

    /**
     * @param array<string, mixed> $adSpace
     * @param array{north: float, south: float, east: float, west: float} $bounds
     */
    private function isInsideBounds(array $adSpace, array $bounds): bool
    {
        if (!is_float($adSpace['latitude']) || !is_float($adSpace['longitude'])) {
            return false;
        }

        return $adSpace['latitude'] <= $bounds['north']
            && $adSpace['latitude'] >= $bounds['south']
            && $adSpace['longitude'] <= $bounds['east']
            && $adSpace['longitude'] >= $bounds['west'];
    }

    /**
     * @param array<int, array<string, mixed>> $points
     * @return array<int, array<string, mixed>>
     */
    private function dedupeMapPoints(array $points): array
    {
        $deduped = [];

        foreach ($points as $point) {
            $key = $this->mapPointDedupeKey($point);

            if (!isset($deduped[$key]) || $this->mapPointScore($point) > $this->mapPointScore($deduped[$key])) {
                $deduped[$key] = $point;
            }
        }

        return array_values($deduped);
    }

    /**
     * @param array<string, mixed> $point
     */
    private function mapPointDedupeKey(array $point): string
    {
        return implode('|', [
            $this->searchText((string) ($point['code'] ?? '')),
            $this->searchText((string) ($point['mediaType'] ?? '')),
            number_format((float) ($point['latitude'] ?? 0), 6, '.', ''),
            number_format((float) ($point['longitude'] ?? 0), 6, '.', ''),
        ]);
    }

    /**
     * @param array<string, mixed> $point
     */
    private function mapPointScore(array $point): int
    {
        $score = 0;

        foreach (['imageUrl', 'title', 'locationLabel', 'sizeLabel'] as $field) {
            if (trim((string) ($point[$field] ?? '')) !== '') {
                $score++;
            }
        }

        return $score;
    }

    /**
     * @param array<int, array<string, mixed>> $points
     * @return array<int, array<string, mixed>>
     */
    private function clusterPoints(array $points, int $zoom): array
    {
        $cellSize = $this->clusterCellSize($zoom);
        $clusters = [];

        foreach ($points as $point) {
            $latitude = (float) $point['latitude'];
            $longitude = (float) $point['longitude'];
            $key = (string) floor($latitude / $cellSize) . ':' . (string) floor($longitude / $cellSize);

            if (!isset($clusters[$key])) {
                $clusters[$key] = [
                    'count' => 0,
                    'sum_latitude' => 0.0,
                    'sum_longitude' => 0.0,
                    'north' => $latitude,
                    'south' => $latitude,
                    'east' => $longitude,
                    'west' => $longitude,
                    'sample' => $point,
                    'media_types' => [],
                ];
            }

            $mediaType = (string) ($point['mediaType'] ?? 'unknown');
            $clusters[$key]['count']++;
            $clusters[$key]['sum_latitude'] += $latitude;
            $clusters[$key]['sum_longitude'] += $longitude;
            $clusters[$key]['north'] = max($clusters[$key]['north'], $latitude);
            $clusters[$key]['south'] = min($clusters[$key]['south'], $latitude);
            $clusters[$key]['east'] = max($clusters[$key]['east'], $longitude);
            $clusters[$key]['west'] = min($clusters[$key]['west'], $longitude);
            $clusters[$key]['media_types'][$mediaType] = ($clusters[$key]['media_types'][$mediaType] ?? 0) + 1;
        }

        $items = [];

        foreach ($clusters as $key => $cluster) {
            if ($cluster['count'] === 1 && $zoom >= 10) {
                $items[] = $cluster['sample'];
                continue;
            }

            $latitude = $cluster['sum_latitude'] / $cluster['count'];
            $longitude = $cluster['sum_longitude'] / $cluster['count'];
            $sample = $cluster['sample'];
            $mediaType = $this->dominantMediaType($cluster['media_types']);

            $items[] = [
                'type' => 'cluster',
                'id' => 'cluster_' . $zoom . '_' . md5((string) $key),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'count' => $cluster['count'],
                'title' => (string) $cluster['count'] . ' reklamných plôch',
                'mediaType' => $mediaType,
                'locationLabel' => (string) ($sample['locationLabel'] ?? ''),
                'bounds' => [
                    'north' => $cluster['north'],
                    'south' => $cluster['south'],
                    'east' => $cluster['east'],
                    'west' => $cluster['west'],
                ],
            ];
        }

        usort($items, static function (array $a, array $b): int {
            return (int) ($b['count'] ?? 1) <=> (int) ($a['count'] ?? 1);
        });

        return $items;
    }

    private function clusterCellSize(int $zoom): float
    {
        if ($zoom <= 6) {
            return 0.9;
        }

        if ($zoom === 7) {
            return 0.55;
        }

        if ($zoom === 8) {
            return 0.32;
        }

        if ($zoom === 9) {
            return 0.18;
        }

        if ($zoom === 10) {
            return 0.1;
        }

        return 0.05;
    }

    /**
     * @param array<string, int> $mediaTypes
     */
    private function dominantMediaType(array $mediaTypes): string
    {
        arsort($mediaTypes);

        if (count($mediaTypes) > 1) {
            $total = array_sum($mediaTypes);
            $first = reset($mediaTypes);

            if ((int) $first < $total) {
                return 'mixed';
            }
        }

        $mediaType = (string) array_key_first($mediaTypes);

        return $mediaType !== '' ? $mediaType : 'unknown';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allMapped(): array
    {
        $cacheKey = $this->cacheKey('all_mapped', []);
        $cached = get_transient($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $mapped = array_map(function (array $source): array {
            return $this->mapper->map($source);
        }, $this->repository->all());

        set_transient($cacheKey, $mapped, $this->ttl());

        return $mapped;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function cacheKey(string $scope, array $params): string
    {
        ksort($params);

        $version = (int) get_option(Plugin::OPTION_CACHE_VERSION, 1);

        return 'billboardy_map_' . $scope . '_' . $version . '_' . md5(wp_json_encode($params));
    }

    private function ttl(): int
    {
        return (int) SettingsPage::get()['cache_ttl'];
    }

    private function sourceIdFromApiId(string $id): int
    {
        if (preg_match('/^wc_(\d+)$/', $id, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/^db_(\d+)$/', $id, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/^\d+$/', $id)) {
            return (int) $id;
        }

        return 0;
    }

    private function searchText(string $value): string
    {
        $value = function_exists('remove_accents') ? remove_accents($value) : $value;

        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }
}
