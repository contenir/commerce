<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Repository;

use Contenir\Commerce\Model\Entity\ArtworkEntity;
use Contenir\Db\Model\Repository\AbstractRepository;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\TableIdentifier;

class ArtworkRepository extends AbstractRepository
{
    /** @var string|array<string, string>|TableIdentifier */
    protected TableIdentifier|string|array|null $table = 'artwork';

    /**
     * @param iterable<string, mixed> $data
     */
    public function create(iterable $data = []): ArtworkEntity
    {
        return new ArtworkEntity($data);
    }

    public function findOne(mixed $where = null, mixed $order = null, ?Select $select = null): ?ArtworkEntity
    {
        foreach ($this->find($where, $order, $select) as $entity) {
            if ($entity instanceof ArtworkEntity) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * @param list<int> $resourceIds
     * @return array<int, ArtworkEntity> keyed by resource_id
     */
    public function findByResourceIds(array $resourceIds): array
    {
        if ($resourceIds === []) {
            return [];
        }

        return $this->mapByResourceId(['resource_id' => $resourceIds]);
    }

    /**
     * @return array<int, ArtworkEntity> keyed by resource_id
     */
    public function findByExhibitionResourceId(int $exhibitionResourceId): array
    {
        return $this->mapByResourceId(['exhibition_resource_id' => $exhibitionResourceId]);
    }

    /**
     * @return array<int, ArtworkEntity> keyed by resource_id
     */
    public function findByArtistResourceId(int $artistResourceId): array
    {
        return $this->mapByResourceId(['artist_resource_id' => $artistResourceId]);
    }

    /**
     * Ongoing works from selected artists — available originals that are not
     * part of any exhibition.
     *
     * @return array<int, ArtworkEntity> keyed by resource_id
     */
    public function findAvailableOngoing(): array
    {
        return $this->mapByResourceId([
            'exhibition_resource_id' => null,
            'item_type'              => 'artwork',
            'status'                 => 'available',
        ]);
    }

    /**
     * @param array<string, mixed> $where
     * @return array<int, ArtworkEntity> keyed by resource_id
     */
    private function mapByResourceId(array $where): array
    {
        $map = [];

        foreach ($this->find($where) as $artwork) {
            if ($artwork instanceof ArtworkEntity && $artwork->resource_id !== null) {
                $map[(int) $artwork->resource_id] = $artwork;
            }
        }

        return $map;
    }
}
