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
     * @return array<int, array<string, mixed>>
     */
    public function mapPoints(array $params): array
    {
        $cacheKey = $this->cacheKey('map_points', $params);
        $cached = get_transient($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $points = [];

        foreach ($this->filteredAdSpaces($params) as $adSpace) {
            $point = $this->mapper->mapPoint($adSpace);

            if ($point !== null) {
                $points[] = $point;
            }
        }

        set_transient($cacheKey, $points, $this->ttl());

        return $points;
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
