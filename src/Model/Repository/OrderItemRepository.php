<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Repository;

use Contenir\Commerce\Model\Entity\OrderItemEntity;
use Contenir\Db\Model\Repository\AbstractRepository;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\TableIdentifier;

class OrderItemRepository extends AbstractRepository
{
    /** @var string|array<string, string>|TableIdentifier */
    protected TableIdentifier|string|array|null $table = 'gallery_order_item';

    /**
     * @param iterable<string, mixed> $data
     */
    public function create(iterable $data = []): OrderItemEntity
    {
        return new OrderItemEntity($data);
    }

    public function findOne(mixed $where = null, mixed $order = null, ?Select $select = null): ?OrderItemEntity
    {
        foreach ($this->find($where, $order, $select) as $entity) {
            if ($entity instanceof OrderItemEntity) {
                return $entity;
            }
        }

        return null;
    }
}
