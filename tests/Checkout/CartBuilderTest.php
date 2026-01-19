<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\Product\ProductFixturePool;
use TddWizard\Fixtures\Catalog\Product\ProductTrait;
use TddWizard\Fixtures\Customer\CustomerFixturePool;
use TddWizard\Fixtures\Customer\CustomerTrait;

class CartBuilderTest extends TestCase
{
    use CustomerTrait;
    use ProductTrait;

    private readonly ObjectManagerInterface $objectManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerFixturePool = $this->objectManager->get(CustomerFixturePool::class);
        $this->productFixturePool = $this->objectManager->get(ProductFixturePool::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->customerFixturePool->rollback();
        $this->productFixturePool->rollback();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     */
    public function testProductCanBeAddedWithCustomBuyRequest(): void
    {
        $qty = 2;
        $customOptionId = 42;
        $customOptionValue = 'foobar';

        $this->createProduct();
        $productFixture = $this->productFixturePool->get('tdd_product');

        $cartBuilder = CartBuilder::forCurrentSession();
        $cartBuilder = $cartBuilder->withProductRequest(
            sku: $productFixture->getSku(),
            qty: $qty,
            request: ['options' => [$customOptionId => $customOptionValue]],
        );
        $cart = $cartBuilder->build();

        $quoteItems = $cart->getAllItems();
        $this->assertCount(expectedCount: 1, haystack: $quoteItems, message: "1 quote item should be added");
        /** @var Item $quoteItem */
        $quoteItem = reset(array: $quoteItems);
        $serializedBuyRequest = $quoteItem->getOptionByCode(code: 'info_buyRequest')
            ->getValue();
        $serializer = $this->objectManager->get(Json::class);
        $this->assertJsonStringEqualsJsonString(
            $serializer->serialize(data: ['qty' => $qty, 'options' => ['42' => 'foobar']]),
            $serializedBuyRequest,
            "Value of info_buyRequest option should be as configured",
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     */
    public function testSimpleProductAddedToCart(): void
    {
        $this->createCustomer();
        $customerFixture = $this->customerFixturePool->get('tdd_customer');
        $customer = $customerFixture->getCustomer();

        $this->createProduct();
        $productFixture = $this->productFixturePool->get('tdd_product');

        $qty = 3;

        $cartBuilder = CartBuilder::forCurrentSession();
        $cartBuilder = $cartBuilder->withCustomer(customer: $customer);
        $cartBuilder = $cartBuilder->withSimpleProduct(sku: $productFixture->getSku(), qty: $qty);
        $cart = $cartBuilder->build();

        $quoteItems = $cart->getAllItems();
        $this->assertCount(expectedCount: 1, haystack: $quoteItems, message: "1 quote item should be added");
        /** @var Item $quoteItem */
        $quoteItem = reset(array: $quoteItems);
        $this->assertSame(expected: 3.0, actual: $quoteItem->getQty(), message: "Item quantity should be as configured");
        $this->assertEquals(expected: $productFixture->getId(), actual: $quoteItem->getProduct()->getId(), message: "Item product should be as configured");
    }
}
