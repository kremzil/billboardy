<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Repository;

final class HybridAdSpaceRepository implements AdSpaceRepositoryInterface
{
    private DatabaseAdSpaceRepository $database;
    private WooCommerceAdSpaceRepository $woocommerce;
    private ?bool $hasRows = null;

    public function __construct(DatabaseAdSpaceRepository $database, WooCommerceAdSpaceRepository $woocommerce)
    {
        $this->database = $database;
        $this->woocommerce = $woocommerce;
    }

    public function all(): array
    {
        return $this->useDatabase() ? $this->database->all() : $this->woocommerce->all();
    }

    public function find(int $sourceId): ?array
    {
        return $this->useDatabase() ? $this->database->find($sourceId) : $this->woocommerce->find($sourceId);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>|null
     */
    public function mapPointQuery(array $params): ?array
    {
        return $this->useDatabase() ? $this->database->mapPointQuery($params) : null;
    }

    /**
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, total: int}|null
     */
    public function pagedQuery(array $params, int $page, int $perPage): ?array
    {
        return $this->useDatabase() ? $this->database->pagedQuery($params, $page, $perPage) : null;
    }

    /**
     * @return array{mediaTypes: array<int, array<string, string>>, cities: array<int, array<string, string>>}|null
     */
    public function filterOptions(): ?array
    {
        return $this->useDatabase() ? $this->database->filterOptions() : null;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>|null
     */
    public function mapQuery(array $params): ?array
    {
        return $this->useDatabase() ? $this->database->mapQuery($params) : null;
    }

    private function useDatabase(): bool
    {
        if ($this->hasRows === null) {
            $this->hasRows = $this->database->hasRows();
        }

        return $this->hasRows;
    }
}
