# Product Fixtures

## Defaults

### `ProductBuilder::aSimpleProduct()`

```php
[
    'key' => 'tdd_product',
    'type_id' => 'simple',
    'sku' => sha1(uniqid(prefix: '', more_entropy: true)),
    'name' => 'TDD Test Simple Product',
    'status' => Status::STATUS_ENABLED,
    'visibility' => Visibility::VISIBILITY_BOTH,
    'description' => 'Description',
    'url_key' => strtolower(str_replace( ' ', replace: '_', subject: $sku)),
    'qty' => 100,
    'is_in_stock' => true,
    'manage_stock' => true,
    'qty_is_decimal' => false,
    'price' => 10.00,
    'tax_class_id' => 1,
    'attribute_set_id' => 4,
]
```

### `ProductBuilder::aVirtualProduct()`

As `aSimpleProduct` with the following changes

```php
[
    'type' => 'virtual',
    'name' => 'TDD Test Virtual Product',
]
```

### `ProductBuilder::aDownloadableProduct()`

As `aSimpleProduct` with the following changes

```php
[
    'type' => 'downloadable',
    'name' => 'TDD Test Downloadable Product',
]
```

### `ProductBuilder::aGroupedProduct()`

As `aSimpleProduct` with the following changes

```php
[
    'type' => 'grouped',
    'name' => 'TDD Test Grouped Product',
]
```

When creating a grouped product, we will likely also wish to link some simple products.

```php

$simpleProductBuilder = ProductBuilder::aSimpleProduct();
$simpleProduct = $simpleProductBuilder->build();

$groupedProductBuilder = ProductBuilder::aGroupedProduct();
$groupedProductBuilder = $groupedProductBuilder->withLinkedProduct(
    linkedProduct: $simpleProduct,
);
```

### `ProductBuilder::aConfigurableProduct()`

As `aSimpleProduct` with the following changes

```php
[
    'type' => 'configurable',
    'name' => 'TDD Test Configurable Product',
    'price' => null, // unsetData('price')
]
```

When creating a grouped product, we will likely also wish to link some simple products.
We will also need to specify the attributes to be used to create the configurable product.

```php
$attributeBuilder = AttributeBuilder::aProductAttribute(
    attributeCode: 'tdd_configurable_attribute',
    attributeType: 'configurable',
);
$attribute = $attributeBuilder->build();

$simpleProductBuilder = ProductBuilder::aSimpleProduct();
$simpleProductBuilder->withCustomAttributes(values: [
    $attribute->getAttributeCode => '1',
]);
$simpleProduct = $simpleProductBuilder->build();

$configurableProductBuilder = ProductBuilder::aConfigurableProduct();
$configurableProductBuilder = $productBuilder->withConfigurableAttribute(attribute: $attribute,);
$configurableProductBuilder->withVariant(variantProduct: $simpleProduct);

```

## Build With Trait

When building via the `ProductTrait` we can pass the following array keys to `$this->createProduct(productData: [])`.
All keys are optional, unless creating multiple products then a separate key must to be passed for each
to avoid a clash in the `productFixturePool`.

```php
$this->createProduct(
    productData: [
        'key' => 'tdd_simple_product',
        'type_id' => 'simple', // or virtual, downloadable, grouped, configurable
        'sku' => 'PRODUCT_SKU_001',
        'name' => 'TDD Test Product 001',
        'status' => Status::STATUS_ENABLED,
        'visibility' => Visibility::VISIBILITY_BOTH,
        'is_in_stock' => true,
        'qty' => 10,
        'is_qty_decimal' => false,
        'manage_stock' => true,
        'backorders' => Stock::BACKORDERS_YES_NOTIFY,
        'price' => 23.99,
        'tax_class_id' => 1,
        'tier_prices' => [
            ['price' => 20.00, 'qty' => 1, 'customer_group' => 1],
            ['price' => 18.00, 'qty' => 5, 'customer_group' => 1],
            ['price' => 15.00, 'qty' => 1, 'customer_group' => 2],
            ['price' => 13.00, 'qty' => 5, 'customer_group' => 2],
        ],
        'images' => [
            'image' => [
                'fileName' => 'image.png',
                'path' => 'path/to/images',
            ],
            'small_image' => [
                'fileName' => 'small_image.jpg',
                'path' => 'path/to/images',
                'mimeType' => 'image/jpg',
            ],
            'thumbnail' => 'image1.png', // path assumed to be _files/images
        ],
        'category_ids' => [3, 4, 5, 6],
        'website_ids' => [$store1->getWebsiteId(), $store2->getWebsiteId()],
        'custom_attributes' => [ // can also use 'data' => [] to set these values
            'description' => 'Product description',
            'short_description' => 'Prod desc',
            'special_price' => 17.50,
            'special_price_from' => '1970-01-01',
            'special_price_to' => '2099-12-31',
        ],
        'stores' => [
            $store1->getId() => [
                'name' => 'My Product 001 Store 1',
                'custom_attributes' => [
                    'description' => 'Product description Store 1',
                    'short_description' => 'Prod desc Str 1',
                ],
                'visibility' => Visibility::VISIBILITY_IN_CATALOG,
            ],
            $store2->getId() => [
                'name' => 'My Product 001 Store 2',
                'custom_attributes' => [
                    'description' => 'Product description Store 2',
                    'short_description' => 'Prod desc Str 2',
                ],
                'status' => Status::STATUS_DISABLED,
            ],
        ],
    ],
);
$productFixture = $this->productFixturePool->get('tdd_simple_product');
```

