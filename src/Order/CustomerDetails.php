<?php

declare(strict_types=1);

namespace Contenir\Commerce\Order;

use InvalidArgumentException;

final class CustomerDetails
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
        public readonly ?string $notes = null
    ) {
        if ($name === '' || $email === '') {
            throw new InvalidArgumentException('Customer name and email are required');
        }
    }
}
