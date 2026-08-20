<?php

declare(strict_types=1);

namespace Contenir\Commerce\Payment\Factory;

use Contenir\Commerce\Clock\SystemClock;
use Contenir\Commerce\Payment\PaymentGatewayInterface;
use Contenir\Commerce\Payment\StripeGateway;
use Contenir\Commerce\Payment\UnconfiguredGateway;
use Psr\Container\ContainerInterface;
use Stripe\StripeClient;

final class StripeGatewayFactory
{
    public function __invoke(ContainerInterface $container): PaymentGatewayInterface
    {
        $config    = $container->get('config');
        $secretKey = $config['stripe']['secret_key'] ?? null;

        if ($secretKey === null || $secretKey === '') {
            return new UnconfiguredGateway();
        }

        return new StripeGateway(new StripeClient($secretKey), new SystemClock());
    }
}
