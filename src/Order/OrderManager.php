<?php

declare(strict_types=1);

namespace Contenir\Commerce\Order;

use Contenir\Commerce\Artwork\ArtworkStatus;
use Contenir\Commerce\Exception\ArtworkUnavailableException;
use Contenir\Commerce\Model\Entity\ArtworkEntity;
use Contenir\Commerce\Model\Entity\OrderEntity;
use Contenir\Commerce\Model\Repository\ArtworkRepository;
use Contenir\Commerce\Model\Repository\OrderItemRepository;
use Contenir\Commerce\Model\Repository\OrderRepository;
use Contenir\Commerce\Money\Money;
use Contenir\Commerce\Payment\CheckoutLineItem;
use Contenir\Commerce\Payment\CheckoutRequest;
use Contenir\Commerce\Payment\CheckoutSession;
use Contenir\Commerce\Payment\PaymentGatewayInterface;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;
use RuntimeException;

use function array_map;
use function sprintf;

/**
 * Orchestrates the order lifecycle shared by the public site (create,
 * checkout, webhook completion) and the CMS (pickup, refund, cancel).
 *
 * There are no customer holds: availability is checked when the order is
 * created and again when payment completes. If two buyers pay for the same
 * work, the first completed payment wins and the second is refunded in full.
 */
