# Category Fixtures

## Defaults

```php
[
    'key' => 'tdd_category',
    'parent' => null,
    'root_id' => null,
    'name' => 'Top Level Category',
    'is_active' => true,
    'description' => null,
    'url_key' => null,
    'is_anchor' => null,
    'display_mode' => null,
    'products' => [],
    'custom_attributes' => [],
    'image' => null,
    'store_id' => null,
]
```

If neither `parent` is set or `root_id` is set to 1,
then a top level category will be created (i.e. category level = 2).
In the magento sample data, examples of top level categories would be Men, Women, and Gear.  
When creating these top level categories, if `root_id` is not set then the following data will be set

```php
'parent_id' => 2 // or whatever the lowest root category id in the database is
'path' => '1/2', // or whatever the lowest root category id in the database is
```

If `parent` is set then the category created will be a child of the category passed in `parent`.

If `root_id` is set to 1, then a new root category will be created (i.e. category level = 1).

## Build With Trait

```php
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\Category\CategoryFixturesPool;
use TddWizard\Fixtures\Catalog\Category\CategoryTrait;
use TddWizard\Fixtures\Store\StoreFixturesPool;
use TddWizard\Fixtures\Store\StoreTrait;

class SomeTest extends TestCase
{
    use CategoryTrait;
    use StoreTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->storeFixturePool = $this->objectManager->create(StoreFixturesPool::class);
        $this->categoryFixturePool = $this->objectManager->create(CategoryFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->attributeFixturePool->rollback();
        $this->storeFixturePool->rollback();
    }
    
    public function testSomething_withDefaultCategoryValues(): void
    {
        $this->createCategory();
        $categoryFixture = $this->categoryFixturePool->get('tdd_category');
        ...
    }
    
    public function testSomething_withCustomCategoryValues(): void 
    {
        $this->createStore([
            'key' => 'tdd_store_1',
        ]);
        $storeFixture1 = $this->storeFixturePool->get('tdd_store_1');
        $this->createStore([
            'key' => 'tdd_store_2',
            'code' => 'tdd_store_2',
        ]);
        $storeFixture2 = $this->storeFixturePool->get('tdd_store_2');
        
        $this->createCategory([
            'key' => 'some_key',
            'root_id' => 2,
            'name' => 'TDD Top Level Category',
            'is_active' => true,
            'description' => 'TDD Top Level Category Description',
            'url_key' => 'tdd-test-top-level-category',
            'is_anchor' => true,
            'display_mode' => 'PRODUCTS',
            'image' => 'image3.png',
            'products' => [
                'PRODUCT_SKU_001',
                'PRODUCT_SKU_002',
                'PRODUCT_SKU_003',
            ],
            'custom_attributes' => [
                'meta_description' => 'Meta Description',
            ],
            'stores' => [
                $storeFixture1->getId() => [
                    'name' => 'Store 1 Name',
                    'url_key' => 'tdd-category-store-1',
                    'custom_attributes' => [
                        'meta_title' => 'Meta Title Store 1',
                    ],
                ],
                $storeFixture2->getId() => [
                    'name' => 'Store 1 Name',
                    'url_key' => 'tdd-category-store-2',
                    'is_active' => false,
                ],
            ],
        ]);
        $categoryFixture = $this->categoryFixturePool->get('some_key');
        ...
    }
    
    public function testSomething_withMultipleCategories(): void
    {
        $this->createCategory([
            'key' => 'tdd_parent_category',
            'name' => 'TDD Parent Category',
        ]);
        $parentCategoryFixture = $this->categoryFixturePool->get('tdd_parent_category');
        
        $this->createCategory([
            'key' => 'tdd_child_category',
            'name' => 'TDD Child Category',
            'parent' => $categoryFixture1->getCategory(),
        ]);
        $childCategoryFixture = $this->categoryFixturePool->get('tdd_child_category');
        ...
    }
}
```

## Build Without Trait

Build with defaults

Root Category

```php
$categoryBuilder = CategoryBuilder::rootCategory(); 
$categoryBuilder->build();
```

Top Level Category

```php
$categoryBuilder = CategoryBuilder::topLevelCategory(
    rootCategoryId: 2,
); 
$categoryBuilder->build();
```

Child Category

```php
$categoryBuilder = CategoryBuilder::childCategoryOf(
    parent: $category, // CategoryInterface
); 
$categoryBuilder->build();
```

Build with custom values

```php
$categoryBuilder = CategoryBuilder::topLevelCategory(
    rootCategoryId: 2,
);
$categoryBuilder = $categoryBuilder->withName('TDD Top Level Category');
$categoryBuilder = $categoryBuilder->withName('TDD Top Level Category Store 1', $storeFixture1->getId());
$categoryBuilder = $categoryBuilder->withName('TDD Top Level Category Store 2', $storeFixture2->getId());
$categoryBuilder = $categoryBuilder->withUrlKey('tdd-top-level-category');
$categoryBuilder = $categoryBuilder->withUrlKey('tdd-top-level-category-store-1', $storeFixture1->getId());
$categoryBuilder = $categoryBuilder->withUrlKey('tdd-top-level-category-store-2', $storeFixture2->getId());
$categoryBuilder = $categoryBuilder->withDescription('Description');
$categoryBuilder = $categoryBuilder->withDescription('Description Store 1', $storeFixture1->getId());
$categoryBuilder = $categoryBuilder->withDescription('Description Store 2', $storeFixture2->getId());
$categoryBuilder = $categoryBuilder->withIsActive(true);
$categoryBuilder = $categoryBuilder->withIsActive(false, $storeFixture1->getId());
$categoryBuilder = $categoryBuilder->withIsActive(true, $storeFixture2->getId());
$categoryBuilder = $categoryBuilder->withIsAnchor(true);
$categoryBuilder = $categoryBuilder->withDisplayMode('PRODUCTS');
$categoryBuilder = $categoryBuilder->withImage('image2.png');
$categoryBuilder = $categoryBuilder->withProducts([
    'PRODUCT_SKU_001',
    'PRODUCT_SKU_002',
]);
$categoryBuilder = $categoryBuilder->withCustomAttributes([
    'meta_title' => 'Meta Title',
    'meta_description' => 'Meta Description',
]);
$categoryBuilder = $categoryBuilder->withCustomAttributes(
    [
        'meta_title' => 'Meta Title Store 1',
        'meta_description' => 'Meta Description Store 1',
    ], 
    $storeFixture1->getId(),
);
$categoryBuilder->build();
```

Add to fixture pool and tag it for easy recall.

```php
$categoryBuilder = CategoryBuilder::topLevelCategory(2);
...
$this->categoryFixturePool->add(
    $categoryBuilder->build(),
    'tdd_category'
);
```

Retrieve from fixture pool using tag/key

```php
$categoryFixture = $this->categoryFixturePool->get('tdd_category');
```

---
