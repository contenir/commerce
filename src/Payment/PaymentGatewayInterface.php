<?php

declare(strict_types=1);

namespace Contenir\Commerce\Payment;

use Contenir\Commerce\Exception\PaymentFailedException;
use Contenir\Commerce\Money\Money;

interface PaymentGatewayInterface
{
    /**
     * @throws PaymentFailedException
     */
    public function createCheckoutSession(CheckoutRequest $request): CheckoutSession;

    /**
     * @throws PaymentFailedException
     */
    public function retrieveCheckoutSession(string $sessionId): CheckoutSession;

    /**
     * A null amount refunds the full payment.
     *
     * @throws PaymentFailedException
     */
    public function refund(string $paymentIntentId, ?Money $amount = null): RefundResult;
}
