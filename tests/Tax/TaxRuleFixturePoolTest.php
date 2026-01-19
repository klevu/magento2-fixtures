<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Tax\Api\Data\TaxRuleInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class TaxRuleFixturePoolTest extends TestCase
{
    private TaxRuleFixturePool $taxRuleFixtures;
    private TaxRuleRepositoryInterface $taxRuleRepository;
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var TaxRateFixture[]
     */
    private array $taxRates = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();

        $this->taxRuleFixtures = new TaxRuleFixturePool();
        $this->taxRuleRepository = $this->objectManager->create(type: TaxRuleRepositoryInterface::class);
    }

    /**
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::teardown();

        $this->rollbackTaxRates();
    }

    public function testLastTaxRuleFixtureReturnedByDefault(): void
    {
        $firstTaxRule = $this->createTaxRule();
        $lastTaxRule = $this->createTaxRule();
        $this->taxRuleFixtures->add(taxRule: $firstTaxRule);
        $this->taxRuleFixtures->add(taxRule: $lastTaxRule);
        $taxRuleFixture = $this->taxRuleFixtures->get();
        $this->assertEquals(expected: $lastTaxRule->getId(), actual: $taxRuleFixture->getId());
    }

    public function testExceptionThrownWhenAccessingEmptyTaxRulePool(): void
    {
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxRuleFixtures->get();
    }

    public function testTaxRuleFixtureReturnedByKey(): void
    {
        $firstTaxRule = $this->createTaxRule();
        $lastTaxRule = $this->createTaxRule();
        $this->taxRuleFixtures->add(taxRule: $firstTaxRule, key: 'first');
        $this->taxRuleFixtures->add(taxRule: $lastTaxRule, key: 'last');
        $taxRuleFixture = $this->taxRuleFixtures->get(key: 'first');
        $this->assertEquals(expected: $firstTaxRule->getId(), actual: $taxRuleFixture->getId());
    }

    public function testTaxRuleFixtureReturnedByNumericKey(): void
    {
        $firstTaxRule = $this->createTaxRule();
        $lastTaxRule = $this->createTaxRule();
        $this->taxRuleFixtures->add(taxRule: $firstTaxRule);
        $this->taxRuleFixtures->add(taxRule: $lastTaxRule);
        $taxRuleFixture = $this->taxRuleFixtures->get(key: 0);
        $this->assertEquals(expected: $firstTaxRule->getId(), actual: $taxRuleFixture->getId());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $taxRule = $this->createTaxRule();
        $this->taxRuleFixtures->add(taxRule: $taxRule, key: 'foo');
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxRuleFixtures->get(key: 'bar');
    }

    /**
     * @throws \Exception
     */
    public function testRollbackRemovesTaxRulesFromPool(): void
    {
        $taxRule = $this->createTaxRuleInDb();
        $this->taxRuleFixtures->add(taxRule: $taxRule);
        $this->taxRuleFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxRuleFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackWorksWithKeys(): void
    {
        $taxRule = $this->createTaxRuleInDb();
        $this->taxRuleFixtures->add(taxRule: $taxRule, key: 'key');
        $this->taxRuleFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxRuleFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackDeletesTaxRulesFromDb(): void
    {
        $taxRule = $this->createTaxRuleInDb();
        $this->taxRuleFixtures->add(taxRule: $taxRule);
        $this->taxRuleFixtures->rollback();
        $this->expectException(exception: NoSuchEntityException::class);
        $this->taxRuleRepository->get(ruleId: $taxRule->getId());
    }

    /**
     * Creates dummy tax rule object
     */
    private function createTaxRule(): TaxRuleInterface
    {
        static $nextId = 1;
        /** @var TaxRuleInterface $taxRule */
        $taxRule = $this->objectManager->create(type: TaxRuleInterface::class);
        $taxRule->setId($nextId++);

        return $taxRule;
    }

    /**
     * Creates tax rule using builder
     *
     * @throws \Exception
     */
    private function createTaxRuleInDb(): TaxRuleInterface
    {
        $taxRate = TaxRateBuilder::addTaxRate()->build();
        $this->taxRates[] = new TaxRateFixture(taxRate: $taxRate);

        $taxRateIds = [$taxRate->getId()];
        $taxRuleBuilder = TaxRuleBuilder::addTaxRule();
        $taxRuleBuilder->withTaxRateIds(taxRateIds: $taxRateIds);

        return $taxRuleBuilder->build();
    }

    /**
     * @throws \Exception
     */
    private function rollbackTaxRates(): void
    {
        TaxRateFixtureRollback::create()->execute(...$this->taxRates);
    }
}
