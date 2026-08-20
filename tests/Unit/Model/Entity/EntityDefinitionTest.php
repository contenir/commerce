<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Model\Entity;

use Contenir\Commerce\Model\Entity\ArtistEnquiryEntity;
use Contenir\Commerce\Model\Entity\ArtistEnquiryFileEntity;
use Contenir\Commerce\Model\Entity\ArtworkEntity;
use Contenir\Commerce\Model\Entity\EmailLogEntity;
use Contenir\Commerce\Model\Entity\OrderEntity;
use Contenir\Commerce\Model\Entity\OrderItemEntity;
use Contenir\Db\Model\Entity\AbstractEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class EntityDefinitionTest extends TestCase
{
    /**
     * @param class-string<AbstractEntity> $entityClass
     * @param array<string, int|string> $sample
     */
    #[DataProvider('entityProvider')]
    public function testDeclaresPrimaryKeyAndRoundTripsItsColumns(
        string $entityClass,
        string $primaryKey,
        array $sample
    ): void {
        $entity = new $entityClass($sample);

        $this->assertSame([$primaryKey => $sample[$primaryKey]], $entity->getPrimaryKeys());

        $copy = $entity->getArrayCopy();
        foreach ($sample as $column => $value) {
            $this->assertSame($value, $copy[$column]);
        }
    }

    /**
     * @return array<string, array{class-string<AbstractEntity>, string, array<string, int|string>}>
     */
    public static function entityProvider(): array
    {
        return [
            'artwork' => [
                ArtworkEntity::class,
                'artwork_id',
                [
                    'artwork_id' => 1,
                    'item_type'  => 'artwork',
                    'price'      => 185000,
                    'status'     => 'available',
                    'medium'     => 'Oil on canvas',
                ],
            ],
            'order' => [
                OrderEntity::class,
                'order_id',
                [
                    'order_id'   => 1,
                    'order_ref'  => 'LR-2026-0001',
                    'status'     => 'pending',
                    'total'      => 188500,
                    'gst_amount' => 17136,
                ],
            ],
            'order item' => [
                OrderItemEntity::class,
                'order_item_id',
                [
                    'order_item_id' => 1,
                    'order_id'      => 1,
                    'title'         => 'Coastal Dawn',
                    'artist_name'   => 'June Example',
                    'price'         => 185000,
                ],
            ],
            'artist enquiry' => [
                ArtistEnquiryEntity::class,
                'artist_enquiry_id',
                [
                    'artist_enquiry_id' => 1,
                    'name'              => 'June Example',
                    'email'             => 'june@example.test',
                    'status'            => 'new',
                ],
            ],
            'artist enquiry file' => [
                ArtistEnquiryFileEntity::class,
                'artist_enquiry_file_id',
                [
                    'artist_enquiry_file_id' => 1,
                    'artist_enquiry_id'      => 1,
                    'filename'               => 'sample.jpg',
                    'path'                   => 'enquiry/1/sample.jpg',
                ],
            ],
            'email log' => [
                EmailLogEntity::class,
                'email_log_id',
                [
                    'email_log_id' => 1,
                    'recipient'    => 'buyer@example.test',
                    'subject'      => 'Your Lon Retreat order',
                    'status'       => 'sent',
                ],
            ],
        ];
    }
}
