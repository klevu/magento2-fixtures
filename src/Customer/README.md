# Customer Fixtures

## Customer Group

### Defaults

```php
[
    'key' => 'tdd_customer_group',
    'code' => 'TDD Customer Group',
    'tax_class_id' => 3, // either the id of the tax class called "Retail Customer" or the lowest customer tax class id 
    'excluded_website_ids' => [],
]
```

## Build With Trait

```php
use Magento\Framework\ObjectManagerInterface;
use Magento\Tax\Model\ClassModel as TaxClass;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Customer\CustomerGroupFixturesPool;
use TddWizard\Fixtures\Customer\CustomerGroupTrait;
use TddWizard\Fixtures\Store\WebsiteFixturesPool;
use TddWizard\Fixtures\Store\WebsiteTrait;
use TddWizard\Fixtures\Tax\TaxClassFixturesPool;
use TddWizard\Fixtures\Tax\TaxClassTrait;

class SomeTest extends TestCase
{
    use CustomerGroupTrait;
    use TaxClassTrait;
    use WebsiteTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->websiteFixturePool = $this->objectManager->create(WebsiteFixturesPool::class);
        $this->customerGroupFixturePool = $this->objectManager->create(CustomerGroupFixturesPool::class);
        $this->taxClassFixturePool = $this->objectManager->create(TaxClassFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->customerGroupFixturePool->rollback();
        $this->websiteFixturePool->rollback();
        $this->taxClassFixturePool->rollback();
    }
    
    public function testSomething_withDefaultCustomerGroupValues(): void
    {
        $this->createCustomerGroup();
        $customerGroupFixture = $this->customerGroupFixturePool->get('tdd_customer_group');
        ...
    }
    
    public function testSomething_withCustomCustomerGroupValues(): void 
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturePool->get('tdd_website');
        
        $this->createTaxClass([
            'class_type' => TaxClass::TAX_CLASS_TYPE_CUSTOMER,
        ]);
        $taxClassFixture = $this->taxClassFixturePool->get('tdd_tax_class');
        
        $this->createCustomerGroup([
            'key' => 'some_key',
            'code' => 'TDD Custom Customer Group',
            'tax_class_id' => $taxClassFixture->getId(),
            'excluded_website_ids' => [
                $websiteFixture->getId(),
            ],
        ]);
        $customerGroupFixture = $this->customerGroupFixturePool->get('some_key');
        ...
    }
    
    public function testSomething_withMultipleCustomerGroups(): void
    {
        $this->createCustomerGroup([
            'key' => 'tdd_customer_group_retail',
            'code' => 'TDD Retail Customer Group',
        ]);
        $retailCustomerGroupFixture = $this->customerGroupFixturePool->get('tdd_customer_group_retail');
        
        $this->createCustomerGroup([
            'key' => 'tdd_customer_group_wholesale',
            'code' => 'TDD Wholesale Customer Group',
        ]);
        $wholesaleCustomerGroupFixture = $this->customerGroupFixturePool->get('tdd_customer_group_wholesale');
        ...
    }
}
```

---

## Customer

### Defaults

```php
[
    'key' => 'tdd_customer',
    'email' => sha1(uniqid('', true)) . '@example.com'),
    'group_id' => 1,
    'store_id' => 1,
    'website_id' => 1,
    'first_name' => 'John',
    'middle_name' => 'A',
    'last_name' => 'Smith',
    'prefix' => 'Mr.',
    'suffix' => 'Esq.',
    'dob' => null,
    'tax_vat' => '12',
    'addresses' => [],
    'confirmation' => null,
    'custom_attributes' => [
        'gender' => 0,
    ],
]
```

## Build With Trait

