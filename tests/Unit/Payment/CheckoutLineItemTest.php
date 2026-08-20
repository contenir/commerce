<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Payment;

use Contenir\Commerce\Money\Money;
use Contenir\Commerce\Payment\CheckoutLineItem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CheckoutLineItemTest extends TestCase
{
    public function testDefaultsToSingleQuantityWithNoDescription(): void
    {
        $item = new CheckoutLineItem('Coastal Dawn', Money::fromCents(185000));

        $this->assertSame(1, $item->quantity);
        $this->assertNull($item->description);
    }

    public function testRejectsZeroQuantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Line item quantity must be at least 1, got 0');

        new CheckoutLineItem('Tote bag', Money::fromCents(3500), 0);
    }
}
