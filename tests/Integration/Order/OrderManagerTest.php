<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Integration\Order;

use Contenir\Commerce\Exception\ArtworkUnavailableException;
use Contenir\Commerce\Exception\InvalidTransitionException;
use Contenir\Commerce\Model\Entity\ArtworkEntity;
use Contenir\Commerce\Model\Entity\OrderEntity;
use Contenir\Commerce\Model\Entity\OrderItemEntity;
use Contenir\Commerce\Model\Repository\ArtworkRepository;
use Contenir\Commerce\Model\Repository\OrderItemRepository;
use Contenir\Commerce\Model\Repository\OrderRepository;
use Contenir\Commerce\Money\Money;
use Contenir\Commerce\Order\CompletionOutcome;
use Contenir\Commerce\Order\CustomerDetails;
use Contenir\Commerce\Order\OrderManager;
use Contenir\Commerce\Order\PurchaseItem;
use Contenir\Commerce\Tests\TestAsset\FakePaymentGateway;
use Contenir\Commerce\Tests\TestAsset\FixedClock;
use Contenir\Db\Model\Repository\RepositoryLookup;
use DateTimeImmutable;
use InvalidArgumentException;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Full order lifecycle against a real (in-memory) database with a
 * scriptable gateway. Each test builds a fresh database, so no teardown
 * is required.
 */
#[Group('integration')]
final class OrderManagerTest extends TestCase
{
    private OrderRepository $orders;
    private ArtworkRepository $artworks;
    private FakePaymentGateway $gateway;
    private OrderManager $manager;

    protected function setUp(): void
    {
        $adapter = new Adapter([
            'driver'   => 'Pdo_Sqlite',
            'database' => ':memory:',
        ]);

        $adapter->query(
            'CREATE TABLE gallery_order ('
                . 'order_id INTEGER PRIMARY KEY AUTOINCREMENT, '
                . 'order_ref TEXT NOT NULL, '
                . 'customer_name TEXT NULL, customer_email TEXT NULL, customer_phone TEXT NULL, '
                . 'status TEXT NOT NULL DEFAULT "pending", '
                . 'total INTEGER NOT NULL DEFAULT 0, gst_amount INTEGER NOT NULL DEFAULT 0, '
                . 'stripe_checkout_session_id TEXT NULL, stripe_payment_intent_id TEXT NULL, '
                . 'customer_notes TEXT NULL, staff_notes TEXT NULL, '
                . 'paid_at TEXT NULL, collected_at TEXT NULL, refunded_at TEXT NULL, cancelled_at TEXT NULL, '
                . 'created TEXT NULL, updated TEXT NULL'
            . ')',
            Adapter::QUERY_MODE_EXECUTE
        );
        $adapter->query(
            'CREATE TABLE gallery_order_item ('
                . 'order_item_id INTEGER PRIMARY KEY AUTOINCREMENT, '
                . 'order_id INTEGER NOT NULL, artwork_id INTEGER NULL, '
                . 'title TEXT NOT NULL, artist_name TEXT NULL, '
                . 'price INTEGER NOT NULL DEFAULT 0, created TEXT NULL'
            . ')',
            Adapter::QUERY_MODE_EXECUTE
        );
        $adapter->query(
            'CREATE TABLE artwork ('
                . 'artwork_id INTEGER PRIMARY KEY AUTOINCREMENT, '
                . 'resource_id INTEGER NULL, artist_resource_id INTEGER NULL, '
                . 'exhibition_resource_id INTEGER NULL, '
                . 'item_type TEXT NOT NULL DEFAULT "artwork", '
                . 'price INTEGER NOT NULL DEFAULT 0, '
                . 'status TEXT NOT NULL DEFAULT "available", '
                . 'medium TEXT NULL, dimensions TEXT NULL, year TEXT NULL, '
                . 'edition_details TEXT NULL, external_sale_url TEXT NULL, '
                . 'created TEXT NULL, updated TEXT NULL'
            . ')',
            Adapter::QUERY_MODE_EXECUTE
        );

        $lookup         = $this->createStub(RepositoryLookup::class);
        $this->orders   = new OrderRepository($adapter, new OrderEntity(), $lookup);
        $orderItems     = new OrderItemRepository($adapter, new OrderItemEntity(), $lookup);
        $this->artworks = new ArtworkRepository($adapter, new ArtworkEntity(), $lookup);
        $this->gateway  = new FakePaymentGateway();

        $this->manager = new OrderManager(
            $this->orders,
            $orderItems,
            $this->artworks,
            $this->gateway,
            new FixedClock(new DateTimeImmutable('2026-08-20T10:00:00+10:00'))
        );

        foreach ([185000, 98000] as $price) {
            $this->artworks->save($this->artworks->create([
                'price'  => $price,
                'status' => 'available',
            ]));
        }
    }

