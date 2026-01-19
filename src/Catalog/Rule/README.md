# Catalog Rule Fixtures

## Defaults

```php
[
    'key' => 'tdd_catalog_rule',
    'name' => 'TDD Catalog Rule',
    'is_active' => true,
    'stop_rules' => true,
    'sort_order' => 1,
    'website_ids' => [1],
    'customer_group_ids' => [Group::NOT_LOGGED_IN_ID],
    'discount_amount' => 10.00,
    'is_percent' => true,
    'from_date' => time() - (3600 * 24),
    'to_date' => time() + (3600 * 24),
    'conditions' => [],
    'condition_type' => 'all',
]
```

## Build With Trait

```php
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\Rule\CatalogRuleFixturesPool;
use TddWizard\Fixtures\Catalog\Rule\CatalogRuleTrait;
use TddWizard\Fixtures\Customer\CustomerGroupFixturesPool;
use TddWizard\Fixtures\Customer\CustomerGroupTrait;
use TddWizard\Fixtures\Store\WebsiteFixturesPool;
use TddWizard\Fixtures\Store\WebsiteTrait;

class SomeTest extends TestCase
{
    use CatalogRuleTrait;
    use CustomerGroupTrait;
    use WebsiteTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->websiteFixturePool = $this->objectManager->create(WebsiteFixturesPool::class);
        $this->catalogRuleFixturePool = $this->objectManager->create(CatalogRuleFixturesPool::class);
        $this->customerGroupFixturePool = $this->objectManager->create(CustomerGroupFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->customerGroupFixturePool->rollback();
        $this->websiteFixturePool->rollback();
        $this->catalogRuleFixturePool->rollback();
    }
    
    public function testSomething_withDefaultCatalogRuleValues(): void
    {
        $this->createCatalogRule();
        $catalogRuleFixture = $this->catalogRuleFixturePool->get('tdd_catalog_rule');
        ...
    }
    
    public function testSomething_withCustomCatalogRuleValues(): void 
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturePool->get('tdd_website');
        
        $this->createCustomerGroup();
        $customerGroupFixture = $this->customerGroupFixturePool->get('tdd_customer_group');
        
        $this->createCatalogRule([
            'key' => 'some_key',
            'name' => 'TDD Catalog Rule',
            'is_active' => true,
            'stop_rules' => false,
            'website_ids' => [$websiteFixture->getId()],
            'customer_group_ids' => [$customerGroupFixture->getId()],
            'from_date' =>  date('Y-m-d H:i:s', time() - (3600 * 48)),
            'to_date' => date('Y-m-d H:i:s', time() + (3600 * 72)),
            'discount_amount' => 25.00,
            'is_percent' => false,
            'sort_order' => 3,
            'conditions' => [
                [
                    'attribute' => ProductInterface::SKU,
                    'operator' => '{}', // @see \Magento\CatalogRule\Model\Rule\Condition\ConditionsToSearchCriteriaMapper::mapRuleOperatorToSQLCondition
                    'value' => 'SKU_ABC_'
                ],
                [
                    'attribute' => ProductInterface::PRICE,
                    'operator' => '>=',
                    'value' => 250
                ],
            ],
            'condition_type' => 'any',
        ]);
        $catalogRuleFixture = $this->catalogRuleFixturePool->get('some_key');
        ...
    }
    
    public function testSomething_withMultipleCatalogRules(): void
    {
        $this->createCatalogRule();
        $catalogRuleFixture1 = $this->catalogRuleFixturePool->get('tdd_catalog_rule');
        
         $this->createCatalogRule([
            'key' => 'tdd_catalog_rule_2',
            'name' => 'TDD Catalog Rule 2',
            ...
        ]);
        $catalogRuleFixture2 = $this->catalogRuleFixturePool->get('tdd_catalog_rule_2');
        ...
    }
}
```
