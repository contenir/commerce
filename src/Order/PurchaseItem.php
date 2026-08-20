<?php

declare(strict_types=1);

namespace Contenir\Commerce\Order;

use Contenir\Commerce\Money\Money;

/**
 * A cart line at the moment of purchase. Title, artist and price are
 * snapshots — the order must stay accurate if the artwork later changes.
 */
final class PurchaseItem
{
    public function __construct(
        public readonly int $artworkId,
        public readonly string $title,
        public readonly Money $price,
        public readonly ?string $artistName = null
    ) {
    }
}
