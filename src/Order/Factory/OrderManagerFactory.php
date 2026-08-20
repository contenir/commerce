<?php

declare(strict_types=1);

namespace Contenir\Commerce\Order\Factory;

use Contenir\Commerce\Model\Repository\ArtworkRepository;
use Contenir\Commerce\Model\Repository\OrderItemRepository;
use Contenir\Commerce\Model\Repository\OrderRepository;
use Contenir\Commerce\Order\OrderManager;
use Contenir\Commerce\Payment\PaymentGatewayInterface;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;

final class OrderManagerFactory
{
    public function __invoke(ContainerInterface $container): OrderManager
    {
        return new OrderManager(
            $container->get(OrderRepository::class),
            $container->get(OrderItemRepository::class),
            $container->get(ArtworkRepository::class),
            $container->get(PaymentGatewayInterface::class),
            $container->get(ClockInterface::class)
        );
    }
}
