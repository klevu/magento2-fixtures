# Attribute Fixtures

## Attribute

### Defaults

Some of these defaults are only set when using `AttributeTrait`.

```php
[
    'key' => 'tdd_attribute',
    'code' => 'tdd_attribute',
    'attribute_type' => 'text',
    'entity_type' => 'catalog_product', // or catalog_category
    'label' => 'Tdd Attribute',
    'store_labels' => [],
    'data' => [],
    'options' => [ // if attribute type requires options (i.e. attribute type is select, multiselect or configurable)
        '1' => 'Option 1',
        '2' => 'Option 2',
        '3' => 'Option 3',
        '4' => 'Option 4',
        '5' => 'Option 5',
    ],
    'attribute_set' => 'Default', // name or id
    'attribute_group' => 'General', // name or id
]
```

Setting the `attribute_type` will cause the following values to be set for the attribute.

| attribute_type | Frontend Input | Backend Type | Backend Model                                     | Is Global |
|----------------|----------------|--------------|---------------------------------------------------|-----------|
| text           | text           | varchar      | -                                                 | -         |
| textarea       | textarea       | text         | -                                                 | -         |
| configurable   | select         | int          | -                                                 | true      |
| enum           | select         | int          | -                                                 | -         |
| select         | select         | varchar      | -                                                 | -         |
| multiselect    | multiselect    | text         | -                                                 | -         |
| boolean        | boolean        | int          | -                                                 | -         |
| yes_no         | boolean        | int          | Magento\Eav\Model\Entity\Attribute\Source\Boolean | -         |
| date           | date           | datetime     | -                                                 | -         |
| price          | price          | decimal      | -                                                 | -         |
| image          | media_image    | varchar      | -                                                 | -         |
| weee           | weee           | static       | Magento\Weee\Model\Attribute\Backend\Weee\Tax     | -         |

If the required combination is not present then `attribute_type` can be set to an empty string and the values set via
the `data`
array.  
e.g.  
via `AttributeTrait`

```php
$this->createAttribute([
    'attribute_type' => '',
    'data' => [
        'frontend_input' => 'select',
        'backend_type' => 'varchar',
        'backend_model' => \Vendor\Module\Model\Attribute\Source\MyAttribute::class,
        'is_global' => true,
    ]
]);
```

via `AttributeBuilder`

```php
$builder = AttributeBuilder::addProductAttribute(
    attributeCode: 'tdd_attribute_code',
    attributeType: '',
);
$builder->withAttributeData([
    'frontend_input' => 'select',
    'backend_type' => 'varchar',
    'backend_model' => \Vendor\Module\Model\Attribute\Source\MyAttribute::class,
    'is_global' => true,
]);
```

### Build With Trait

```php
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\Attribute\AttributeFixturesPool;
use TddWizard\Fixtures\Catalog\Attribute\AttributeTrait;
use TddWizard\Fixtures\Store\StoreFixturesPool;
use TddWizard\Fixtures\Store\StoreTrait;

class SomeTest extends TestCase
{
    use AttributeTrait;
    use StoreTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->storeFixturePool = $this->objectManager->create(StoreFixturesPool::class);
        $this->attributeFixturePool = $this->objectManager->create(AttributeFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->attributeFixturePool->rollback();
        $this->storeFixturePool->rollback();
    }
    
    public function testSomething_withDefaultAttributeValues(): void
    {
        $this->createAttribute();
        $attributeFixture = $this->attributeFixturePool->get('tdd_attribute');
        ...
    }
    
    public function testSomething_withCustomAttributeValues(): void 
    {
        $this->createStore();
        $storeFixture1 = $this->storeFixturePool->get('tdd_store_1');
        $this->createStore([
            'key' => 'tdd_store_2',
            'code' => 'tdd_store_2',
        ]);
        $storeFixture2 = $this->storeFixturePool->get('tdd_store_2');
        
        $this->createAttribute([
            'key' => 'some_key',
            'code' => 'tdd_test_attribute_configurable',
            'attribute_type' => 'configurable',
            'label' => 'Tdd Configurable Attribute',
            'store_labels' => [
                $storeFixture1->getId() => 'Store 1 Label',
                $storeFixture2->getId() => 'Store 2 Label',
            ],
            'data' => [
                'is_required' => 1,
                'used_in_product_listing' => 1,
                'is_filterable' => 1,
            ],
            'options' => [ 
                '1' => 'Variant 1',
                '2' => 'Variant 2',
                '3' => 'Variant 3',
            ],
            'attribute_set' => 'Custom Attribute Set', // name or id
            'attribute_group' => 3, // name or id
        ]);
        $attributeFixture = $this->attributeFixturePool->get('some_key');
        ...
    }
    
    public function testSomething_withMultipleAttributes(): void
    {
        $this->createAttribute();
        $attributeFixture1 = $this->attributeFixturePool->get('tdd_attribute');
        
        $this->createAttribute([
            'key' => 'tdd_attribute_2',
            'code' => 'tdd_attribute_2',
        ]);
        $attributeFixture2 = $this->attributeFixturePool->get('tdd_attribute_2');
        ...
    }
}
```

### Build Without Trait

Build with defaults

```php
$attributeBuilder = AttributeBuilder::addProductAttribute( // or AttributeBuilder::addCategoryAttribute()
    attributeCode: 'tdd_attribute_code',
    attributeType: 'text',
); 
$attributeBuilder->build();
```

Build with custom values

```php
$attributeBuilder = AttributeBuilder::addProductAttribute(
    attributeCode: 'tdd_attribute_code',
    attributeType: 'text',
);
$attributeBuilder = $attributeBuilder->withLabel('TDD Attribute Label');
$attributeBuilder = $attributeBuilder->withLabels([
    $storeFixture1->getId() => 'Store 1 Label',
    $storeFixture2->getId() => 'Store 2 Label',
]);
$attributeBuilder = $attributeBuilder->withAttributeData([
    'is_required' => 1,
    'used_in_product_listing' => 1,
    'is_filterable' => 1,
]);
$attributeBuilder = $attributeBuilder->withAttributeSet('Custom Attribute Set'); // name or id
$attributeBuilder = $attributeBuilder->withAttributeGroup(3); // name or id
$attributeBuilder = $attributeBuilder->withOptions([
    '1' => 'Variant 1',
    '2' => 'Variant 2',
    '3' => 'Variant 3',
]);
$attributeBuilder->build();
```

Add to fixture pool and tag it for easy recall.   
The tag/key does not need to match the code

```php
$attributeBuilder = AttributeBuilder::addProductAttribute();
...
$this->attributeFixturePool->add(
    $attributeBuilder->build(),
    'tdd_attribute'
);
```

Retrieve from fixture pool using tag/key

```php
$attributeFixture = $this->attributeFixturePool->get('tdd_attribute');
```

---
