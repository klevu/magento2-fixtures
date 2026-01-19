# Store, Group and Website Fixtures

## Store

### Defaults

```php
[
    'key' => 'tdd_store_1',
    'code' => 'tdd_store_1',
    'name' => 'Tdd Store 1',
    'website_id' => 1, // or whatever the default website id is
    'group_id' => 1, // or whatever the first store group id for the above website is
    'is_active' => true,
    'with_sequence' => false, // creates sequence tables for the store
]
```

### Build With Trait

```php
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Store\StoreFixturesPool;
use TddWizard\Fixtures\Store\StoreTrait;

class SomeTest extends TestCase
{
    use StoreTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->storeFixturePool = $this->objectManager->create(StoreFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->storeFixturePool->rollback();
    }
    
    public function testSomething_withDefaultStoreValues(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturePool->get('tdd_store_1');
        ...
    }
    
    public function testSomething_withCustomStoreValues(): void 
    {
        $this->createStore([
            'key' => 'some_key',
            'code' => 'tdd_test_store_12345',
            'name' => 'Tdd Test Store',
            'website_id' => 10,
            'group_id' => 3,
            'is_active' => true,
            'with_sequence' => false,
        ]);
        $storeFixture = $this->storeFixturePool->get('some_key');
        ...
    }
    
    public function testSomething_withMultipleStores(): void
    {
        $this->createStore();
        $storeFixture1 = $this->storeFixturePool->get('tdd_store_1');
        
        $this->createStore([
            'key' => 'tdd_store_2',
            'code' => 'tdd_store_2',
        ]);
        $storeFixture2 = $this->storeFixturePool->get('tdd_store_2');
        ...
    }
}
```

### Build Without Trait

Build with defaults

```php
$storeBuilder = StoreBuilder::addStore();
$storeBuilder->build();
```

Build with custom values

```php
$storeBuilder = StoreBuilder::addStore();
$storeBuilder = $storeBuilder->withCode('store_code');
$storeBuilder = $storeBuilder->withName('Store Name');
$storeBuilder = $storeBuilder->withWebsiteId(10);
$storeBuilder = $storeBuilder->withGroupId(3);
$storeBuilder = $storeBuilder->withIsActive(true);
$storeBuilder = $storeBuilder->withSequence(false);
$storeBuilder->build();
```

Add to fixture pool and tag it for easy recall.   
The tag/key does not need to match the code

```php
$storeBuilder = StoreBuilder::addStore();
...
$this->storeFixturePool->add(
    $storeBuilder->build(),
    'test_store'
);
```

Retrieve from fixture pool using tag/key

```php
$store = $this->storeFixturePool->get('test_store');
```

---

## Store Group

### Defaults

```php
[
    'key' => 'tdd_store_group_1',
    'code' => 'tdd_store_group_1',
    'name' => 'Tdd Store Group_1',
    'website_id' => 1, // or whatever the default website id is
    'root_category_id' => 2, // or whatever the first root category id is
]
```

### Build With Trait

```php
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Store\GroupFixturesPool;
use TddWizard\Fixtures\Store\GroupTrait;

class SomeTest extends TestCase
{
    use GroupTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->groupFixturePool = $this->objectManager->create(GroupFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->groupFixturePool->rollback();
    }
    
    public function testSomething_withDefaultGroupValues(): void
    {
        $this->createStoreGroup();
        $groupFixture = $this->groupFixturePool->get('tdd_store_group_1');
        ...
    }
    
    public function testSomething_withCustomGroupValues(): void 
    {
        $this->createStoreGroup([
            'key' => 'some_key',
            'code' => 'tdd_store_group_1234',
            'name' => 'TDD Test Store Group',
            'website_id' => 10,
            'root_category_id' => 6,
        ]);
        $groupFixture = $this->groupFixturePool->get('some_key');
        ...
    }
    
    public function testSomething_withMultipleGroups(): void
    {
        $this->createStoreGroup();
        $groupFixture1 = $this->groupFixturePool->get('tdd_store_group_1');
        
        $this->createStoreGroup([
            'key' => 'tdd_store_group_2',
            'code' => 'tdd_store_group_2',
        ]);
        $groupFixture2 = $this->groupFixturePool->get('tdd_store_group_2');
        ...
    }
}
```

### Build Without Trait

Build with defaults

```php
$groupBuilder = GroupBuilder::addGroup();
$groupBuilder->build();
```

Build with custom values

```php
$groupBuilder = GroupBuilder::addGroup();
$groupBuilder = $groupBuilder->withCode('group_code');
$groupBuilder = $groupBuilder->withName('Group Name');
$groupBuilder = $groupBuilder->withWebsiteId(10);
$groupBuilder = $groupBuilder->withRootCategoryId(3);
$groupBuilder->build();
```

Add to fixture pool and tag it for easy recall.   
The tag/key does not need to match the code

```php
$groupBuilder = GroupBuilder::addGroup();
...
$this->groupFixturePool->add(
    $groupBuilder->build(),
    'test_group'
);
```

Retrieve from fixture pool using tag/key

```php
$store = $this->groupFixturePool->get('test_group');
```

---

## Website

### Defaults

```php
[
    'key' => 'tdd_website_1',
    'code' => 'tdd_website_1',
    'name' => 'Tdd Website 1',
    'default_group_id' => 0,
]
```

### Build With Trait

```php
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Store\WebsiteFixturesPool;
use TddWizard\Fixtures\Store\WebsiteTrait;

class SomeTest extends TestCase
{
    use WebsiteTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->websiteFixturePool = $this->objectManager->create(WebsiteFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->websiteFixturePool->rollback();
    }
    
    public function testSomething_withDefaultWebsiteValues(): void
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturePool->get('tdd_website_1');
        ...
    }
    
    public function testSomething_withCustomWebsiteValues(): void 
    {
        $this->createWebsite([
            'key' => 'some_key',
            'code' => 'tdd_test_website_1',
            'name' => 'TDD Test Website',
            'default_group_id' => 10,
        ]);
        $websiteFixture = $this->websiteFixturePool->get('some_key');
        ...
    }
    
    public function testSomething_withMultipleWebsites(): void
    {
        $this->createWebsite();
        $websiteFixture1 = $this->websiteFixturePool->get('tdd_website_1');
        
        $this->createWebsite([
            'key' => 'tdd_website_2',
            'code' => 'tdd_website_2',
        ]);
        $websiteFixture2 = $this->websiteFixturePool->get('tdd_website_2');
        ...
    }
}
```

### Build Without Trait

Build with defaults

```php
$websiteBuilder = WebsiteBuilder::addWebsite();
$websiteBuilder->build();
```

Build with custom values

```php
$websiteBuilder = WebsiteBuilder::addWebsite();
$websiteBuilder = $websiteBuilder->withCode('website_code');
$websiteBuilder = $websiteBuilder->withName('Website Name');
$websiteBuilder = $websiteBuilder->withDefaultGroupId(300);
$websiteBuilder->build();
```

Add to fixture pool and tag it for easy recall.   
The tag/key does not need to match the code

```php
$websiteBuilder = WebsiteBuilder::addStore();
...
$this->websiteFixturePool->add(
    $websiteBuilder->build(),
    'test_website'
);
```

Retrieve from fixture pool using tag/key

```php
$store = $this->websiteFixturePool->get('test_website');
```
