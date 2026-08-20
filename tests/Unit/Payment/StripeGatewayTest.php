<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Payment;

use Contenir\Commerce\Exception\PaymentFailedException;
use Contenir\Commerce\Money\Money;
use Contenir\Commerce\Payment\CheckoutLineItem;
use Contenir\Commerce\Payment\CheckoutRequest;
use Contenir\Commerce\Payment\StripeGateway;
use Contenir\Commerce\Tests\TestAsset\FakeStripeHttpClient;
use Contenir\Commerce\Tests\TestAsset\FixedClock;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\CurlClient;
use Stripe\StripeClient;

#[Group('unit')]
final class StripeGatewayTest extends TestCase
{
    private FakeStripeHttpClient $http;
    private StripeGateway $gateway;

    protected function setUp(): void
    {
        $this->http = new FakeStripeHttpClient();
        ApiRequestor::setHttpClient($this->http);

        $this->gateway = new StripeGateway(
            new StripeClient('sk_test_fake'),
            new FixedClock(new DateTimeImmutable('2026-08-20T10:00:00+10:00'))
        );
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    public function testCreateCheckoutSessionSendsGstInclusiveAudLineItems(): void
    {
        $this->http->queueResponse([
            'id'             => 'cs_test_123',
            'object'         => 'checkout.session',
            'status'         => 'open',
            'url'            => 'https://checkout.stripe.com/c/pay/cs_test_123',
            'payment_intent' => null,
            'customer_email' => 'buyer@example.test',
        ]);

        $session = $this->gateway->createCheckoutSession(new CheckoutRequest(
            [new CheckoutLineItem('Coastal Dawn', Money::fromCents(185000), 1, 'Oil on canvas, 2026')],
            'https://example.test/thanks',
            'https://example.test/cart',
            'buyer@example.test',
            ['order_ref' => 'LR-2026-0001']
        ));

        $this->assertSame('cs_test_123', $session->id);
        $this->assertSame('open', $session->status);
        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_123', $session->url);
        $this->assertNull($session->paymentIntentId);

        $request = $this->http->requests[0];
        $this->assertSame('post', $request['method']);
        $this->assertStringContainsString('/v1/checkout/sessions', $request['url']);
        $this->assertSame('payment', $request['params']['mode']);
        $this->assertSame('https://example.test/thanks', $request['params']['success_url']);
        $this->assertSame('buyer@example.test', $request['params']['customer_email']);
        $this->assertSame(['order_ref' => 'LR-2026-0001'], $request['params']['metadata']);
        $this->assertSame([
            'quantity'   => 1,
            'price_data' => [
                'currency'     => 'aud',
                'unit_amount'  => 185000,
                'product_data' => [
                    'name'        => 'Coastal Dawn',
                    'description' => 'Oil on canvas, 2026',
                ],
            ],
        ], $request['params']['line_items'][0]);
    }

    public function testCheckoutSessionExpiryComesFromTheInjectedClock(): void
    {
        $this->http->queueResponse([
            'id'             => 'cs_test_123',
            'object'         => 'checkout.session',
            'status'         => 'open',
            'url'            => 'https://checkout.stripe.com/c/pay/cs_test_123',
            'payment_intent' => null,
            'customer_email' => null,
        ]);

        $this->gateway->createCheckoutSession(new CheckoutRequest(
            [new CheckoutLineItem('Tote bag', Money::fromCents(3500))],
            'https://example.test/thanks',
            'https://example.test/cart'
        ));

        $expectedExpiry = (new DateTimeImmutable('2026-08-20T10:30:00+10:00'))->getTimestamp();
        $this->assertSame($expectedExpiry, $this->http->requests[0]['params']['expires_at']);
    }

    public function testCreateCheckoutSessionWrapsStripeErrors(): void
    {
        $this->http->queueResponse(
            ['error' => ['message' => 'Invalid API key', 'type' => 'invalid_request_error']],
            401
        );

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('Unable to create Stripe checkout session');

        $this->gateway->createCheckoutSession(new CheckoutRequest(
            [new CheckoutLineItem('Tote bag', Money::fromCents(3500))],
            'https://example.test/thanks',
            'https://example.test/cart'
        ));
    }

    public function testRetrieveCheckoutSessionMapsCompletedPayment(): void
    {
        $this->http->queueResponse([
            'id'             => 'cs_test_123',
            'object'         => 'checkout.session',
            'status'         => 'complete',
            'url'            => null,
            'payment_intent' => 'pi_test_456',
            'customer_email' => 'buyer@example.test',
        ]);

        $session = $this->gateway->retrieveCheckoutSession('cs_test_123');

        $this->assertTrue($session->isComplete());
        $this->assertSame('pi_test_456', $session->paymentIntentId);
        $this->assertSame('buyer@example.test', $session->customerEmail);
    }

    public function testRetrieveCheckoutSessionWrapsStripeErrors(): void
    {
        $this->http->queueResponse(
            ['error' => ['message' => 'No such session', 'type' => 'invalid_request_error']],
            404
        );

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('Unable to retrieve Stripe checkout session');

        $this->gateway->retrieveCheckoutSession('cs_missing');
    }

    public function testFullRefundOmitsAmount(): void
    {
        $this->http->queueResponse([
            'id'     => 're_test_789',
            'object' => 'refund',
            'status' => 'succeeded',
        ]);

        $refund = $this->gateway->refund('pi_test_456');

        $this->assertSame('re_test_789', $refund->id);
        $this->assertSame('succeeded', $refund->status);

        $request = $this->http->requests[0];
        $this->assertStringContainsString('/v1/refunds', $request['url']);
        $this->assertSame('pi_test_456', $request['params']['payment_intent']);
        $this->assertArrayNotHasKey('amount', $request['params']);
    }

    public function testPartialRefundSendsAmountInCents(): void
    {
        $this->http->queueResponse([
            'id'     => 're_test_789',
            'object' => 'refund',
            'status' => 'succeeded',
        ]);

        $this->gateway->refund('pi_test_456', Money::fromCents(50000));

        $this->assertSame(50000, $this->http->requests[0]['params']['amount']);
    }

    public function testRefundWrapsStripeErrors(): void
    {
        $this->http->queueResponse(
            ['error' => ['message' => 'Charge already refunded', 'type' => 'invalid_request_error']],
            400
        );

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('Unable to refund Stripe payment');

        $this->gateway->refund('pi_test_456');
    }
}
