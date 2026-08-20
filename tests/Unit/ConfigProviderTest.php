<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit;

use Contenir\Commerce\Clock\SystemClock;
use Contenir\Commerce\ConfigProvider;
use Contenir\Commerce\Model\Entity\OrderEntity;
use Contenir\Commerce\Model\Repository\OrderRepository;
use Contenir\Commerce\Order\OrderManager;
use Contenir\Commerce\Payment\PaymentGatewayInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    public function testRegistersEntitiesRepositoriesGatewayAndOrderManager(): void
    {
        $config    = (new ConfigProvider())()['service_manager'];
        $factories = $config['factories'];

        $this->assertArrayHasKey(OrderEntity::class, $factories);
        $this->assertArrayHasKey(OrderRepository::class, $factories);
        $this->assertArrayHasKey(PaymentGatewayInterface::class, $factories);
        $this->assertArrayHasKey(OrderManager::class, $factories);
        $this->assertSame(SystemClock::class, $config['aliases'][ClockInterface::class]);
        $this->assertCount(15, $factories);
    }
}
