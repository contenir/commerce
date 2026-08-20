<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Repository;

use Contenir\Commerce\Model\Entity\EmailLogEntity;
use Contenir\Db\Model\Repository\AbstractRepository;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\TableIdentifier;

class EmailLogRepository extends AbstractRepository
{
    /** @var string|array<string, string>|TableIdentifier */
    protected TableIdentifier|string|array|null $table = 'email_log';

    /**
     * @param iterable<string, mixed> $data
     */
    public function create(iterable $data = []): EmailLogEntity
    {
        return new EmailLogEntity($data);
    }

    public function findOne(mixed $where = null, mixed $order = null, ?Select $select = null): ?EmailLogEntity
    {
        foreach ($this->find($where, $order, $select) as $entity) {
            if ($entity instanceof EmailLogEntity) {
                return $entity;
            }
        }

        return null;
    }
}