```php
use Magento\Framework\ObjectManagerInterface;
use Magento\Tax\Model\ClassModel as TaxClass;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Customer\AddressBuilder;
use TddWizard\Fixtures\Customer\CustomerFixturesPool;
use TddWizard\Fixtures\Customer\CustomerTrait;
use TddWizard\Fixtures\Customer\CustomerGroupFixturesPool;
use TddWizard\Fixtures\Customer\CustomerGroupTrait;
use TddWizard\Fixtures\Store\StoreFixturesPool;
use TddWizard\Fixtures\Store\StoreTrait;
use TddWizard\Fixtures\Store\WebsiteFixturesPool;
use TddWizard\Fixtures\Store\WebsiteTrait;
use TddWizard\Fixtures\Tax\TaxClassFixturesPool;
use TddWizard\Fixtures\Tax\TaxClassTrait;

class SomeTest extends TestCase
{
    use CustomerTrait;
    use StoreTrait;
    use TaxClassTrait;
    use WebsiteTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->websiteFixturePool = $this->objectManager->create(WebsiteFixturesPool::class);
        $this->storeFixturePool = $this->objectManager->create(StoreFixturesPool::class);
        $this->customerFixturePool = $this->objectManager->create(CustomerFixturesPool::class);
        $this->customerGroupFixturePool = $this->objectManager->create(CustomerGroupFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->customerFixturePool->rollback();
        $this->customerGroupFixturePool->rollback();
        $this->storeFixturePool->rollback();
        $this->websiteFixturePool->rollback();
    }
    
    public function testSomething_withDefaultCustomerValues(): void
    {
        $this->createCustomer();
        $customerGroupFixture = $this->customerFixturePool->get('tdd_customer');
        ...
    }
    
    public function testSomething_withCustomCustomerValues(): void 
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturePool->get('tdd_website');
        
        $this->createStore();
        $storeFixture = $this->storeFixturePool->get('tdd_store');
        
        $this->createCustomerGroup();
        $customerGroupFixture = $this->customerGroupFixturePool->get('tdd_customer_group');
        
        $this->createCustomer([
            'key' => 'some_key',
            'email' => 'test.customer@example.com',
            'group_id' => $customerGroupFixture->getId(),
            'store_id' => $storeFixture->getId(),
            'website_id' => $websiteFixture->getId(),
            'first_name' => 'Test',
            'middle_name' => 'A',
            'last_name' => 'Customer',
            'prefix' => 'Mrs.',
            'suffix' => '',
            'dob' => '01/01/1970',
            'addresses' => [
                AddressBuilder::anAddress()->asDefaultBilling(),
                AddressBuilder::anAddress()->asDefaultShipping(),
                AddressBuilder::anAddress(),
            ],
        ]);
        $customerGroupFixture = $this->customerFixturePool->get('some_key');
        ...
    }
    
    public function testSomething_withMultipleCustomers(): void
    {
        $this->createCustomer([
            'key' => 'tdd_customer_1',
            'email' => 'customer@example.com',
        ]);
        $customerFixture1 = $this->customerFixturePool->get('tdd_customer_1');
        
        $this->createCustomer([
            'key' => 'tdd_customer_2',
            'email' => 'another_customer@example.com',
        ]);
        $customerFixture2 = $this->customerFixturePool->get('tdd_customer_2');
        ...
    }
}
```

### Build Without Trait

If you need a customer without specific data, this is all:

```php
protected function setUp(): void
{
  $this->customerFixture = new CustomerFixture(
    CustomerBuilder::aCustomer()->build()
  );
}
protected function tearDown(): void
{
  $this->customerFixture->rollback();
}
```

It uses default sample data and a random email address. If you need the ID or email address in the tests, the
`CustomerFixture` gives you access:

```php
$this->customerFixture->getId();
$this->customerFixture->getEmail();
```

You can configure the builder with attributes:

```php
CustomerBuilder::aCustomer()
  ->withEmail('test@example.com')
  ->withCustomAttributes(
    [
      'my_custom_attribute' => 42
    ]
  )
  ->build()
```

You can add addresses to the customer:

```php
CustomerBuilder::aCustomer()
  ->withAddresses(
    AddressBuilder::anAddress()->asDefaultBilling(),
    AddressBuilder::anAddress()->asDefaultShipping(),
    AddressBuilder::anAddress()
  )
  ->build()
```

Or just one:

```php
CustomerBuilder::aCustomer()
  ->withAddresses(
    AddressBuilder::anAddress()->asDefaultBilling()->asDefaultShipping()
  )
  ->build()
```

The `CustomerFixture` also has a shortcut to create a customer session:

```php
$this->customerFixture->login();
```

### Addresses

Similar to the customer builder you can also configure the address builder with custom attributes:

```php
AddressBuilder::anAddress()
  ->withCountryId('DE')
  ->withCity('Aachen')
  ->withPostcode('52078')
  ->withCustomAttributes(
    [
      'my_custom_attribute' => 42
    ]
  )
  ->asDefaultShipping()
```
