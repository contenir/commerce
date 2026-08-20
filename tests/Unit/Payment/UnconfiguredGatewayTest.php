<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Payment;

use Contenir\Commerce\Exception\PaymentFailedException;
use Contenir\Commerce\Money\Money;
use Contenir\Commerce\Payment\CheckoutLineItem;
use Contenir\Commerce\Payment\CheckoutRequest;
use Contenir\Commerce\Payment\UnconfiguredGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class UnconfiguredGatewayTest extends TestCase
{
    #[DataProvider('operationProvider')]
    public function testEveryPaymentOperationFailsCatchably(callable $operation): void
    {
        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('Stripe is not configured');

        $operation(new UnconfiguredGateway());
    }

    /**
     * @return array<string, array{callable}>
     */
    public static function operationProvider(): array
    {
        return [
            'create checkout session' => [
                static fn (UnconfiguredGateway $gateway) => $gateway->createCheckoutSession(new CheckoutRequest(
                    [new CheckoutLineItem('Tote bag', Money::fromCents(3500))],
                    'https://example.test/thanks',
                    'https://example.test/cart'
                )),
            ],
            'retrieve session'        => [
                static fn (UnconfiguredGateway $gateway) => $gateway->retrieveCheckoutSession('cs_x'),
            ],
            'refund'                  => [
                static fn (UnconfiguredGateway $gateway) => $gateway->refund('pi_x'),
            ],
        ];
    }
}
