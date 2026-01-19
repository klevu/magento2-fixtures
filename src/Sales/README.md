### Order

The `OrderBuilder` is a shortcut for checkout simulation.

```php
$order = OrderBuilder::anOrder()->build(); 
```

Logged-in customer, products, and cart item quantities will be
generated internally unless more control is desired:

```php
$order = OrderBuilder::anOrder()
    ->withProducts(
        // prepare catalog product fixtures
        ProductBuilder::aSimpleProduct()->withSku('foo'),
        ProductBuilder::aSimpleProduct()->withSku('bar')
    )->withCart(
        // define cart item quantities
        CartBuilder::forCurrentSession()->withSimpleProduct('foo', 2)->withSimpleProduct('bar', 3)
    )->build();
```

### Shipment

Orders can be fully or partially shipped, optionally with tracks.

```php
$order = OrderBuilder::anOrder()->build();

// ship everything
$shipment = ShipmentBuilder::forOrder($order)->build();
// ship only given order items, add tracks
$shipment = ShipmentBuilder::forOrder($order)
    ->withItem($fooItemId, $fooQtyToShip)
    ->withItem($barItemId, $barQtyToShip)
    ->withTrackingNumbers('123-FOO', '456-BAR')
    ->build();
```

### Invoice

Orders can be fully or partially invoiced.

```php
$order = OrderBuilder::anOrder()->build();

// invoice everything
$invoice = InvoiceBuilder::forOrder($order)->build();
// invoice only given order items
$invoice = InvoiceBuilder::forOrder($order)
    ->withItem($fooItemId, $fooQtyToInvoice)
    ->withItem($barItemId, $barQtyToInvoice)
    ->build();
```

### Credit Memo

Credit memos can be created for either all or some of the items ordered.
An invoice to refund will be created internally.

```php
$order = OrderBuilder::anOrder()->build();

// refund everything
$creditmemo = CreditmemoBuilder::forOrder($order)->build();
// refund only given order items
$creditmemo = CreditmemoBuilder::forOrder($order)
    ->withItem($fooItemId, $fooQtyToRefund)
    ->withItem($barItemId, $barQtyToRefund)
    ->build();
```
