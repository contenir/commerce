<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Entity;

use Contenir\Db\Model\Entity\AbstractEntity;

/**
 * @property int $artwork_id
 * @property ?int $resource_id
 * @property ?int $artist_resource_id
 * @property ?int $exhibition_resource_id
 * @property string $item_type
 * @property int $price
 * @property string $status
 * @property ?string $medium
 * @property ?string $dimensions
 * @property ?string $year
 * @property ?string $edition_details
 * @property ?string $external_sale_url
 * @property ?string $created
 * @property ?string $updated
 */
class ArtworkEntity extends AbstractEntity
{
    /** @var list<string> */
    protected array $primaryKeys = [
        'artwork_id',
    ];

    /** @var list<string> */
    protected array $columns = [
        'artwork_id',
        'resource_id',
        'artist_resource_id',
        'exhibition_resource_id',
        'item_type',
        'price',
        'status',
        'medium',
        'dimensions',
        'year',
        'edition_details',
        'external_sale_url',
        'created',
        'updated',
    ];
}
