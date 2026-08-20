<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Model\Repository;

use Contenir\Commerce\Model\Entity\ArtistEnquiryEntity;
use Contenir\Commerce\Model\Entity\ArtistEnquiryFileEntity;
use Contenir\Commerce\Model\Entity\ArtworkEntity;
use Contenir\Commerce\Model\Entity\EmailLogEntity;
use Contenir\Commerce\Model\Entity\OrderEntity;
use Contenir\Commerce\Model\Entity\OrderItemEntity;
use Contenir\Commerce\Model\Repository\ArtistEnquiryFileRepository;
use Contenir\Commerce\Model\Repository\ArtistEnquiryRepository;
use Contenir\Commerce\Model\Repository\ArtworkRepository;
use Contenir\Commerce\Model\Repository\EmailLogRepository;
use Contenir\Commerce\Model\Repository\OrderItemRepository;
use Contenir\Commerce\Model\Repository\OrderRepository;
use Contenir\Db\Model\Repository\AbstractRepository;
use Contenir\Db\Model\Repository\RepositoryLookup;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RepositoryDefinitionTest extends TestCase
{
    /**
     * @param class-string<AbstractRepository> $repositoryClass
     * @param class-string $entityClass
     */
    #[DataProvider('repositoryProvider')]
    public function testCreatesItsEntityAgainstItsTable(
        string $repositoryClass,
        string $entityClass,
        string $table
    ): void {
        $repository = new $repositoryClass(
            $this->createStub(Adapter::class),
            new $entityClass(),
            $this->createStub(RepositoryLookup::class)
        );

        $this->assertInstanceOf($entityClass, $repository->create(['status' => 'any']));
        $this->assertSame($table, $repository->getTable());
    }

    /**
     * @return array<string, array{class-string<AbstractRepository>, class-string, string}>
     */
    public static function repositoryProvider(): array
    {
        return [
            'artwork'             => [ArtworkRepository::class, ArtworkEntity::class, 'artwork'],
            'order'               => [OrderRepository::class, OrderEntity::class, 'gallery_order'],
            'order item'          => [OrderItemRepository::class, OrderItemEntity::class, 'gallery_order_item'],
            'artist enquiry'      => [ArtistEnquiryRepository::class, ArtistEnquiryEntity::class, 'artist_enquiry'],
            'artist enquiry file' => [
                ArtistEnquiryFileRepository::class,
                ArtistEnquiryFileEntity::class,
                'artist_enquiry_file',
            ],
            'email log'           => [EmailLogRepository::class, EmailLogEntity::class, 'email_log'],
        ];
    }
}
