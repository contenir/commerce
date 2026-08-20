<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Payment;

use Contenir\Commerce\Money\Money;
use Contenir\Commerce\Payment\CheckoutLineItem;
use Contenir\Commerce\Payment\CheckoutRequest;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CheckoutRequestTest extends TestCase
{
    public function testRejectsEmptyLineItems(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Checkout requires at least one line item');

        new CheckoutRequest([], 'https://example.test/thanks', 'https://example.test/cart');
    }

    public function testRejectsExpiryBelowStripeMinimum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stripe checkout sessions cannot expire in under 30 minutes');

        new CheckoutRequest(
            [new CheckoutLineItem('Coastal Dawn', Money::fromCents(185000))],
            'https://example.test/thanks',
            'https://example.test/cart',
            null,
            [],
            29
        );
    }
}
