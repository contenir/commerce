<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Artwork;

use Contenir\Commerce\Artwork\ItemType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ItemTypeTest extends TestCase
{
    #[DataProvider('typeProvider')]
    public function testMapsDatabaseValueToLabel(string $value, ItemType $expected, string $label): void
    {
        $type = ItemType::from($value);

        $this->assertSame($expected, $type);
        $this->assertSame($label, $type->label());
    }

    /**
     * @return array<string, array{string, ItemType, string}>
     */
    public static function typeProvider(): array
    {
        return [
            'artwork' => ['artwork', ItemType::Artwork, 'Artwork'],
            'retail'  => ['retail', ItemType::Retail, 'Retail product'],
        ];
    }
}
