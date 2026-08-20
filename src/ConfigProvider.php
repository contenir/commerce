<?php

declare(strict_types=1);

namespace Contenir\Commerce;

use Contenir\Commerce\Clock\SystemClock;
use Contenir\Commerce\Order\Factory\OrderManagerFactory;
use Contenir\Commerce\Order\OrderManager;
use Contenir\Commerce\Payment\Factory\StripeGatewayFactory;
use Contenir\Commerce\Payment\PaymentGatewayInterface;
use Contenir\Db\Model\Repository\Factory\RepositoryFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Psr\Clock\ClockInterface;

final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'service_manager' => $this->getDependencyConfig(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDependencyConfig(): array
    {
        return [
            'aliases'   => [
                ClockInterface::class => SystemClock::class,
            ],
            'factories' => [
                SystemClock::class  => InvokableFactory::class,
                OrderManager::class => OrderManagerFactory::class,
                Model\Entity\ArtworkEntity::class           => InvokableFactory::class,
                Model\Entity\OrderEntity::class             => InvokableFactory::class,
                Model\Entity\OrderItemEntity::class         => InvokableFactory::class,
                Model\Entity\ArtistEnquiryEntity::class     => InvokableFactory::class,
                Model\Entity\ArtistEnquiryFileEntity::class => InvokableFactory::class,
                Model\Entity\EmailLogEntity::class          => InvokableFactory::class,
                Model\Repository\ArtworkRepository::class           => RepositoryFactory::class,
                Model\Repository\OrderRepository::class             => RepositoryFactory::class,
                Model\Repository\OrderItemRepository::class         => RepositoryFactory::class,
                Model\Repository\ArtistEnquiryRepository::class     => RepositoryFactory::class,
                Model\Repository\ArtistEnquiryFileRepository::class => RepositoryFactory::class,
                Model\Repository\EmailLogRepository::class          => RepositoryFactory::class,
                PaymentGatewayInterface::class => StripeGatewayFactory::class,
            ],
        ];
    }
}
