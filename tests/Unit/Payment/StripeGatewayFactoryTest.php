<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Payment;

use Contenir\Commerce\Payment\Factory\StripeGatewayFactory;
use Contenir\Commerce\Payment\StripeGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

#[Group('unit')]
final class StripeGatewayFactoryTest extends TestCase
{
    public function testBuildsGatewayFromConfiguredSecretKey(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn([
            'stripe' => ['secret_key' => 'sk_test_fake'],
        ]);

        $this->assertInstanceOf(StripeGateway::class, (new StripeGatewayFactory())($container));
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('missingConfigProvider')]
    public function testRefusesToBuildWithoutSecretKey(array $config): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stripe is not configured');

        (new StripeGatewayFactory())($container);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function missingConfigProvider(): array
    {
        return [
            'no stripe block' => [[]],
            'no secret key'   => [['stripe' => []]],
            'empty secret'    => [['stripe' => ['secret_key' => '']]],
        ];
    }
}
