<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Repository;

final class HybridAdSpaceRepository implements AdSpaceRepositoryInterface
{
    private DatabaseAdSpaceRepository $database;
    private WooCommerceAdSpaceRepository $woocommerce;

    public function __construct(DatabaseAdSpaceRepository $database, WooCommerceAdSpaceRepository $woocommerce)
    {
        $this->database = $database;
        $this->woocommerce = $woocommerce;
    }

    public function all(): array
    {
        return $this->database->hasRows() ? $this->database->all() : $this->woocommerce->all();
    }

    public function find(int $sourceId): ?array
    {
        return $this->database->hasRows() ? $this->database->find($sourceId) : $this->woocommerce->find($sourceId);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>|null
     */
    public function mapQuery(array $params): ?array
    {
        return $this->database->hasRows() ? $this->database->mapQuery($params) : null;
    }
}
