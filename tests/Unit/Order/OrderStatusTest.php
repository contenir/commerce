<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Order;

use Contenir\Commerce\Exception\InvalidTransitionException;
use Contenir\Commerce\Order\OrderStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function in_array;
use function sprintf;

#[Group('unit')]
final class OrderStatusTest extends TestCase
{
    /**
     * Every allowed edge in the lifecycle, exhaustively.
     */
    private const ALLOWED = [
        ['pending', 'paid'],
        ['pending', 'cancelled'],
        ['paid', 'awaiting_pickup'],
        ['paid', 'refunded'],
        ['paid', 'cancelled'],
        ['awaiting_pickup', 'collected'],
        ['awaiting_pickup', 'refunded'],
        ['awaiting_pickup', 'cancelled'],
        ['collected', 'refunded'],
    ];

    #[DataProvider('transitionMatrixProvider')]
    public function testTransitionMatrixIsEnforcedExhaustively(
        OrderStatus $from,
        OrderStatus $to,
        bool $allowed
    ): void {
        $this->assertSame($allowed, $from->canTransitionTo($to));
    }

    /**
     * @return array<string, array{OrderStatus, OrderStatus, bool}>
     */
    public static function transitionMatrixProvider(): array
    {
        $cases = [];

        foreach (OrderStatus::cases() as $from) {
            foreach (OrderStatus::cases() as $to) {
                $allowed = in_array([$from->value, $to->value], self::ALLOWED, true);

                $cases[sprintf('%s to %s', $from->value, $to->value)] = [$from, $to, $allowed];
            }
        }

        return $cases;
    }

    public function testTransitionToReturnsTheNextStatus(): void
    {
        $this->assertSame(OrderStatus::Paid, OrderStatus::Pending->transitionTo(OrderStatus::Paid));
    }

    public function testInvalidTransitionThrowsWithBothStatesNamed(): void
    {
        $this->expectException(InvalidTransitionException::class);
        $this->expectExceptionMessage('Order cannot move from "collected" to "pending"');

        OrderStatus::Collected->transitionTo(OrderStatus::Pending);
    }

    #[DataProvider('finalityProvider')]
    public function testOnlyRefundedAndCancelledAreFinal(OrderStatus $status, bool $isFinal): void
    {
        $this->assertSame($isFinal, $status->isFinal());
    }

    /**
     * @return array<string, array{OrderStatus, bool}>
     */
    public static function finalityProvider(): array
    {
        return [
            'pending'         => [OrderStatus::Pending, false],
            'paid'            => [OrderStatus::Paid, false],
            'awaiting pickup' => [OrderStatus::AwaitingPickup, false],
            'collected'       => [OrderStatus::Collected, false],
            'refunded'        => [OrderStatus::Refunded, true],
            'cancelled'       => [OrderStatus::Cancelled, true],
        ];
    }

    #[DataProvider('labelProvider')]
    public function testProvidesHumanReadableLabels(OrderStatus $status, string $label): void
    {
        $this->assertSame($label, $status->label());
    }

    /**
     * @return array<string, array{OrderStatus, string}>
     */
    public static function labelProvider(): array
    {
        return [
            'pending'         => [OrderStatus::Pending, 'Pending'],
            'paid'            => [OrderStatus::Paid, 'Paid'],
            'awaiting pickup' => [OrderStatus::AwaitingPickup, 'Awaiting pickup'],
            'collected'       => [OrderStatus::Collected, 'Collected'],
            'refunded'        => [OrderStatus::Refunded, 'Refunded'],
            'cancelled'       => [OrderStatus::Cancelled, 'Cancelled'],
        ];
    }
}