### Example Test Class

```php
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\Product\AttributeFixturesPool;
use TddWizard\Fixtures\Catalog\Product\AttributeTrait;
use TddWizard\Fixtures\Catalog\Product\ProductFixturesPool;
use TddWizard\Fixtures\Catalog\Product\ProductTrait;
use TddWizard\Fixtures\Store\StoreFixturesPool;
use TddWizard\Fixtures\Store\StoreTrait;

class SomeTest extends TestCase
{
    use AttributeTrait;
    use ProductTrait;
    use StoreTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->storeFixturePool = $this->objectManager->create(StoreFixturesPool::class);
        $this->attributeFixturePool = $this->objectManager->create(AttributeFixturesPool::class);
        $this->productFixturePool = $this->objectManager->create(ProductFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->productFixturePool->rollback();
        $this->attributeFixturePool->rollback();
        $this->storeFixturePool->rollback();
    }

    public function testSomething_withDefaultSimpleProduct(): void
    {
        $this->createProduct();
        $productFixture = $this->productFixturePool->get('tdd_product');
        ...
    }

    public function testSomething_withDefaultVirtualProduct(): void
    {
        $this->createProduct([
            'type_id' => 'virtual',
        ]);
        $productFixture = $this->productFixturePool->get('tdd_product');
        ...
    }

    public function testSomething_withGroupedProduct(): void
    {
        $this->createProduct();
        $simpleProductFixture = $this->productFixturePool->get('tdd_product');
        
        $this->createProduct([
            'key' => 'tdd_grouped_product',
            'type_id' => 'grouped',
            'linked_products' => [
                $simpleProductFixture->getProduct(),
            ],
        ]);
        $groupedProductFixture = $this->productFixturePool->get('tdd_grouped_product');
        ...
    }

    public function testSomething_withConfigurableProduct(): void
    {
        $this->createAttribute(
            'attribute_type' => 'configurable',
        );
        $attributeFixture = $this->attributeFixturePool->get('tdd_attribute');
    
        $this->createProduct([
            'custom_attributes' => [
                $attributeFixture->getAttributeCode => '1',
            ],
        ]);
        $simpleProductFixture1 = $this->productFixturePool->get('tdd_product');

        $this->createProduct([
            'key' => 'tdd_product_2',
            'custom_attributes' => [
                $attributeFixture->getAttributeCode => '2',
            ],
        ]);
        $simpleProductFixture2 = $this->productFixturePool->get('tdd_product_2');

        $this->createProduct([
            'key' => 'tdd_configurable_product',
            'type_id' => 'configurable',
            'configurable_attributes' => [
                $attributeFixture->getAttribute(),
            ],
            'variants' => [
                $simpleProductFixture1->getProduct(),
                $simpleProductFixture2->getProduct(),
            ],
        ]);
        $configurableProductFixture = $this->productFixturePool->get('tdd_configurable_product');
        ...
    }

    public function testSomething_withStoreScopeValues(): void
    {
        $this->createStore();
        $storeFixture1 = $this->storeFixturePool->get('tdd_store');
        $this->createStore([
            'key' => 'tdd_store_2',
            'code' => 'tdd_store_2',
        ]);
        $storeFixture2 = $this->storeFixturePool->get('tdd_store_2');
        
        $this->createProduct([
            'name' => 'Global Name',
            'status' => 1,
            'visibility' => 4,
            'custom_attributes' => [
                'description' => 'Global Description',
            ],
            'stores' => [
                $storeFixture1->getId() => [
                    'name' => 'Store 1 Name',
                    'visibility' => 2,
                    'custom_attributes' => [
                        'description' => 'Store 1 Description',
                    ],
                ],
                $storeFixture2->getId() => [
                    'name' => 'Store 2 Name',
                    'status' => 2,
                    'visibility' => 3,
                    'custom_attributes' => [
                        'description' => 'Store 2 Description',
                    ],
                ],
            ],
        ]);
        $productFixture = $this->productFixturePool->get('tdd_product');
        ...
    }
```

### Build Without Trait

```php
protected function setUp(): void
{
  $this->productFixture = new ProductFixture(
    ProductBuilder::aSimpleProduct()
      ->withPrice(10)
      ->withCustomAttributes(
        [
          'my_custom_attribute' => 42
        ]
      )
      ->build()
  );
}
protected function tearDown(): void
{
  $this->productFixture->rollback();
}
```

The SKU is randomly generated and can be accessed through `ProductFixture`, just as the ID:

```php
$this->productFixture->getSku();
$this->productFixture->getId();
```
