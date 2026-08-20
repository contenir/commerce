<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Entity;

use Contenir\Commerce\Model\Repository\OrderItemRepository;
use Contenir\Db\Model\Entity\AbstractEntity;

/**
 * @property int $order_id
 * @property string $order_ref
 * @property ?string $customer_name
 * @property ?string $customer_email
 * @property ?string $customer_phone
 * @property string $status
 * @property int $total
 * @property int $gst_amount
 * @property ?string $stripe_checkout_session_id
 * @property ?string $stripe_payment_intent_id
 * @property ?string $customer_notes
 * @property ?string $staff_notes
 * @property ?string $paid_at
 * @property ?string $collected_at
 * @property ?string $refunded_at
 * @property ?string $cancelled_at
 * @property ?string $created
 * @property ?string $updated
 * @property array<int, OrderItemEntity> $items
 */
class OrderEntity extends AbstractEntity
{
    /** @var list<string> */
    protected array $primaryKeys = [
        'order_id',
    ];

    /** @var list<string> */
    protected array $columns = [
        'order_id',
        'order_ref',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'total',
        'gst_amount',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'customer_notes',
        'staff_notes',
        'paid_at',
        'collected_at',
        'refunded_at',
        'cancelled_at',
        'created',
        'updated',
    ];

    /** @var array<string, array<string, mixed>> */
    protected array $relations = [
        'items' => [
            'type'   => AbstractEntity::RELATION_MANY,
            'column' => ['order_id'],
            'table'  => [
                'class'  => OrderItemRepository::class,
                'column' => ['order_id'],
            ],
        ],
    ];
}
