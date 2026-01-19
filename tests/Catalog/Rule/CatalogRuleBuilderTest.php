<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Rule;

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\CatalogRule\Model\Rule;
use Magento\Framework\Phrase;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Customer\CustomerGroupFixturePool;
use TddWizard\Fixtures\Customer\CustomerGroupTrait;
use TddWizard\Fixtures\Store\WebsiteFixturePool;
use TddWizard\Fixtures\Store\WebsiteTrait;

class CatalogRuleBuilderTest extends TestCase
{
    use CustomerGroupTrait;
    use WebsiteTrait;

    private CatalogRuleRepositoryInterface $catalogRuleRepository;
    /**
     * @var CatalogRuleFixture[]
     */
    private array $catalogRules = [];

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->catalogRuleRepository = $objectManager->create(type: CatalogRuleRepositoryInterface::class);
        $this->customerGroupFixturePool = $objectManager->create(type: CustomerGroupFixturePool::class);
        $this->websiteFixturePool = $objectManager->create(type: WebsiteFixturePool::class);
        $this->catalogRules = [];
    }

    /**
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if (!empty($this->catalogRules)) {
            foreach ($this->catalogRules as $catalogRule) {
                CatalogRuleFixtureRollback::create()->execute(ruleFixtures: $catalogRule);
            }
        }
        $this->customerGroupFixturePool->rollback();
        $this->websiteFixturePool->rollback();
    }

    public function testCatalogRule_WithDefaultValues(): void
    {
        $catalogRuleFixture = new CatalogRuleFixture(
            CatalogRuleBuilder::aCatalogRule()->build(),
        );
        $this->catalogRules[] = $catalogRuleFixture;

        /** @var RuleInterface $catalogRule */
        $catalogRule = $this->catalogRuleRepository->get(ruleId: $catalogRuleFixture->getRuleId());

        $this->assertSame(expected: 'TDD Catalog Rule', actual: $catalogRule->getName());
        $this->assertEquals(expected: 1, actual: $catalogRule->getIsActive());
        $this->assertEquals(expected: 1, actual: $catalogRule->getStopRulesProcessing());
        $this->assertSame(expected: 'by_percent', actual: $catalogRule->getSimpleAction());
        $this->assertEquals(expected: 1, actual: $catalogRule->getSortOrder());
        $this->assertEquals(expected: 10.00, actual: $catalogRule->getDiscountAmount());
    }

    public function testCatalogRule_WithCustomValues(): void
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturePool->get('tdd_website');

        $this->createCustomerGroup();
        $customerGroupFixture = $this->customerGroupFixturePool->get('tdd_customer_group');

        $catalogRuleBuilder = CatalogRuleBuilder::aCatalogRule();
        $catalogRuleBuilder = $catalogRuleBuilder->withName(name: 'TDD Custom Catalog Rule');
        $catalogRuleBuilder = $catalogRuleBuilder->withStopRulesProcessing(stopRulesProcessing: false);
        $catalogRuleBuilder = $catalogRuleBuilder->withSortOrder(sortOrder: 2);
        $catalogRuleBuilder = $catalogRuleBuilder->withDiscountAmount(discountAmount: 25.00);
        $catalogRuleBuilder = $catalogRuleBuilder->withSimpleAction(simpleAction: 'by_fixed');
        $catalogRuleBuilder = $catalogRuleBuilder->withFromDate(
            fromDate: date(format: 'Y-m-d H:i:s', timestamp: time() - (3600 * 36)),
        );
        $catalogRuleBuilder = $catalogRuleBuilder->withToDate(
            toDate: date(format: 'Y-m-d H:i:s', timestamp: time() + (3600 * 36)),
        );
        $catalogRuleBuilder = $catalogRuleBuilder->withWebsiteIds(websiteIds: [$websiteFixture->getId()]);
        $catalogRuleBuilder = $catalogRuleBuilder->withCustomerGroupIds(
            customerGroupIds: [$customerGroupFixture->getId()],
        );
        $catalogRuleBuilder = $catalogRuleBuilder->withConditions(
            conditions: [
                [
                    'attribute' => 'price',
                    'operator' => '>=',
                    'value' => '250',
                ],
                [
                    'attribute' => 'sku',
                    'operator' => '{}',
                    'value' => 'SKU_ABC_',
                ],
            ],
            type: 'any',
        );

        $catalogRuleFixture = new CatalogRuleFixture(
            rule: $catalogRuleBuilder->build(),
        );
        $this->catalogRules[] = $catalogRuleFixture;

        /** @var Rule $catalogRule */
        $catalogRule = $this->catalogRuleRepository->get(ruleId: $catalogRuleFixture->getRuleId());

        $this->assertSame(expected: 'TDD Custom Catalog Rule', actual: $catalogRule->getName());
        $this->assertEquals(expected: 1, actual: $catalogRule->getIsActive());
        $this->assertEquals(expected: 0, actual: $catalogRule->getStopRulesProcessing());
        $this->assertSame(expected: 'by_fixed', actual: $catalogRule->getSimpleAction());
        $this->assertEquals(expected: 2, actual: $catalogRule->getSortOrder());
        $this->assertEquals(expected: 25.00, actual: $catalogRule->getDiscountAmount());
        $this->assertSame(
            expected: date(format: 'Y-m-d', timestamp: time() - (3600 * 36)),
            actual: $catalogRule->getFromDate(),
        );
        $this->assertSame(
            expected: date(format: 'Y-m-d', timestamp: time() + (3600 * 36)),
            actual: $catalogRule->getToDate(),
        );
        /** @var array<int, string> $websiteIds */
        $websiteIds = $catalogRule->getWebsiteIds();
        $this->assertContains(
            needle: (string)$websiteFixture->getId(),
            haystack: $websiteIds,
        );
        $this->assertContains(
            needle: (string)$customerGroupFixture->getId(),
            haystack: $catalogRule->getCustomerGroupIds(),
        );
        $conditionsCombination = $catalogRule->getConditions();
        /** @var Phrase $aggregator */
        $aggregator = $conditionsCombination->getAggregatorName();
        $this->assertSame(expected: 'ANY', actual: $aggregator->render());
        $conditions = $conditionsCombination->getConditions();

        $priceConditions = array_filter(
            array: $conditions,
            callback: static fn (Rule\Condition\Product $condition): bool => $condition->getAttribute() === 'price',
        );
        $priceCondition = array_shift(array: $priceConditions);
        $this->assertSame(expected: '>=', actual: $priceCondition->getOperator());
        $this->assertEquals(expected: 250.00, actual: $priceCondition->getValue());

        $skuConditions = array_filter(
            array: $conditions,
            callback: static fn (Rule\Condition\Product $condition): bool => $condition->getAttribute() === 'sku',
        );
        $skuCondition = array_shift(array: $skuConditions);
        $this->assertSame(expected: '{}', actual: $skuCondition->getOperator());
        $this->assertSame(expected: 'SKU_ABC_', actual: $skuCondition->getValue());
    }
}
