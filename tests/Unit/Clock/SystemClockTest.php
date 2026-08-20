<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Clock;

use Contenir\Commerce\Clock\SystemClock;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SystemClockTest extends TestCase
{
    public function testReturnsCurrentTime(): void
    {
        $before = new \DateTimeImmutable();
        $now    = (new SystemClock())->now();
        $after  = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual($after, $now);
    }
}
