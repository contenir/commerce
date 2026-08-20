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
}
