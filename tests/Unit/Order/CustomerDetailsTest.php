<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Order;

use Contenir\Commerce\Order\CustomerDetails;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CustomerDetailsTest extends TestCase
{
    public function testPhoneAndNotesAreOptional(): void
    {
        $customer = new CustomerDetails('Avery Buyer', 'avery@example.test');

        $this->assertNull($customer->phone);
        $this->assertNull($customer->notes);
    }

    #[DataProvider('missingFieldProvider')]
    public function testRequiresNameAndEmail(string $name, string $email): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer name and email are required');

        new CustomerDetails($name, $email);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function missingFieldProvider(): array
    {
        return [
            'no name'  => ['', 'avery@example.test'],
            'no email' => ['Avery Buyer', ''],
        ];
    }
}
