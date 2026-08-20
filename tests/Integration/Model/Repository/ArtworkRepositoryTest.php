<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Integration\Model\Repository;

use Contenir\Commerce\Model\Entity\ArtworkEntity;
use Contenir\Commerce\Model\Repository\ArtworkRepository;
use Contenir\Db\Model\Repository\RepositoryLookup;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('repository')]
final class ArtworkRepositoryTest extends TestCase
{
    private ArtworkRepository $repository;

    protected function setUp(): void
    {
        $adapter = new Adapter([
            'driver'   => 'Pdo_Sqlite',
            'database' => ':memory:',
        ]);

        $adapter->query(
            'CREATE TABLE artwork ('
                . 'artwork_id INTEGER PRIMARY KEY AUTOINCREMENT, '
                . 'resource_id INTEGER NULL, '
                . 'artist_resource_id INTEGER NULL, '
                . 'exhibition_resource_id INTEGER NULL, '
                . 'item_type TEXT NOT NULL DEFAULT "artwork", '
                . 'price INTEGER NOT NULL DEFAULT 0, '
                . 'status TEXT NOT NULL DEFAULT "available", '
                . 'medium TEXT NULL, '
                . 'dimensions TEXT NULL, '
                . 'year TEXT NULL, '
                . 'edition_details TEXT NULL, '
                . 'external_sale_url TEXT NULL, '
                . 'created TEXT NULL, '
                . 'updated TEXT NULL'
            . ')',
            Adapter::QUERY_MODE_EXECUTE
        );

        $this->repository = new ArtworkRepository(
            $adapter,
            new ArtworkEntity(),
            $this->createStub(RepositoryLookup::class)
        );

        $fixtures = [
            [
                'resource_id'            => 101,
                'artist_resource_id'     => 11,
                'exhibition_resource_id' => 51,
                'status'                 => 'available',
            ],
            [
                'resource_id'            => 102,
                'artist_resource_id'     => 11,
                'exhibition_resource_id' => 51,
                'status'                 => 'sold',
            ],
            [
                'resource_id'            => 103,
                'artist_resource_id'     => 12,
                'exhibition_resource_id' => null,
                'status'                 => 'available',
            ],
            [
                'resource_id'            => 104,
                'artist_resource_id'     => 12,
                'exhibition_resource_id' => null,
                'status'                 => 'sold',
            ],
            [
                'resource_id'            => 105,
                'artist_resource_id'     => null,
                'exhibition_resource_id' => null,
                'status'                 => 'available',
                'item_type'              => 'retail',
            ],
        ];

        foreach ($fixtures as $fixture) {
            $this->repository->save($this->repository->create($fixture + ['price' => 100000]));
        }
    }

    public function testFindsArtworksKeyedByResourceId(): void
    {
        $map = $this->repository->findByResourceIds([101, 103, 999]);

        $this->assertSame([101, 103], array_keys($map));
        $this->assertSame('available', $map[101]->status);
        $this->assertSame([], $this->repository->findByResourceIds([]));
    }

    public function testFindsExhibitionWorksIncludingSoldOnes(): void
    {
        $map = $this->repository->findByExhibitionResourceId(51);

        $this->assertSame([101, 102], array_keys($map));
        $this->assertSame('sold', $map[102]->status);
    }

    public function testFindsWorksByArtist(): void
    {
        $this->assertSame([103, 104], array_keys($this->repository->findByArtistResourceId(12)));
    }

    public function testAvailableOngoingExcludesExhibitedSoldAndRetailWorks(): void
    {
        $this->assertSame([103], array_keys($this->repository->findAvailableOngoing()));
    }
}
