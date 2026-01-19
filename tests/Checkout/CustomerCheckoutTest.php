<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\Product\ProductBuilder;
use TddWizard\Fixtures\Catalog\Product\ProductFixturePool;
use TddWizard\Fixtures\Customer\AddressBuilder;
use TddWizard\Fixtures\Customer\CustomerBuilder;
use TddWizard\Fixtures\Customer\CustomerFixturePool;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerCheckoutTest extends TestCase
{
    private CustomerFixturePool $customerFixtures;
    private ProductFixturePool $productFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productFixtures = new ProductFixturePool();
        $this->customerFixtures = new CustomerFixturePool();
        $this->customerFixtures->add(
            customer: CustomerBuilder::aCustomer()->withAddresses(
                addressBuilders: AddressBuilder::anAddress()->asDefaultBilling()->asDefaultShipping(),
            )->build(),
        );
        $this->productFixtures->add(
            product: ProductBuilder::aSimpleProduct()->withPrice(price: 10)->build(),
            key: 'simple',
        );
        $this->productFixtures->add(
            product: ProductBuilder::aVirtualProduct()->withPrice(price: 10)->build(),
            key: 'virtual',
        );
    }

    /**
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->customerFixtures->rollback();
        $this->productFixtures->rollback();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     */
    public function testCreateOrderFromCart(): void
    {
        $customerFixture = $this->customerFixtures->get();
        $customerFixture->login();
        $checkout = CustomerCheckout::fromCart(
            CartBuilder::forCurrentSession()
                ->withCustomer(customer: $customerFixture->getCustomer())
                ->withSimpleProduct(
                    sku: $this->productFixtures->get('simple')->getSku(),
                )->build(),
        );
        $order = $checkout->placeOrder();
        $this->assertNotEmpty($order->getEntityId(), 'Order should be saved successfully');
        $this->assertNotEmpty($order->getShippingDescription(), 'Order should have a shipping description');
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     */
    public function testCreateOrderFromCartWithVirtualProduct(): void
    {
        $customerFixture = $this->customerFixtures->get();
        $customerFixture->login();
        $checkout = CustomerCheckout::fromCart(
            CartBuilder::forCurrentSession()
                ->withCustomer(customer: $customerFixture->getCustomer())
                ->withSimpleProduct(
                    sku: $this->productFixtures->get(key: 'virtual')->getSku(),
                )->build(),
        );
        $order = $checkout->placeOrder();
        $this->assertNotEmpty(actual: $order->getEntityId(), message: 'Order should be saved successfully');
        $this->assertEmpty(
            actual: $order->getExtensionAttributes()->getShippingAssignments(),
            message: 'Order with virtual product should not have any shipping assignments',
        );
        $this->assertEmpty(
            actual: $order->getShippingDescription(),
            message: 'Order should not have a shipping description',
        );
    }
}
