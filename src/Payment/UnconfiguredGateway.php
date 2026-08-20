<?php

declare(strict_types=1);

namespace Contenir\Commerce\Payment;

use Contenir\Commerce\Exception\PaymentFailedException;
use Contenir\Commerce\Money\Money;

/**
 * Stands in when no Stripe secret key is configured, so the container and
 * everything depending on the gateway stay constructible. Any attempt to
 * move money fails with the same catchable exception a Stripe outage
 * would produce.
 */
final class UnconfiguredGateway implements PaymentGatewayInterface
{
    public function createCheckoutSession(CheckoutRequest $request): CheckoutSession
    {
        throw $this->notConfigured();
    }

    public function retrieveCheckoutSession(string $sessionId): CheckoutSession
    {
        throw $this->notConfigured();
    }

    public function refund(string $paymentIntentId, ?Money $amount = null): RefundResult
    {
        throw $this->notConfigured();
    }

    private function notConfigured(): PaymentFailedException
    {
        return new PaymentFailedException(
            'Stripe is not configured: set stripe.secret_key in local configuration'
        );
    }
}
