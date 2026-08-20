<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Artwork;

use Contenir\Commerce\Artwork\ArtworkStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ArtworkStatusTest extends TestCase
{
    #[DataProvider('statusProvider')]
    public function testMapsDatabaseValueToLabel(string $value, ArtworkStatus $expected, string $label): void
    {
        $status = ArtworkStatus::from($value);

        $this->assertSame($expected, $status);
        $this->assertSame($label, $status->label());
    }

    /**
     * @return array<string, array{string, ArtworkStatus, string}>
     */
    public static function statusProvider(): array
    {
        return [
            'available' => ['available', ArtworkStatus::Available, 'Available'],
            'sold'      => ['sold', ArtworkStatus::Sold, 'Sold'],
        ];
    }
}
