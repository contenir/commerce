<?php

declare(strict_types=1);

namespace Contenir\Commerce\Money;

use InvalidArgumentException;

use function number_format;
use function round;
use function sprintf;

/**
 * A GST-inclusive AUD amount held as integer cents.
 *
 * All artwork and retail prices at Lon Retreat are GST-inclusive, so the GST
 * component of any amount is one eleventh, rounded to the nearest cent.
 */
final class Money
{
    public function __construct(public readonly int $amount)
    {
        if ($amount < 0) {
            throw new InvalidArgumentException(
                sprintf('Money cannot be negative, got %d cents', $amount)
            );
        }
    }

    public static function fromCents(int $amount): self
    {
        return new self($amount);
    }

    public function add(self $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function subtract(self $other): self
    {
        return new self($this->amount - $other->amount);
    }

    public function multiply(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException(
                sprintf('Quantity cannot be negative, got %d', $quantity)
            );
        }

        return new self($this->amount * $quantity);
    }

    public function gstComponent(): self
    {
        return new self((int) round($this->amount / 11));
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function format(): string
    {
        return '$' . number_format($this->amount / 100, 2);
    }
}
