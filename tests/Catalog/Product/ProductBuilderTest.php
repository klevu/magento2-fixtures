<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Model\Stock;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\Group;
use Magento\Downloadable\Model\Product\Type as DownloadableType;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceInfo\Base as BasePriceInfo;
use Magento\GroupedProduct\Pricing\Price\FinalPrice;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\Attribute\AttributeFixturePool;
use TddWizard\Fixtures\Catalog\Attribute\AttributeTrait;

/**
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class ProductBuilderTest extends TestCase
{
    use AttributeTrait;

    private ObjectManagerInterface $objectManager;
    private ProductRepositoryInterface $productRepository;
    /**
     * @var ProductFixture[]
     */
    private array $products = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->attributeFixturePool = $this->objectManager->create(type: AttributeFixturePool::class);
        $this->productRepository = $this->objectManager->create(type: ProductRepositoryInterface::class);
        $this->products = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (!empty($this->products)) {
            foreach ($this->products as $product) {
                ProductFixtureRollback::create()->execute($product);
            }
        }
        $this->attributeFixturePool->rollback();
    }

    public function testDefaultSimpleProduct(): void
    {
        $productFixture = new ProductFixture(
            product: ProductBuilder::aSimpleProduct()->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $this->assertEquals(expected: Type::TYPE_SIMPLE, actual: $product->getTypeId());
        $this->assertEquals(expected: 'TDD Test Simple Product', actual: $product->getName());
        $this->assertEquals(expected: [1], actual: $product->getWebsiteIds());
        $this->assertEquals(expected: 1, actual: $product->getData('tax_class_id'));
        $this->assertEquals(expected: 10.00, actual: $product->getFinalPrice(qty: 1), message: 'price');
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        $this->assertTrue(condition: $stockItem->getIsInStock());
        $this->assertEquals(expected: 100, actual: $stockItem->getQty());
        $this->assertEquals(expected: 0, actual: $stockItem->getIsQtyDecimal(), message: 'is qty decimal');
        $this->assertEquals(expected: Stock::BACKORDERS_NO, actual: $stockItem->getBackorders(), message: 'backorders');
    }

    /**
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     */
    public function testSimpleProductWithSpecificAttributes(): void
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(type: StoreManagerInterface::class);
        $secondWebsiteId = $storeManager->getWebsite(websiteId: 'test')->getId();
        $productFixture = new ProductFixture(
            product: ProductBuilder::aSimpleProduct()
                ->withSku(sku: 'foobar')
                ->withName(name: 'Foo Bar')
                ->withStatus(status: Status::STATUS_DISABLED)
                ->withVisibility(visibility: Visibility::VISIBILITY_NOT_VISIBLE)
                ->withWebsiteIds(websiteIds: [$secondWebsiteId])
                ->withPrice(price: 9.99)
                ->withTaxClassId(taxClassId: 2)
                ->withIsInStock(inStock: false)
                ->withIsQtyDecimal(isQtyDecimal: true)
                ->withStockQty(qty: -1.2)
                ->withBackorders(backorders: Stock::BACKORDERS_YES_NOTIFY)
                ->withWeight(weight: 10)
                ->withCustomAttributes(
                    attributeValues: [
                        'cost' => 2.0,
                    ],
                )
                ->withImage(fileName: 'image3.png')
                ->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $this->assertEquals(expected: 'foobar', actual: $product->getSku(), message: 'sku');
        $this->assertEquals(
            expected: 'foobar',
            actual: $product->getUrlKey(),
            message: 'URL key should equal SKU if not set otherwise',
        );
        $this->assertEquals(expected: 'Foo Bar', actual: $product->getName(), message: 'name');
        $this->assertEquals(expected: Status::STATUS_DISABLED, actual: $product->getStatus(), message: 'status');
        $this->assertEquals(
            expected: Visibility::VISIBILITY_NOT_VISIBLE,
            actual: $product->getVisibility(),
            message: 'visibility',
        );
        // current website (1) is always added by ProductRepository
        $this->assertEquals(
            expected: [1, $secondWebsiteId],
            actual: $product->getWebsiteIds(),
            message: 'website ids',
        );
        $this->assertEquals(expected: 9.99, actual: $product->getFinalPrice(qty: 1), message: 'price');
        $this->assertEquals(expected: 10, actual: $product->getWeight(), message: 'weight');
        $this->assertEquals(expected: 2, actual: $product->getData(key: 'tax_class_id'), message: 'tax class id');
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        $this->assertFalse(condition: $stockItem->getIsInStock(), message: 'in stock');
        $this->assertEquals(expected: 1, actual: $stockItem->getIsQtyDecimal(), message: 'is qty decimal');
        $this->assertEquals(expected: -1.2, actual: $stockItem->getQty(), message: 'stock qty');
        $this->assertEquals(
            expected: Stock::BACKORDERS_YES_NOTIFY,
            actual: $stockItem->getBackorders(),
            message: 'backorders',
        );
        $this->assertEquals(
            expected: 2.0,
            actual: $product->getCustomAttribute(attributeCode: 'cost')->getValue(),
            message: 'custom attribute "cost"',
        );
        $this->assertStringMatchesFormat(format: '/i/m/image3%a.png', string: $product->getImage());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/dropdown_attribute.php
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     */
    public function testSimpleProductWithStoreSpecificAttributes(): void
    {
        /*
         * Values from core fixture files
         */
        $secondStoreCode = 'fixture_second_store';
        $userDefinedAttributeCode = 'dropdown_attribute';
        $userDefinedDefaultValue = 1;
        $userDefinedStoreValue = 2;
        // ---

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(type: StoreManagerInterface::class);
        $secondStoreId = (int)$storeManager->getStore(storeId: $secondStoreCode)->getId();
        $productFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()
                ->withName(name: 'TDD Test Default Name')
                ->withName(name: 'Store Name', storeId: $secondStoreId)
                ->withStatus(status: Status::STATUS_DISABLED)
                ->withStatus(status: Status::STATUS_ENABLED, storeId: $secondStoreId)
                ->withVisibility(visibility: Visibility::VISIBILITY_NOT_VISIBLE)
                ->withVisibility(visibility: Visibility::VISIBILITY_IN_CATALOG, storeId: $secondStoreId)
                ->withCustomAttributes(
                    attributeValues: [
                        $userDefinedAttributeCode => $userDefinedDefaultValue,
                    ],
                )
                ->withCustomAttributes(
                    attributeValues: [
                        $userDefinedAttributeCode => $userDefinedStoreValue,
                    ],
                    storeId: $secondStoreId,
                )
                ->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $this->assertEquals(
            expected: 'TDD Test Default Name',
            actual: $product->getName(),
            message: 'Default name',
        );
        $this->assertEquals(
            expected: Status::STATUS_DISABLED,
            actual: $product->getStatus(),
            message: 'Default status should be disabled',
        );
        $this->assertEquals(
            expected: Visibility::VISIBILITY_NOT_VISIBLE,
            actual: $product->getVisibility(),
            message: 'Default visibility',
        );
        $this->assertEquals(
            expected: $userDefinedDefaultValue,
            actual: $product->getCustomAttribute(attributeCode: $userDefinedAttributeCode)->getValue(),
            message: 'Default custom attribute',
        );

        /** @var Product $product */
        $productInStore = $this->productRepository->getById(
            productId: $productFixture->getId(),
            storeId: $secondStoreId,
        );
        $this->assertEquals(
            expected: 'Store Name',
            actual: $productInStore->getName(),
            message: 'Store specific name',
        );
        $this->assertEquals(
            expected: Status::STATUS_ENABLED,
            actual: $productInStore->getStatus(),
            message: 'Store specific status should be enabled',
        );
        $this->assertEquals(
            expected: Visibility::VISIBILITY_IN_CATALOG,
            actual: $productInStore->getVisibility(),
            message: 'Store specific visibility',
        );
        $this->assertEquals(
            expected: $userDefinedStoreValue,
            actual: $productInStore->getCustomAttribute(attributeCode: $userDefinedAttributeCode)->getValue(),
            message: 'Store specific custom attribute',
        );
    }

    public function testRandomSkuOnBuild(): void
    {
        $builder = ProductBuilder::aSimpleProduct();
        $productFixture = new ProductFixture(
            product: $builder->build(),
        );
        $this->assertMatchesRegularExpression(pattern: '/[0-9a-f]{32}/', string: $productFixture->getSku());
        $this->products[] = $productFixture;

        $otherProductFixture = new ProductFixture(
            product: $builder->build(),
        );
        $this->assertMatchesRegularExpression(pattern: '/[0-9a-f]{32}/', string: $otherProductFixture->getSku());
        $this->assertNotEquals(expected: $productFixture->getSku(), actual: $otherProductFixture->getSku());
        $this->products[] = $otherProductFixture;
    }

    public function testRandomSkuOnBuildWithoutSave(): void
    {
        $product = ProductBuilder::aSimpleProduct()->buildWithoutSave();
        $this->assertMatchesRegularExpression(pattern: '/[0-9a-f]{32}/', string: $product->getSku());

        $otherProduct = ProductBuilder::aSimpleProduct()->buildWithoutSave();
        $this->assertMatchesRegularExpression(pattern: '/[0-9a-f]{32}/', string: $otherProduct->getSku());
        $this->assertNotEquals(expected: $product->getSku(), actual: $otherProduct->getSku());
    }

    public function testProductCanBeLoadedWithCollection(): void
    {
        $productFixture = new ProductFixture(
            product: ProductBuilder::aSimpleProduct()->build(),
        );
        $this->products[] = $productFixture;
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->create(type: SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter(field: 'sku', value: $productFixture->getSku());
        $productsFromCollection = $this->productRepository->getList(
            searchCriteria: $searchCriteriaBuilder->create(),
        )->getItems();
        $this->assertCount(
            expectedCount: 1,
            haystack: $productsFromCollection,
            message: 'The product should be able to be loaded from collection',
        );
    }

    public function testVirtualProduct_withTierPrices(): void
    {
        $productBuilder = ProductBuilder::aVirtualProduct();
        $productBuilder = $productBuilder->withPrice(price: 25.99);
        $productBuilder = $productBuilder->withTierPrices(tierPrices: [
            ['price' => 15.99, 'qty' => 1, 'customer_group_id' => Group::NOT_LOGGED_IN_ID],
            ['price' => 14.99, 'qty' => 1, 'customer_group_id' => Group::CUST_GROUP_ALL],
            ['price' => 13.99, 'qty' => 5, 'customer_group_id' => Group::NOT_LOGGED_IN_ID],
            ['price' => 12.99, 'qty' => 5, 'customer_group_id' => Group::CUST_GROUP_ALL],
            ['price' => 11.99, 'qty' => 10, 'customer_group_id' => Group::NOT_LOGGED_IN_ID],
            ['price' => 10.99, 'qty' => 10, 'customer_group_id' => Group::CUST_GROUP_ALL],

        ]);
        $productFixture = new ProductFixture(
            product: $productBuilder->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $product->setData(key: 'customer_group_id', value: Group::NOT_LOGGED_IN_ID);

        $this->assertEquals(expected: Type::TYPE_VIRTUAL, actual: $product->getTypeId());
        $this->assertEquals(expected: 'TDD Test Virtual Product', actual: $product->getName());
        $this->assertEquals(expected: [1], actual: $product->getWebsiteIds());
        $this->assertTrue(
            condition: $product->getExtensionAttributes()->getStockItem()->getIsInStock(),
        );
        $this->assertEquals(expected: 15.99, actual: $product->getFinalPrice(qty: 1));
    }

    public function testDefaultDownloadableProduct(): void
    {
        $productFixture = new ProductFixture(
            product: ProductBuilder::aDownloadableProduct()->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $this->assertEquals(expected: DownloadableType::TYPE_DOWNLOADABLE, actual: $product->getTypeId());
        $this->assertEquals(expected: 'TDD Test Downloadable Product', actual: $product->getName());
        $this->assertEquals(expected: [1], actual: $product->getWebsiteIds());

        $extensionAttributes = $product->getExtensionAttributes();
        $this->assertTrue(
            condition: $extensionAttributes->getStockItem()->getIsInStock(),
        );

        $productLinks = $extensionAttributes->getDownloadableProductLinks();
        $this->assertCount(expectedCount: 1, haystack: $productLinks);
        $productLink = array_shift(array: $productLinks);
        $this->assertSame(expected: 'https://magento.test/', actual: $productLink->getLinkUrl());
        $this->assertSame(expected: 'Downloadable Item', actual: $productLink->getTitle());
        $this->assertEquals(expected: 54.99, actual: $productLink->getPrice());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     */
    public function testConfigurableProduct_withSpecialPrices(): void
    {
        $this->createAttribute(attributeData: [
            'attribute_type' => 'configurable',
            'code' => 'tdd_configurable_attribute',
            'key' => 'tdd_configurable_attribute',
        ]);
        $attributeFixture1 = $this->attributeFixturePool->get('tdd_configurable_attribute');

        $this->createAttribute(attributeData: [
            'attribute_type' => 'configurable',
            'code' => 'tdd_configurable_attribute_2',
            'key' => 'tdd_configurable_attribute_2',
        ]);
        $attributeFixture2 = $this->attributeFixturePool->get('tdd_configurable_attribute_2');

        $simpleProductBuilder1 = ProductBuilder::aSimpleProduct();
        $simpleProductBuilder1 = $simpleProductBuilder1->withVisibility(visibility: Visibility::VISIBILITY_NOT_VISIBLE);
        $simpleProductBuilder1 = $simpleProductBuilder1->withSku(sku: 'TDD_TEST_SIMPLE_001');
        $simpleProductBuilder1 = $simpleProductBuilder1->withPrice(price: 24.99);
        $simpleProductBuilder1 = $simpleProductBuilder1->withData(data: [
            'special_price' => 15.99,
            'special_price_from' => '1970-01-01',
            'special_price_to' => '2099-12-31',
        ]);
        $simpleProductBuilder1 = $simpleProductBuilder1->withData(data: [
            $attributeFixture1->getAttributeCode() => '1',
            $attributeFixture2->getAttributeCode() => '5',
        ]);
        $simpleProductFixture1 = new ProductFixture(
            product: $simpleProductBuilder1->build(),
        );

        $simpleProductBuilder2 = ProductBuilder::aSimpleProduct();
        $simpleProductBuilder2 = $simpleProductBuilder2->withVisibility(visibility: Visibility::VISIBILITY_NOT_VISIBLE);
        $simpleProductBuilder2 = $simpleProductBuilder2->withSku(sku: 'TDD_TEST_SIMPLE_002');
        $simpleProductBuilder2 = $simpleProductBuilder2->withPrice(price: 19.99);
        $simpleProductBuilder2 = $simpleProductBuilder2->withData(data: [
            'special_price' => 18.99,
            'special_price_from' => '1970-01-01',
            'special_price_to' => '2099-12-31',
        ]);
        $simpleProductBuilder2 = $simpleProductBuilder2->withData(data: [
            $attributeFixture1->getAttributeCode() => '2',
            $attributeFixture2->getAttributeCode() => '3',
        ]);
        $simpleProductFixture2 = new ProductFixture(
            product: $simpleProductBuilder2->build(),
        );

        $configurableProductBuilder = ProductBuilder::aConfigurableProduct();
        $configurableProductBuilder = $configurableProductBuilder->withVisibility(
            visibility: Visibility::VISIBILITY_BOTH,
        );
        $configurableProductBuilder = $configurableProductBuilder->withSku(
            sku: 'TDD_TEST_CONFIGURABLE',
        );
        $configurableProductBuilder = $configurableProductBuilder->withConfigurableAttribute(
            attribute: $attributeFixture1->getAttribute(),
        );
        $configurableProductBuilder = $configurableProductBuilder->withConfigurableAttribute(
            attribute: $attributeFixture2->getAttribute(),
        );
        $configurableProductBuilder = $configurableProductBuilder->withVariant(
            variantProduct: $simpleProductFixture1->getProduct(),
        );
        $configurableProductBuilder = $configurableProductBuilder->withVariant(
            variantProduct: $simpleProductFixture2->getProduct(),
        );
        $configurableProductFixture = new ProductFixture(
            product: $configurableProductBuilder->build(),
        );

        $configurableProduct = $configurableProductFixture->getProduct();
        /** @var Configurable $configurableProductType */
        $configurableProductType = $configurableProduct->getTypeInstance();
        $childIdsArray = $configurableProductType->getChildrenIds(parentId: (int)$configurableProduct->getId());
        $this->assertIsArray(actual: $childIdsArray);
        $childIds = array_shift(array: $childIdsArray);
        $this->assertIsArray(actual: $childIds);
        $this->assertArrayHasKey(key: $simpleProductFixture1->getId(), array: $childIds);
        $this->assertSame(
            expected: (string)$simpleProductFixture1->getId(),
            actual: $childIds[$simpleProductFixture1->getId()],
        );
        $this->assertArrayHasKey(key: $simpleProductFixture2->getId(), array: $childIds);
        $this->assertSame(
            expected: (string)$simpleProductFixture2->getId(),
            actual: $childIds[$simpleProductFixture2->getId()],
        );
        $this->assertEquals(expected: 15.99, actual: $configurableProduct->getFinalPrice(qty: 1));
    }

    public function testGroupedProduct_withSpecialPrices(): void
    {
        $simpleProductBuilder1 = ProductBuilder::aSimpleProduct();
        $simpleProductBuilder1 = $simpleProductBuilder1->withVisibility(visibility: Visibility::VISIBILITY_NOT_VISIBLE);
        $simpleProductBuilder1 = $simpleProductBuilder1->withSku(sku: 'TDD_TEST_SIMPLE_001');
        $simpleProductBuilder1 = $simpleProductBuilder1->withStatus(status: Status::STATUS_ENABLED);
        $simpleProductBuilder1 = $simpleProductBuilder1->withPrice(price: 18.99);
        $simpleProductBuilder1 = $simpleProductBuilder1->withData(data: [
            'special_price' => 15.99,
            'special_price_from' => '1970-01-01',
            'special_price_to' => '2099-12-31',
        ]);
        $simpleProductFixture1 = new ProductFixture(
            product: $simpleProductBuilder1->build(),
        );

        $simpleProductBuilder2 = ProductBuilder::aSimpleProduct();
        $simpleProductBuilder2 = $simpleProductBuilder2->withVisibility(visibility: Visibility::VISIBILITY_NOT_VISIBLE);
        $simpleProductBuilder2 = $simpleProductBuilder2->withSku(sku: 'TDD_TEST_SIMPLE_002');
        $simpleProductBuilder2 = $simpleProductBuilder2->withStatus(status: Status::STATUS_ENABLED);
        $simpleProductBuilder2 = $simpleProductBuilder2->withPrice(price: 19.99);
        $simpleProductBuilder2 = $simpleProductBuilder2->withData(data: [
            'special_price' => 14.99,
            'special_price_from' => '1970-01-01',
            'special_price_to' => '2099-12-31',
        ]);
        $simpleProductFixture2 = new ProductFixture(
            product: $simpleProductBuilder2->build(),
        );

        $simpleProductBuilder3 = ProductBuilder::aSimpleProduct();
        $simpleProductBuilder3 = $simpleProductBuilder3->withVisibility(visibility: Visibility::VISIBILITY_NOT_VISIBLE);
        $simpleProductBuilder3 = $simpleProductBuilder3->withSku(sku: 'TDD_TEST_SIMPLE_003');
        $simpleProductBuilder3 = $simpleProductBuilder3->withStatus(status: Status::STATUS_DISABLED);
        $simpleProductBuilder3 = $simpleProductBuilder3->withPrice(price: 3.99);
        $simpleProductBuilder3 = $simpleProductBuilder3->withData(data: [
            'special_price' => 2.99,
            'special_price_from' => '1970-01-01',
            'special_price_to' => '2099-12-31',
        ]);
        $simpleProductFixture3 = new ProductFixture(
            product: $simpleProductBuilder3->build(),
        );

        $groupedProductBuilder = ProductBuilder::aGroupedProduct();
        $groupedProductBuilder = $groupedProductBuilder->withVisibility(visibility: Visibility::VISIBILITY_BOTH);
        $groupedProductBuilder = $groupedProductBuilder->withSku(sku: 'TDD_TEST_GROUPED_001');
        $groupedProductBuilder = $groupedProductBuilder->withStatus(status: Status::STATUS_ENABLED);
        $groupedProductBuilder = $groupedProductBuilder->withPrice(price: 25.99);
        $groupedProductBuilder = $groupedProductBuilder->withData(data: [
            'special_price' => 18.99,
            'special_price_from' => '1970-01-01',
            'special_price_to' => '2099-12-31',
        ]);
        $groupedProductBuilder = $groupedProductBuilder->withLinkedProduct(
            linkedProduct: $simpleProductFixture1->getProduct(),
        );
        $groupedProductBuilder = $groupedProductBuilder->withLinkedProduct(
            linkedProduct: $simpleProductFixture2->getProduct(),
        );
        $groupedProductBuilder = $groupedProductBuilder->withLinkedProduct(
            linkedProduct: $simpleProductFixture3->getProduct(),
        );
        $groupedProductFixture = new ProductFixture(
            product: $groupedProductBuilder->build(),
        );
        $groupedProduct = $groupedProductFixture->getProduct();

        $this->assertSame(expected: 'TDD_TEST_GROUPED_001', actual: $groupedProduct->getSku());
        $minimumPricedProduct = $this->getGroupedMinimumPriceProduct(groupedProduct: $groupedProduct);
        $this->assertSame(expected: 14.99, actual: $minimumPricedProduct->getFinalPrice(qty: 1));
    }

    public function testTierPricesLowerThenSpecialPrice(): void
    {
        $productBuilder = ProductBuilder::aSimpleProduct();
        $productBuilder = $productBuilder->withPrice(price: 25.99);
        $productBuilder = $productBuilder->withTierPrices(tierPrices: [
            ['price' => 15.99, 'qty' => 1, 'customer_group_id' => Group::NOT_LOGGED_IN_ID],
            ['price' => 14.99, 'qty' => 1, 'customer_group_id' => Group::CUST_GROUP_ALL],
            ['price' => 13.99, 'qty' => 5, 'customer_group_id' => Group::NOT_LOGGED_IN_ID],
            ['price' => 12.99, 'qty' => 5, 'customer_group_id' => Group::CUST_GROUP_ALL],
            ['price' => 11.99, 'qty' => 10, 'customer_group_id' => Group::NOT_LOGGED_IN_ID],
            ['price' => 10.99, 'qty' => 10, 'customer_group_id' => Group::CUST_GROUP_ALL],
        ]);
        $productBuilder = $productBuilder->withData(data: [
            'special_price' => 18.99,
            'special_price_from' => '1970-01-01',
            'special_price_to' => '2099-12-31',
        ]);
        $productFixture = new ProductFixture(
            product: $productBuilder->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $product->setData(key: 'customer_group_id', value: Group::NOT_LOGGED_IN_ID);

        $this->assertEquals(expected: 15.99, actual: $product->getFinalPrice(qty: 1));
        $this->assertEquals(expected: 13.99, actual: $product->getFinalPrice(qty: 5));
        $this->assertEquals(expected: 11.99, actual: $product->getFinalPrice(qty: 10));
    }

    public function testSpecialPriceLowerThenTierPrices(): void
    {
        $productBuilder = ProductBuilder::aSimpleProduct();
        $productBuilder = $productBuilder->withPrice(price: 25.99);
        $productBuilder = $productBuilder->withTierPrices(tierPrices: [
            ['price' => 15.99, 'qty' => 1, 'customer_group_id' => Group::NOT_LOGGED_IN_ID],
            ['price' => 14.99, 'qty' => 1, 'customer_group_id' => Group::CUST_GROUP_ALL],
            ['price' => 13.99, 'qty' => 5, 'customer_group_id' => Group::NOT_LOGGED_IN_ID],
            ['price' => 12.99, 'qty' => 5, 'customer_group_id' => Group::CUST_GROUP_ALL],
            ['price' => 11.99, 'qty' => 10, 'customer_group_id' => Group::NOT_LOGGED_IN_ID],
            ['price' => 10.99, 'qty' => 10, 'customer_group_id' => Group::CUST_GROUP_ALL],
        ]);
        $productBuilder = $productBuilder->withData(data: [
            'special_price' => 9.99,
            'special_price_from' => '1970-01-01',
            'special_price_to' => '2099-12-31',
        ]);
        $productFixture = new ProductFixture(
            product: $productBuilder->build(),
        );
        $this->products[] = $productFixture;
        /** @var Product $product */
        $product = $this->productRepository->getById(productId: $productFixture->getId());
        $product->setData(key: 'customer_group_id', value: Group::NOT_LOGGED_IN_ID);

        $this->assertEquals(expected: 9.99, actual: $product->getFinalPrice(qty: 1));
        $this->assertEquals(expected: 9.99, actual: $product->getFinalPrice(qty: 5));
        $this->assertEquals(expected: 9.99, actual: $product->getFinalPrice(qty: 10));
    }

    /**
     * Magento return type hint for $price->getMinProduct is Product,
     *  though it can return null if all child products are disabled.
     */
    private function getGroupedMinimumPriceProduct(ProductInterface $groupedProduct): ?ProductInterface
    {
        if (!(method_exists($groupedProduct, 'getPriceInfo'))) {
            throw new \LogicException(
                sprintf(
                    'Method getPriceInfo does not exists on product object %s',
                    $groupedProduct::class,
                ),
            );
        }
        /** @var BasePriceInfo $priceInfo */
        $priceInfo = $groupedProduct->getPriceInfo();
        /** @var FinalPrice $price */
        $price = $priceInfo->getPrice(FinalPrice::PRICE_CODE);
        if (!(method_exists($price, 'getMinProduct'))) {
            return null;
        }

        return $price->getMinProduct();
    }
}