    /**
     * @return list<PurchaseItem>
     */
    private function items(): array
    {
        return [
            new PurchaseItem(1, 'Headland, Dawn', Money::fromCents(185000), 'June Hollis'),
            new PurchaseItem(2, 'Swan Bay Nocturne', Money::fromCents(98000), 'Marcus Tran'),
        ];
    }

    private function customer(): CustomerDetails
    {
        return new CustomerDetails('Avery Buyer', 'avery@example.test', '0400 000 000', 'Will collect Saturday');
    }

    private function checkedOutOrder(): OrderEntity
    {
        $order = $this->manager->createPendingOrder($this->items(), $this->customer());
        $this->manager->beginCheckout($order, 'https://example.test/thanks', 'https://example.test/cart');

        return $order;
    }

    public function testCreatesPendingOrderWithReferenceTotalsAndSnapshots(): void
    {
        $order = $this->manager->createPendingOrder($this->items(), $this->customer());

        $this->assertSame('LR-2026-0001', $order->order_ref);
        $this->assertSame('pending', $order->status);
        $this->assertSame(283000, (int) $order->total);
        $this->assertSame(25727, (int) $order->gst_amount);

        $items = $this->manager->purchaseItemsFor($order);
        $this->assertCount(2, $items);
        $this->assertSame('Headland, Dawn', $items[0]->title);
        $this->assertSame('June Hollis', $items[0]->artistName);
        $this->assertSame(185000, $items[0]->price->amount);
    }

