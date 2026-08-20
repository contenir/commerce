# contenir/commerce

Commerce domain for [Contenir CMS](https://github.com/contenir) — orders, artworks, GST-inclusive money and Stripe checkout for small-volume gallery sales with in-person pickup.

## Install

```bash
composer require contenir/commerce
```

## Usage

Prices are GST-inclusive AUD held as integer cents:

```php
use Contenir\Commerce\Money\Money;

$price = Money::fromCents(185000);
$price->gstComponent();   // Money of 16818 cents
$price->format();         // "$1,850.00"
```

The order lifecycle is enforced by `OrderStatus`:

```php
use Contenir\Commerce\Order\OrderStatus;

$status = OrderStatus::Pending;
$status = $status->transitionTo(OrderStatus::Paid);
$status->canTransitionTo(OrderStatus::Collected); // false — must await pickup first
```

Payments go through `PaymentGatewayInterface`, implemented by `StripeGateway`
(Stripe hosted Checkout). Configure with:

```php
'stripe' => [
    'secret_key' => '...',
],
```

Entities and repositories (artworks, orders, order items, artist enquiries,
email log) build on `contenir/contenir-db-model` and are registered by the
shipped `ConfigProvider` for Laminas applications.
