<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Sales\Model\Order;
use TddWizard\Fixtures\Catalog\Product\ProductBuilder;
use TddWizard\Fixtures\Checkout\CartBuilder;
use TddWizard\Fixtures\Checkout\CustomerCheckout;
use TddWizard\Fixtures\Customer\AddressBuilder;
use TddWizard\Fixtures\Customer\CustomerBuilder;
use TddWizard\Fixtures\Customer\CustomerFixture;

/**
 * Builder to be used by fixtures
 */
class OrderBuilder
{
    private CartBuilder $cartBuilder;
    private CustomerInterface $customer;
    private ?string $shippingMethod = null;
    private array $productBuilders;
    private ?string $paymentMethod = null;

    public function __construct()
    {
    }

    public static function anOrder(): OrderBuilder
    {
        return new static();
    }

    public function withProducts(ProductBuilder ...$productBuilders): OrderBuilder
    {
        $builder = clone $this;
        $builder->productBuilders = $productBuilders;

        return $builder;
    }

    public function withCustomer(CustomerInterface $customer): OrderBuilder
    {
        $builder = clone $this;
        $builder->customer = $customer;

        return $builder;
    }

    public function withCart(CartBuilder $cartBuilder): OrderBuilder
    {
        $builder = clone $this;
        $builder->cartBuilder = $cartBuilder;

        return $builder;
    }

    public function withShippingMethod(string $shippingMethod): OrderBuilder
    {
        $builder = clone $this;
        $builder->shippingMethod = $shippingMethod;

        return $builder;
    }

    public function withPaymentMethod(string $paymentMethod): OrderBuilder
    {
        $builder = clone $this;
        $builder->paymentMethod = $paymentMethod;

        return $builder;
    }

    /**
     * @throws \Exception
     */
    public function build(): Order
    {
        $builder = clone $this;

        if (empty($builder->productBuilders)) {
            // init simple products
            for ($i = 0; $i < 3; $i++) {
                $builder->productBuilders[] = ProductBuilder::aSimpleProduct();
            }
        }

        // create products
        $products = array_map(
            callback: static function (ProductBuilder $productBuilder) {
                return $productBuilder->build();
            },
            array: $builder->productBuilders,
        );

        if (empty($builder->customer)) {
            // init customer
            $builder->customer = CustomerBuilder::aCustomer()
                ->withAddresses(
                    addressBuilders: AddressBuilder::anAddress()->asDefaultBilling()->asDefaultShipping(),
                )->build();
        }

        // log customer in
        $customer = $builder->customer;
        $customerFixture = new CustomerFixture(customer: $customer);
        $customerFixture->login();

        if (empty($builder->cartBuilder)) {
            // init cart, add products
            $builder->cartBuilder = CartBuilder::forCurrentSession();
            foreach ($products as $product) {
                $qty = 1;
                $builder->cartBuilder = $builder->cartBuilder->withSimpleProduct(sku: $product->getSku(), qty: $qty);
            }
        }
        $addresses = $customerFixture->getCustomer()->getAddresses();
        $shippingAddress = array_filter(
            array: $addresses,
            callback: static fn (AddressInterface $address): bool => (
                $address->getId() === $customerFixture->getDefaultShippingAddressId()
            ),
        );
        if (!count($shippingAddress)) {
            $shippingAddress = $addresses;
        }
        $billingAddress = array_filter(
            array: $addresses,
            callback: static fn (AddressInterface $address): bool => (
                $address->getId() === $customerFixture->getDefaultBillingAddressId()
            ),
        );
        if (!count($billingAddress)) {
            $billingAddress = $addresses;
        }
        $builder->cartBuilder->withCustomer(customer: $customerFixture->getCustomer());
        $builder->cartBuilder->withAddress(address: array_shift($shippingAddress), isBillingAddress: false);
        $builder->cartBuilder->withAddress(address: array_shift($billingAddress), isShippingAddress: false);

        // check out, place order
        $checkout = CustomerCheckout::fromCart(
            cart: $builder->cartBuilder->build(),
        );
        if ($builder->shippingMethod) {
            $checkout = $checkout->withShippingMethodCode(code: $builder->shippingMethod);
        }

        if ($builder->paymentMethod) {
            $checkout = $checkout->withPaymentMethodCode(code: $builder->paymentMethod);
        }
        $order = $checkout->placeOrder();
        $customerFixture->logout();

        return $order;
    }
}
