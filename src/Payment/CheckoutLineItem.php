<?php

declare(strict_types=1);

namespace Contenir\Commerce\Payment;

use Contenir\Commerce\Money\Money;
use InvalidArgumentException;

use function sprintf;

final class CheckoutLineItem
{
    public function __construct(
        public readonly string $name,
        public readonly Money $price,
        public readonly int $quantity = 1,
        public readonly ?string $description = null
    ) {
        if ($quantity < 1) {
            throw new InvalidArgumentException(
                sprintf('Line item quantity must be at least 1, got %d', $quantity)
            );
        }
    }
}
