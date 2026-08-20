<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit;

use Contenir\Commerce\ConfigProvider;
use Contenir\Commerce\Module;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ModuleTest extends TestCase
{
    public function testExposesTheConfigProviderConfigToLaminasMvc(): void
    {
        $this->assertSame((new ConfigProvider())(), (new Module())->getConfig());
    }
}
