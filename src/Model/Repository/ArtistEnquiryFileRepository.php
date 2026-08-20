<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Repository;

use Contenir\Commerce\Model\Entity\ArtistEnquiryFileEntity;
use Contenir\Db\Model\Repository\AbstractRepository;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\TableIdentifier;

class ArtistEnquiryFileRepository extends AbstractRepository
{
    /** @var string|array<string, string>|TableIdentifier */
    protected TableIdentifier|string|array|null $table = 'artist_enquiry_file';

    /**
     * @param iterable<string, mixed> $data
     */
    public function create(iterable $data = []): ArtistEnquiryFileEntity
    {
        return new ArtistEnquiryFileEntity($data);
    }

    public function findOne(mixed $where = null, mixed $order = null, ?Select $select = null): ?ArtistEnquiryFileEntity
    {
        foreach ($this->find($where, $order, $select) as $entity) {
            if ($entity instanceof ArtistEnquiryFileEntity) {
                return $entity;
            }
        }

        return null;
    }
}
