<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Payment;

use Contenir\Commerce\Payment\CheckoutSession;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CheckoutSessionTest extends TestCase
{
    public function testCompleteStatusIsRecognised(): void
    {
        $this->assertTrue((new CheckoutSession('cs_1', 'complete'))->isComplete());
        $this->assertFalse((new CheckoutSession('cs_1', 'open'))->isComplete());
    }
}
