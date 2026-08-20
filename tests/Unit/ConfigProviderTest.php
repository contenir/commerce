<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit;

use Contenir\Commerce\ConfigProvider;
use Contenir\Commerce\Model\Entity\OrderEntity;
use Contenir\Commerce\Model\Repository\OrderRepository;
use Contenir\Commerce\Payment\PaymentGatewayInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    public function testRegistersEntitiesRepositoriesAndPaymentGateway(): void
    {
        $factories = (new ConfigProvider())()['service_manager']['factories'];

        $this->assertArrayHasKey(OrderEntity::class, $factories);
        $this->assertArrayHasKey(OrderRepository::class, $factories);
        $this->assertArrayHasKey(PaymentGatewayInterface::class, $factories);
        $this->assertCount(13, $factories);
    }
}
