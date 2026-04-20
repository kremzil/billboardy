<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Repository;

interface AdSpaceRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $sourceId): ?array;
}

