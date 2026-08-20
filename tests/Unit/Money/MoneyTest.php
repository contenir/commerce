<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Money;

use Contenir\Commerce\Money\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class MoneyTest extends TestCase
{
    public function testHoldsAmountInCents(): void
    {
        $this->assertSame(185000, Money::fromCents(185000)->amount);
    }

    public function testRejectsNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Money cannot be negative, got -1 cents');

        new Money(-1);
    }

    public function testAddsAmounts(): void
    {
        $sum = Money::fromCents(1000)->add(Money::fromCents(250));

        $this->assertSame(1250, $sum->amount);
    }

    public function testSubtractsAmounts(): void
    {
        $difference = Money::fromCents(1000)->subtract(Money::fromCents(250));

        $this->assertSame(750, $difference->amount);
    }

    public function testSubtractionBelowZeroIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(100)->subtract(Money::fromCents(200));
    }

    public function testMultipliesByQuantity(): void
    {
        $this->assertSame(3000, Money::fromCents(1000)->multiply(3)->amount);
    }

    public function testMultiplyByZeroGivesZero(): void
    {
        $this->assertTrue(Money::fromCents(1000)->multiply(0)->isZero());
    }

    public function testMultiplyByNegativeQuantityIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity cannot be negative, got -2');

        Money::fromCents(1000)->multiply(-2);
    }

    #[DataProvider('gstProvider')]
    public function testGstComponentIsOneEleventhRoundedToNearestCent(int $amount, int $expectedGst): void
    {
        $this->assertSame($expectedGst, Money::fromCents($amount)->gstComponent()->amount);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function gstProvider(): array
    {
        return [
            'even eleventh'  => [11000, 1000],
            'rounds down'    => [100, 9],
            'rounds up'      => [17, 2],
            'rounds to zero' => [5, 0],
            'zero'           => [0, 0],
        ];
    }

    public function testEqualityComparesAmounts(): void
    {
        $this->assertTrue(Money::fromCents(500)->equals(Money::fromCents(500)));
        $this->assertFalse(Money::fromCents(500)->equals(Money::fromCents(501)));
    }

    #[DataProvider('formatProvider')]
    public function testFormatsAsAustralianDollars(int $amount, string $expected): void
    {
        $this->assertSame($expected, Money::fromCents($amount)->format());
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function formatProvider(): array
    {
        return [
            'thousands with separator' => [185000, '$1,850.00'],
            'cents preserved'          => [1999, '$19.99'],
            'zero'                     => [0, '$0.00'],
        ];
    }
}
