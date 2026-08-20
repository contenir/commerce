<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Repository;

use Contenir\Commerce\Model\Entity\ArtistEnquiryEntity;
use Contenir\Db\Model\Repository\AbstractRepository;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\TableIdentifier;

class ArtistEnquiryRepository extends AbstractRepository
{
    /** @var string|array<string, string>|TableIdentifier */
    protected TableIdentifier|string|array|null $table = 'artist_enquiry';

    /**
     * @param iterable<string, mixed> $data
     */
    public function create(iterable $data = []): ArtistEnquiryEntity
    {
        return new ArtistEnquiryEntity($data);
    }

    public function findOne(mixed $where = null, mixed $order = null, ?Select $select = null): ?ArtistEnquiryEntity
    {
        foreach ($this->find($where, $order, $select) as $entity) {
            if ($entity instanceof ArtistEnquiryEntity) {
                return $entity;
            }
        }

        return null;
    }
}
