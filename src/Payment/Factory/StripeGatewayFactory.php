<?php

declare(strict_types=1);

namespace Contenir\Commerce\Payment\Factory;

use Contenir\Commerce\Clock\SystemClock;
use Contenir\Commerce\Payment\StripeGateway;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Stripe\StripeClient;

final class StripeGatewayFactory
{
    public function __invoke(ContainerInterface $container): StripeGateway
    {
        $config    = $container->get('config');
        $secretKey = $config['stripe']['secret_key'] ?? null;

        if ($secretKey === null || $secretKey === '') {
            throw new RuntimeException(
                'Stripe is not configured: set stripe.secret_key in local configuration'
            );
        }

        return new StripeGateway(new StripeClient($secretKey), new SystemClock());
    }
}
