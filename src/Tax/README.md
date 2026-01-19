# Tax Fixtures

## Tax Class

### Defaults

```php
[
    'key' => 'tdd_tax_class',
    'class_name' => 'TDD Product Tax Class',
    'class_type' => 'PRODUCT', // the other option is 'CUSTOMER' see Magento\Tax\Model\ClassModel
]
```

### Build With Trait

```php
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Tax\TaxClassFixturesPool;
use TddWizard\Fixtures\Tax\TaxClassTrait;
use TddWizard\Fixtures\Store\StoreFixturesPool;
use TddWizard\Fixtures\Store\StoreTrait;

class SomeTest extends TestCase
{
    use TaxClassTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->taxClassFixturePool = $this->objectManager->create(TaxClassFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->taxClassFixturePool->rollback();
    }
    
    public function testSomething_withDefaultTaxClassValues(): void
    {
        $this->createTaxClass();
        $taxClassFixture = $this->taxClassFixturePool->get('tdd_tax_class');
        ...
    }

    public function testSomething_withCustomTaxClassValues(): void
    {
        $this->createTaxClass([
            'class_name' => 'TDD Customer Tax Class',
            'class_type' -> 'CUSTOMER',
        ]);
        $taxClassFixture = $this->taxClassFixturePool->get('tdd_tax_class');
        ...
    }

    public function testSomething_withMultipleTaxClasses(): void
    {
        $this->createTaxClass();
        $taxClassFixture1 = $this->taxClassFixturePool->get('tdd_tax_class');
        
        $this->createTaxClass([
            'key' => 'tdd_tax_class_2',
            'class_name' => 'TDD Product Tax Class 2',
        ]);
        $taxClassFixture2 = $this->taxClassFixturePool->get('tdd_tax_class_2');
        ...
    }
```

---

## Tax Rates

### Defaults

```php
[
    'key' => 'tdd_tax_rate',
    'code' => 'tdd_tax_code',
    'rate' => 20.0,
    'tax_country_id' => 'GB',
    'tax_region_id' => 0,
    'zip_is_range' => 0,
    'zip_from' => null | 0, // defaults to 0 if zip_is_range id set, else null
    'zip_to' => null | 999999999, // defaults to 999999999 if zip_is_range id set, else null
    'tax_postcode' => '*'
]
```

### Build With Trait

```php
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Tax\TaxRateFixturesPool;
use TddWizard\Fixtures\Tax\TaxRateTrait;
use TddWizard\Fixtures\Store\StoreFixturesPool;
use TddWizard\Fixtures\Store\StoreTrait;

class SomeTest extends TestCase
{
    use TaxRateTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->taxRateFixturePool = $this->objectManager->create(TaxRateFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->taxRateFixturePool->rollback();
    }
    
    public function testSomething_withDefaultTaxRateValues(): void
    {
        $this->createTaxRate();
        $taxRateFixture = $this->taxRateFixturePool->get('tdd_tax_rate');
        ...
    }

    public function testSomething_withCustomTaxRateValues(): void
    {
        $this->createTaxRate([
            'code' => 'US-CA-*-Rate 1',
            'rate' => 8.25,
            'tax_country_id' => 'US',
            'tax_region_id' => 12,
        ]);
        $taxRateFixture = $this->taxRateFixturePool->get('tdd_tax_rate');
        ...
    }

    public function testSomething_withMultipleTaxRates(): void
    {
        $this->createTaxRate();
        $taxClassFixture1 = $this->taxRateFixturePool->get('tdd_tax_rate');
        
        $this->createTaxRate([
            'key' => 'tdd_tax_rate_2',
            'code' => 'tdd_tax_code-UK_reduced',
            'rate' => 5.00,
        ]);
        $taxClassFixture2 = $this->taxRateFixturePool->get('tdd_tax_rate_2');
        ...
    }
```

---

## Tax Rules

### Defaults

```php
[
    'key' => 'tdd_tax_rule',
    'code' => 'tdd_tax_rule_code',
    'tax_rate_ids' => null, // this is a required field
    'customer_tax_class_ids' => [3], // or whatever the default customer tax class id is
    'product_tax_class_ids' => [2], // or whatever the default product tax class id is
    'priority' => 0,
    'calculate_subtotal' => false,
]
```

### Build With Trait

```php
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Tax\TaxRateFixturesPool;
use TddWizard\Fixtures\Tax\TaxRateTrait;
use TddWizard\Fixtures\Tax\TaxRuleFixturesPool;
use TddWizard\Fixtures\Tax\TaxRuleTrait;
use TddWizard\Fixtures\Store\StoreFixturesPool;
use TddWizard\Fixtures\Store\StoreTrait;

class SomeTest extends TestCase
{
    use TaxRateTrait;
    use TaxRuleTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->taxRateFixturePool = $this->objectManager->create(TaxRateFixturesPool::class);
        $this->taxRuleFixturePool = $this->objectManager->create(TaxRuleFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->taxRuleFixturePool->rollback();
        $this->taxRateFixturePool->rollback();
    }
    
    public function testSomething_withDefaultTaxRuleValues(): void
    {
        $this->createTaxRate();
        $taxRateFixture = $this->taxRateFixturePool->get('tdd_tax_rate');
        
        $this->createTaxRule([
           'tax_rate_ids' => [$taxRateFixture->getId()]
        ]);
        $taxRuleFixture = $this->taxRuleFixturePool->get('tdd_tax_rule');
        ...
    }

    public function testSomething_withCustomTaxRuleValues(): void
    {
        $this->createTaxClass([
            'key' => 'tdd_product_tax_class',
            'class_name' => 'tdd_product_tax_class',
        ]);
        $productTaxClassFixture = $this->taxClassFixturePool->get('tdd_product_tax_class');
    
        $this->createTaxClass([
            'key' => 'tdd_customer_tax_class',
            'class_name' => 'tdd_customer_tax_class',
            'class_type' -> 'CUSTOMER',
        ]);
        $customerTaxClassFixture = $this->taxClassFixturePool->get('tdd_customer_tax_class');
    
        $this->createTaxRate();
        $taxRateFixture = $this->taxRateFixturePool->get('tdd_tax_rate');
        
        $this->createTaxRule([
           'key' => 'some_tax_rule',
           'tax_rate_ids' => [$taxRateFixture->getId()],
           'customer_tax_class_ids' => [$customerTaxClassFixture->getId()],
           'product_tax_class_ids' => [$productTaxClassFixture->getid()],
           'priority' => 3,
           'calculate_subtotal' => true,
        ]);
        $taxRuleFixture = $this->taxRuleFixturePool->get('some_tax_rule');
        ...
    }
```
