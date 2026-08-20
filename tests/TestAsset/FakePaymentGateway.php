<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\TestAsset;

use Contenir\Commerce\Exception\PaymentFailedException;
use Contenir\Commerce\Money\Money;
use Contenir\Commerce\Payment\CheckoutRequest;
use Contenir\Commerce\Payment\CheckoutSession;
use Contenir\Commerce\Payment\PaymentGatewayInterface;
use Contenir\Commerce\Payment\RefundResult;

use function count;
use function sprintf;

/**
 * Scriptable in-memory payment gateway for exercising OrderManager flows.
 */
final class FakePaymentGateway implements PaymentGatewayInterface
{
    public ?CheckoutRequest $lastCheckoutRequest = null;

    /** @var list<array{paymentIntentId: string, amount: ?int}> */
    public array $refunds = [];

    /** @var array<string, CheckoutSession> */
    private array $sessions = [];

    private int $sessionCount = 0;

    public function createCheckoutSession(CheckoutRequest $request): CheckoutSession
    {
        $this->lastCheckoutRequest = $request;
        $this->sessionCount++;

        $session = new CheckoutSession(
            sprintf('cs_fake_%d', $this->sessionCount),
            'open',
            sprintf('https://checkout.stripe.test/pay/cs_fake_%d', $this->sessionCount),
            null,
            $request->customerEmail
        );

        $this->sessions[$session->id] = $session;

        return $session;
    }

    public function retrieveCheckoutSession(string $sessionId): CheckoutSession
    {
        if (! isset($this->sessions[$sessionId])) {
            throw new PaymentFailedException(sprintf('Unknown session "%s"', $sessionId));
        }

        return $this->sessions[$sessionId];
    }

    public function completeSession(string $sessionId, string $paymentIntentId): void
    {
        $existing = $this->sessions[$sessionId] ?? null;

        $this->sessions[$sessionId] = new CheckoutSession(
            $sessionId,
            'complete',
            null,
            $paymentIntentId,
            $existing?->customerEmail
        );
    }

    public function refund(string $paymentIntentId, ?Money $amount = null): RefundResult
    {
        $this->refunds[] = [
            'paymentIntentId' => $paymentIntentId,
            'amount'          => $amount?->amount,
        ];

        return new RefundResult(sprintf('re_fake_%d', count($this->refunds)), 'succeeded');
    }
}
