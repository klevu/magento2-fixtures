# Checkout

To create a quote, use the `CartBuilder` together with product fixtures:

```php
$cart = CartBuilder::forCurrentSession()
  ->withSimpleProduct(
    $productFixture1->getSku()
  )
  ->withSimpleProduct(
    $productFixture2->getSku(), 10 // optional qty parameter
  )
  ->build()
$quote = $cart->getQuote();
```

Checkout is supported for logged in customers. To create an order, you can simulate the checkout as follows, given a
customer fixture with default shipping and billing addresses and a product fixture:

```php
$customerFixture = new CustomerFixture(CustomerBuilder::aCustomer()->withAddresses(
  AddressBuilder::anAddress()->asDefaultBilling(),
  AddressBuilder::anAddress()->asDefaultShipping()
)->build());
$customerFixture->login();

$checkout = CustomerCheckout::fromCart(
  CartBuilder::forCurrentSession()
    ->withProductRequest(ProductBuilder::aVirtualProduct()->build()->getSku())
    ->build()
);

$order = $checkout->placeOrder();
```

It will try to select the default addresses and the first available shipping and payment methods.

You can also select them explicitly:

```php
$order = $checkout
  ->withShippingMethodCode('freeshipping_freeshipping')
  ->withPaymentMethodCode('checkmo')
  ->withCustomerBillingAddressId($this->customerFixture->getOtherAddressId())
  ->withCustomerShippingAddressId($this->customerFixture->getOtherAddressId())
  ->placeOrder();
```
