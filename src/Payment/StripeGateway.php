<?php

declare(strict_types=1);

namespace Contenir\Commerce\Payment;

use Contenir\Commerce\Exception\PaymentFailedException;
use Contenir\Commerce\Money\Money;
use Psr\Clock\ClockInterface;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

use function array_map;
use function is_string;

final class StripeGateway implements PaymentGatewayInterface
{
    private const CURRENCY = 'aud';

    public function __construct(
        private readonly StripeClient $client,
        private readonly ClockInterface $clock
    ) {
    }

    public function createCheckoutSession(CheckoutRequest $request): CheckoutSession
    {
        $params = [
            'mode'        => 'payment',
            'line_items'  => array_map(
                fn (CheckoutLineItem $item): array => $this->toStripeLineItem($item),
                $request->lineItems
            ),
            'success_url' => $request->successUrl,
            'cancel_url'  => $request->cancelUrl,
            'expires_at'  => $this->clock->now()->getTimestamp() + ($request->expiresAfterMinutes * 60),
        ];

        if ($request->customerEmail !== null) {
            $params['customer_email'] = $request->customerEmail;
        }

        if ($request->metadata !== []) {
            $params['metadata'] = $request->metadata;
        }

        try {
            $session = $this->client->checkout->sessions->create($params);
        } catch (ApiErrorException $e) {
            throw new PaymentFailedException('Unable to create Stripe checkout session', 0, $e);
        }

        return $this->toCheckoutSession($session);
    }

    public function retrieveCheckoutSession(string $sessionId): CheckoutSession
    {
        try {
            $session = $this->client->checkout->sessions->retrieve($sessionId);
        } catch (ApiErrorException $e) {
            throw new PaymentFailedException('Unable to retrieve Stripe checkout session', 0, $e);
        }

        return $this->toCheckoutSession($session);
    }

    public function refund(string $paymentIntentId, ?Money $amount = null): RefundResult
    {
        $params = ['payment_intent' => $paymentIntentId];

        if ($amount !== null) {
            $params['amount'] = $amount->amount;
        }

        try {
            $refund = $this->client->refunds->create($params);
        } catch (ApiErrorException $e) {
            throw new PaymentFailedException('Unable to refund Stripe payment', 0, $e);
        }

        return new RefundResult((string) $refund->id, (string) $refund->status);
    }

    /**
     * @return array<string, mixed>
     */
    private function toStripeLineItem(CheckoutLineItem $item): array
    {
        $productData = ['name' => $item->name];

        if ($item->description !== null) {
            $productData['description'] = $item->description;
        }

        return [
            'quantity'   => $item->quantity,
            'price_data' => [
                'currency'     => self::CURRENCY,
                'unit_amount'  => $item->price->amount,
                'product_data' => $productData,
            ],
        ];
    }

    private function toCheckoutSession(Session $session): CheckoutSession
    {
        $paymentIntent = $session->payment_intent;

        return new CheckoutSession(
            (string) $session->id,
            (string) $session->status,
            $session->url,
            $paymentIntent === null ? null : (is_string($paymentIntent) ? $paymentIntent : $paymentIntent->id),
            $session->customer_email
        );
    }
}