    public function testRefusesAnEmptyOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager->createPendingOrder([], $this->customer());
    }

    public function testRefusesCartingASoldWork(): void
    {
        $sold = $this->artworks->findOne(['artwork_id' => 2]);
        $this->assertInstanceOf(ArtworkEntity::class, $sold);
        $sold->status = 'sold';
        $this->artworks->save($sold);

        try {
            $this->manager->createPendingOrder($this->items(), $this->customer());
            $this->fail('Expected ArtworkUnavailableException');
        } catch (ArtworkUnavailableException $e) {
            $this->assertSame(['Swan Bay Nocturne'], $e->getTitles());
        }
    }

    public function testBeginCheckoutStoresTheSessionAndSendsSnapshotsToStripe(): void
    {
        $order = $this->checkedOutOrder();

        $this->assertSame('cs_fake_1', $order->stripe_checkout_session_id);

        $request = $this->gateway->lastCheckoutRequest;
        $this->assertNotNull($request);
        $this->assertSame('avery@example.test', $request->customerEmail);
        $this->assertSame('LR-2026-0001', $request->metadata['order_ref']);
        $this->assertCount(2, $request->lineItems);
        $this->assertSame('Headland, Dawn', $request->lineItems[0]->name);
        $this->assertSame(185000, $request->lineItems[0]->price->amount);
    }

    public function testCompletionMarksOrderPaidAndWorksSold(): void
    {
        $order = $this->checkedOutOrder();
        $this->gateway->completeSession('cs_fake_1', 'pi_fake_1');

        $result = $this->manager->completeFromCheckoutSession('cs_fake_1');

        $this->assertSame(CompletionOutcome::Completed, $result->outcome);
        $this->assertSame('paid', $result->order->status);
        $this->assertSame('pi_fake_1', $result->order->stripe_payment_intent_id);
        $this->assertSame('2026-08-20 10:00:00', $result->order->paid_at);
        $this->assertSame('sold', $this->artworks->findOne(['artwork_id' => 1])?->status);
        $this->assertSame('sold', $this->artworks->findOne(['artwork_id' => 2])?->status);
        $this->assertSame([], $this->gateway->refunds);
    }

    public function testCompletionIsIdempotentAcrossWebhookRetries(): void
    {
        $this->checkedOutOrder();
        $this->gateway->completeSession('cs_fake_1', 'pi_fake_1');

        $this->manager->completeFromCheckoutSession('cs_fake_1');
        $result = $this->manager->completeFromCheckoutSession('cs_fake_1');

        $this->assertSame(CompletionOutcome::AlreadyCompleted, $result->outcome);
        $this->assertCount(1, $this->orders->find(['order_ref' => 'LR-2026-0001']));
    }

    public function testUnpaidSessionLeavesTheOrderPending(): void
    {
        $this->checkedOutOrder();

        $result = $this->manager->completeFromCheckoutSession('cs_fake_1');

        $this->assertSame(CompletionOutcome::NotPaid, $result->outcome);
        $this->assertSame('pending', $result->order->status);
        $this->assertSame('available', $this->artworks->findOne(['artwork_id' => 1])?->status);
    }

    public function testSecondBuyerOfTheSameWorkIsRefundedInFull(): void
    {
        $first = $this->manager->createPendingOrder(
            [new PurchaseItem(1, 'Headland, Dawn', Money::fromCents(185000), 'June Hollis')],
            new CustomerDetails('First Buyer', 'first@example.test')
        );
        $this->manager->beginCheckout($first, 'https://example.test/thanks', 'https://example.test/cart');

        $second = $this->manager->createPendingOrder(
            [new PurchaseItem(1, 'Headland, Dawn', Money::fromCents(185000), 'June Hollis')],
            new CustomerDetails('Second Buyer', 'second@example.test')
        );
        $this->manager->beginCheckout($second, 'https://example.test/thanks', 'https://example.test/cart');

        $this->gateway->completeSession('cs_fake_1', 'pi_first');
        $this->gateway->completeSession('cs_fake_2', 'pi_second');

        $firstResult  = $this->manager->completeFromCheckoutSession('cs_fake_1');
        $secondResult = $this->manager->completeFromCheckoutSession('cs_fake_2');

        $this->assertSame(CompletionOutcome::Completed, $firstResult->outcome);
        $this->assertSame(CompletionOutcome::RefundedRace, $secondResult->outcome);
        $this->assertSame(['Headland, Dawn'], $secondResult->unavailableTitles);
        $this->assertSame('refunded', $secondResult->order->status);
        $this->assertSame(
            [['paymentIntentId' => 'pi_second', 'amount' => null]],
            $this->gateway->refunds
        );
    }

    public function testExpiredCheckoutCancelsThePendingOrderOnly(): void
    {
        $this->checkedOutOrder();

        $cancelled = $this->manager->expireCheckout('cs_fake_1');
        $this->assertInstanceOf(OrderEntity::class, $cancelled);
        $this->assertSame('cancelled', $cancelled->status);

        $this->assertNull($this->manager->expireCheckout('cs_fake_1'));
        $this->assertNull($this->manager->expireCheckout('cs_never_existed'));
    }

    public function testPickupLifecycleReachesCollected(): void
    {
        $this->checkedOutOrder();
        $this->gateway->completeSession('cs_fake_1', 'pi_fake_1');
        $order = $this->manager->completeFromCheckoutSession('cs_fake_1')->order;

        $this->manager->markAwaitingPickup($order);
        $this->assertSame('awaiting_pickup', $order->status);

        $this->manager->markCollected($order);
        $this->assertSame('collected', $order->status);
        $this->assertSame('2026-08-20 10:00:00', $order->collected_at);
    }

    public function testRefundGoesThroughStripeAndClosesTheOrder(): void
    {
        $this->checkedOutOrder();
        $this->gateway->completeSession('cs_fake_1', 'pi_fake_1');
        $order = $this->manager->completeFromCheckoutSession('cs_fake_1')->order;

        $this->manager->refundOrder($order, Money::fromCents(50000));

        $this->assertSame('refunded', $order->status);
        $this->assertSame(
            [['paymentIntentId' => 'pi_fake_1', 'amount' => 50000]],
            $this->gateway->refunds
        );
        $this->assertSame('sold', $this->artworks->findOne(['artwork_id' => 1])?->status);
    }

    public function testRefundWithoutAPaymentIsRejected(): void
    {
        $order = $this->manager->createPendingOrder($this->items(), $this->customer());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no Stripe payment');

        $this->manager->refundOrder($order);
    }

    public function testCollectedOrdersCannotBeCancelled(): void
    {
        $this->checkedOutOrder();
        $this->gateway->completeSession('cs_fake_1', 'pi_fake_1');
        $order = $this->manager->completeFromCheckoutSession('cs_fake_1')->order;
        $this->manager->markAwaitingPickup($order);
        $this->manager->markCollected($order);

        $this->expectException(InvalidTransitionException::class);

        $this->manager->cancelOrder($order);
    }

    public function testUnknownSessionIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No order for checkout session');

        $this->manager->completeFromCheckoutSession('cs_nowhere');
    }
}
