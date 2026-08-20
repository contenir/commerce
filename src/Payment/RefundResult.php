<?php

declare(strict_types=1);

namespace Contenir\Commerce\Payment;

final class RefundResult
{
    public function __construct(
        public readonly string $id,
        public readonly string $status
    ) {
    }
}
