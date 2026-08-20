<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Entity;

use Contenir\Db\Model\Entity\AbstractEntity;

/**
 * Title, artist name and price are copied from the artwork at the moment of
 * sale so the order remains accurate if the artwork later changes.
 *
 * @property int $order_item_id
 * @property int $order_id
 * @property ?int $artwork_id
 * @property string $title
 * @property ?string $artist_name
 * @property int $price
 * @property ?string $created
 */
class OrderItemEntity extends AbstractEntity
{
    /** @var list<string> */
    protected array $primaryKeys = [
        'order_item_id',
    ];

    /** @var list<string> */
    protected array $columns = [
        'order_item_id',
        'order_id',
        'artwork_id',
        'title',
        'artist_name',
        'price',
        'created',
    ];
}
