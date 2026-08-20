<?php

declare(strict_types=1);

namespace Contenir\Commerce\Payment;

use InvalidArgumentException;

final class CheckoutRequest
{
    /**
     * @param list<CheckoutLineItem> $lineItems
     * @param array<string, string> $metadata
     */
    public function __construct(
        public readonly array $lineItems,
        public readonly string $successUrl,
        public readonly string $cancelUrl,
        public readonly ?string $customerEmail = null,
        public readonly array $metadata = [],
        public readonly int $expiresAfterMinutes = 30
    ) {
        if ($lineItems === []) {
            throw new InvalidArgumentException('Checkout requires at least one line item');
        }

        if ($expiresAfterMinutes < 30) {
            throw new InvalidArgumentException('Stripe checkout sessions cannot expire in under 30 minutes');
        }
    }
}
