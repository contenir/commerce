<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Repository;

use Contenir\Commerce\Model\Entity\OrderEntity;
use Contenir\Db\Model\Repository\AbstractRepository;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\TableIdentifier;

class OrderRepository extends AbstractRepository
{
    /** @var string|array<string, string>|TableIdentifier */
    protected TableIdentifier|string|array|null $table = 'gallery_order';

    /**
     * @param iterable<string, mixed> $data
     */
    public function create(iterable $data = []): OrderEntity
    {
        return new OrderEntity($data);
    }

    public function findOne(mixed $where = null, mixed $order = null, ?Select $select = null): ?OrderEntity
    {
        foreach ($this->find($where, $order, $select) as $entity) {
            if ($entity instanceof OrderEntity) {
                return $entity;
            }
        }

        return null;
    }
}
