<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Exception;

use Contenir\Commerce\Exception\ArtworkUnavailableException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ArtworkUnavailableExceptionTest extends TestCase
{
    public function testCarriesTheUnavailableTitles(): void
    {
        $exception = ArtworkUnavailableException::forTitles(['Rip Tide', 'Moonah Study']);

        $this->assertSame(['Rip Tide', 'Moonah Study'], $exception->getTitles());
        $this->assertSame('No longer available: Rip Tide, Moonah Study', $exception->getMessage());
    }
}
