<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Rule;

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\CatalogRule\Model\Rule;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class CatalogRuleFixturePoolTest extends TestCase
{
    private CatalogRuleFixturePool $catalogRuleFixtures;
    private CatalogRuleRepositoryInterface $catalogRuleRepository;
    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->catalogRuleFixtures = new CatalogRuleFixturePool();
        $this->catalogRuleRepository = $this->objectManager->create(type: CatalogRuleRepositoryInterface::class);
    }

    public function testLastCatalogRuleFixtureReturnedByDefault(): void
    {
        $firstCategory = $this->createCatalogRule();
        $lastCategory = $this->createCatalogRule();
        $this->catalogRuleFixtures->add(rule: $firstCategory);
        $this->catalogRuleFixtures->add(rule: $lastCategory);
        $catalogRuleFixture = $this->catalogRuleFixtures->get();
        $this->assertEquals(expected: $lastCategory->getId(), actual: $catalogRuleFixture->getRuleId());
    }

    public function testExceptionThrownWhenAccessingEmptyCategoryPool(): void
    {
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->catalogRuleFixtures->get();
    }

    public function testCatalogRuleFixtureReturnedByKey(): void
    {
        $firstCategory = $this->createCatalogRule();
        $lastCategory = $this->createCatalogRule();
        $this->catalogRuleFixtures->add(rule: $firstCategory, key: 'first');
        $this->catalogRuleFixtures->add(rule: $lastCategory, key: 'last');
        $catalogRuleFixture = $this->catalogRuleFixtures->get(key: 'first');
        $this->assertEquals(expected: $firstCategory->getId(), actual: $catalogRuleFixture->getRuleId());
    }

    public function testCatalogRuleFixtureReturnedByNumericKey(): void
    {
        $firstCategory = $this->createCatalogRule();
        $lastCategory = $this->createCatalogRule();
        $this->catalogRuleFixtures->add(rule: $firstCategory);
        $this->catalogRuleFixtures->add(rule: $lastCategory);
        $catalogRuleFixture = $this->catalogRuleFixtures->get(key: 0);
        $this->assertEquals(expected: $firstCategory->getId(), actual: $catalogRuleFixture->getRuleId());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $catalogRule = $this->createCatalogRule();
        $this->catalogRuleFixtures->add(rule: $catalogRule, key: 'foo');
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->catalogRuleFixtures->get(key: 'bar');
    }

    /**
     * @throws \Exception
     */
    public function testRollbackRemovesCatalogRulesFromPool(): void
    {
        $catalogRule = $this->createCatalogRuleInDb();
        $this->catalogRuleFixtures->add(rule: $catalogRule);
        $this->catalogRuleFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->catalogRuleFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackWorksWithKeys(): void
    {
        $catalogRule = $this->createCatalogRuleInDb();
        $this->catalogRuleFixtures->add(rule: $catalogRule, key: 'key');
        $this->catalogRuleFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->catalogRuleFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackDeletesCatalogRulesFromDb(): void
    {
        $catalogRule = $this->createCatalogRuleInDb();
        $this->catalogRuleFixtures->add(rule: $catalogRule);
        $this->catalogRuleFixtures->rollback();
        $this->expectException(exception: NoSuchEntityException::class);
        $this->catalogRuleRepository->get(ruleId: $catalogRule->getId());
    }

    /**
     * Creates dummy catalog rule object
     */
    private function createCatalogRule(): RuleInterface
    {
        static $nextId = 1;
        /** @var Rule $catalogRule */
        $catalogRule = $this->objectManager->create(type: Rule::class);
        $catalogRule->setId($nextId++);

        return $catalogRule;
    }

    /**
     * Creates catelog rule using builder
     *
     * @throws \Exception
     */
    private function createCatalogRuleInDb(): RuleInterface
    {
        return CatalogRuleBuilder::aCatalogRule()->build();
    }
}
