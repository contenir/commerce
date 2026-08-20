<?php

declare(strict_types=1);

namespace Contenir\Commerce\Payment;

final class CheckoutSession
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly ?string $url = null,
        public readonly ?string $paymentIntentId = null,
        public readonly ?string $customerEmail = null
    ) {
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }
}
