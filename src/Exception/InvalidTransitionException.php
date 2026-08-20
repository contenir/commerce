<?php

declare(strict_types=1);

namespace Contenir\Commerce\Exception;

use Contenir\Commerce\Order\OrderStatus;
use DomainException;

use function sprintf;

final class InvalidTransitionException extends DomainException
{
    public static function between(OrderStatus $from, OrderStatus $to): self
    {
        return new self(sprintf(
            'Order cannot move from "%s" to "%s"',
            $from->value,
            $to->value
        ));
    }
}