final class OrderManager
{
    private const REF_PREFIX = 'LR';

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderItemRepository $orderItems,
        private readonly ArtworkRepository $artworks,
        private readonly PaymentGatewayInterface $gateway,
        private readonly ClockInterface $clock
    ) {
    }

    /**
     * @param list<PurchaseItem> $items
     * @throws ArtworkUnavailableException when a work has been sold since it was carted
     */
    public function createPendingOrder(array $items, CustomerDetails $customer): OrderEntity
    {
        if ($items === []) {
            throw new InvalidArgumentException('An order requires at least one item');
        }

        $this->assertAvailable($items);

        $total = Money::fromCents(0);
        foreach ($items as $item) {
            $total = $total->add($item->price);
        }

        $now = $this->now();

        $order = $this->orders->create([
            'order_ref'      => sprintf('%s-PENDING', self::REF_PREFIX),
            'customer_name'  => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'customer_notes' => $customer->notes,
            'status'         => OrderStatus::Pending->value,
            'total'          => $total->amount,
            'gst_amount'     => $total->gstComponent()->amount,
            'created'        => $now,
            'updated'        => $now,
        ]);
        $this->orders->save($order);

        $order->order_ref = sprintf(
            '%s-%s-%04d',
            self::REF_PREFIX,
            $this->clock->now()->format('Y'),
            (int) $order->order_id
        );
        $this->orders->save($order);

        foreach ($items as $item) {
            $line = $this->orderItems->create([
                'order_id'    => (int) $order->order_id,
                'artwork_id'  => $item->artworkId,
                'title'       => $item->title,
                'artist_name' => $item->artistName,
                'price'       => $item->price->amount,
                'created'     => $now,
            ]);
            $this->orderItems->save($line);
        }

        return $order;
    }

    public function beginCheckout(OrderEntity $order, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $lineItems = array_map(
            static fn (PurchaseItem $item): CheckoutLineItem => new CheckoutLineItem(
                $item->title,
                $item->price,
                1,
                $item->artistName
            ),
            $this->purchaseItemsFor($order)
        );

        $session = $this->gateway->createCheckoutSession(new CheckoutRequest(
            $lineItems,
            $successUrl,
            $cancelUrl,
            $order->customer_email,
            [
                'order_ref' => (string) $order->order_ref,
                'order_id'  => (string) $order->order_id,
            ]
        ));

        $order->stripe_checkout_session_id = $session->id;
        $order->updated                    = $this->now();
        $this->orders->save($order);

        return $session;
    }

    /**
     * Safe to call repeatedly (webhook retries, thank-you page revisits).
     */
    public function completeFromCheckoutSession(string $sessionId): CompletionResult
    {
        $order = $this->orders->findOne(['stripe_checkout_session_id' => $sessionId]);
        if ($order === null) {
            throw new RuntimeException(sprintf('No order for checkout session "%s"', $sessionId));
        }

        $status = OrderStatus::from((string) $order->status);
        if ($status !== OrderStatus::Pending) {
            return new CompletionResult($order, CompletionOutcome::AlreadyCompleted);
        }

        $session = $this->gateway->retrieveCheckoutSession($sessionId);
        if (! $session->isComplete()) {
            return new CompletionResult($order, CompletionOutcome::NotPaid);
        }

        $order->stripe_payment_intent_id = $session->paymentIntentId;

        $lost = $this->unavailableTitlesFor($order);
        if ($lost !== []) {
            return $this->refundRace($order, $lost);
        }

        $now            = $this->now();
        $order->status  = OrderStatus::Pending->transitionTo(OrderStatus::Paid)->value;
        $order->paid_at = $now;
        $order->updated = $now;
        $this->orders->save($order);

        foreach ($this->artworksFor($order) as $artwork) {
            $artwork->status  = ArtworkStatus::Sold->value;
            $artwork->updated = $now;
            $this->artworks->save($artwork);
        }

        return new CompletionResult($order, CompletionOutcome::Completed);
    }

    /**
     * checkout.session.expired — release the pending order. No stock was
     * ever held, so this is bookkeeping only.
     */
    public function expireCheckout(string $sessionId): ?OrderEntity
    {
        $order = $this->orders->findOne(['stripe_checkout_session_id' => $sessionId]);
        if ($order === null || OrderStatus::from((string) $order->status) !== OrderStatus::Pending) {
            return null;
        }

        $now                 = $this->now();
        $order->status       = OrderStatus::Pending->transitionTo(OrderStatus::Cancelled)->value;
        $order->cancelled_at = $now;
        $order->updated      = $now;
        $this->orders->save($order);

        return $order;
    }

    public function markAwaitingPickup(OrderEntity $order): void
    {
        $order->status  = OrderStatus::from((string) $order->status)
            ->transitionTo(OrderStatus::AwaitingPickup)->value;
        $order->updated = $this->now();
        $this->orders->save($order);
    }

    public function markCollected(OrderEntity $order): void
    {
        $now                 = $this->now();
        $order->status       = OrderStatus::from((string) $order->status)
            ->transitionTo(OrderStatus::Collected)->value;
        $order->collected_at = $now;
        $order->updated      = $now;
        $this->orders->save($order);
    }

    /**
     * Refunds via Stripe and closes the order. Artwork availability is left
     * untouched — returning a work to sale is a curatorial decision made in
     * the CMS, not a side effect.
     */
    public function refundOrder(OrderEntity $order, ?Money $amount = null): void
    {
        $paymentIntentId = (string) $order->stripe_payment_intent_id;
        if ($paymentIntentId === '') {
            throw new RuntimeException('Order has no Stripe payment to refund');
        }

        $next = OrderStatus::from((string) $order->status)->transitionTo(OrderStatus::Refunded);
        $this->gateway->refund($paymentIntentId, $amount);

        $now                = $this->now();
        $order->status      = $next->value;
        $order->refunded_at = $now;
        $order->updated     = $now;
        $this->orders->save($order);
    }

    public function cancelOrder(OrderEntity $order): void
    {
        $now                 = $this->now();
        $order->status       = OrderStatus::from((string) $order->status)
            ->transitionTo(OrderStatus::Cancelled)->value;
        $order->cancelled_at = $now;
        $order->updated      = $now;
        $this->orders->save($order);
    }

    /**
     * @return list<PurchaseItem>
     */
    public function purchaseItemsFor(OrderEntity $order): array
    {
        $items = [];
        foreach ($this->orderItems->find(['order_id' => (int) $order->order_id]) as $line) {
            $items[] = new PurchaseItem(
                (int) $line->artwork_id,
                (string) $line->title,
                Money::fromCents((int) $line->price),
                $line->artist_name === null ? null : (string) $line->artist_name
            );
        }

        return $items;
    }

    /**
     * @param list<PurchaseItem> $items
     */
    private function assertAvailable(array $items): void
    {
        $unavailable = [];
        foreach ($items as $item) {
            $artwork = $this->artworks->findOne(['artwork_id' => $item->artworkId]);
            if ($artwork === null || $artwork->status !== ArtworkStatus::Available->value) {
                $unavailable[] = $item->title;
            }
        }

        if ($unavailable !== []) {
            throw ArtworkUnavailableException::forTitles($unavailable);
        }
    }

    /**
     * First completed payment wins: this payment arrived second for at least
     * one work, so refund it in full and close the order as paid-then-refunded.
     *
     * @param list<string> $lost
     */
    private function refundRace(OrderEntity $order, array $lost): CompletionResult
    {
        $paymentIntentId = (string) $order->stripe_payment_intent_id;
        if ($paymentIntentId !== '') {
            $this->gateway->refund($paymentIntentId);
        }

        $now                = $this->now();
        $order->status      = OrderStatus::Pending->transitionTo(OrderStatus::Paid)
            ->transitionTo(OrderStatus::Refunded)->value;
        $order->paid_at     = $now;
        $order->refunded_at = $now;
        $order->updated     = $now;
        $this->orders->save($order);

        return new CompletionResult($order, CompletionOutcome::RefundedRace, $lost);
    }

    /**
     * @return list<string>
     */
    private function unavailableTitlesFor(OrderEntity $order): array
    {
        $lost = [];
        foreach ($this->purchaseItemsFor($order) as $item) {
            $artwork = $this->artworks->findOne(['artwork_id' => $item->artworkId]);
            if ($artwork === null || $artwork->status !== ArtworkStatus::Available->value) {
                $lost[] = $item->title;
            }
        }

        return $lost;
    }

    /**
     * @return list<ArtworkEntity>
     */
    private function artworksFor(OrderEntity $order): array
    {
        $artworks = [];
        foreach ($this->purchaseItemsFor($order) as $item) {
            $artwork = $this->artworks->findOne(['artwork_id' => $item->artworkId]);
            if ($artwork !== null) {
                $artworks[] = $artwork;
            }
        }

        return $artworks;
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
