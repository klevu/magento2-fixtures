<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Tax\Api\Data\TaxRateInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class TaxRateFixturePoolTest extends TestCase
{
    private TaxRateFixturePool $taxRateFixtures;
    private TaxRateRepositoryInterface $taxRateRepository;
    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->taxRateFixtures = new TaxRateFixturePool();
        $this->taxRateRepository = $this->objectManager->create(type: TaxRateRepositoryInterface::class);
    }

    public function testLastTaxRateFixtureReturnedByDefault(): void
    {
        $firstTaxRate = $this->createTaxRate();
        $lastTaxRate = $this->createTaxRate();
        $this->taxRateFixtures->add(taxRate: $firstTaxRate);
        $this->taxRateFixtures->add(taxRate: $lastTaxRate);
        $taxRateFixture = $this->taxRateFixtures->get();
        $this->assertEquals(expected: $lastTaxRate->getId(), actual: $taxRateFixture->getId());
    }

    public function testExceptionThrownWhenAccessingEmptyTaxRatePool(): void
    {
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxRateFixtures->get();
    }

    public function testTaxRateFixtureReturnedByKey(): void
    {
        $firstTaxRate = $this->createTaxRate();
        $lastTaxRate = $this->createTaxRate();
        $this->taxRateFixtures->add(taxRate: $firstTaxRate, key: 'first');
        $this->taxRateFixtures->add(taxRate: $lastTaxRate, key: 'last');
        $taxRateFixture = $this->taxRateFixtures->get(key: 'first');
        $this->assertEquals(expected: $firstTaxRate->getId(), actual: $taxRateFixture->getId());
    }

    public function testTaxRateFixtureReturnedByNumericKey(): void
    {
        $firstTaxRate = $this->createTaxRate();
        $lastTaxRate = $this->createTaxRate();
        $this->taxRateFixtures->add(taxRate: $firstTaxRate);
        $this->taxRateFixtures->add(taxRate: $lastTaxRate);
        $taxRateFixture = $this->taxRateFixtures->get(key: 0);
        $this->assertEquals(expected: $firstTaxRate->getId(), actual: $taxRateFixture->getId());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $taxRate = $this->createTaxRate();
        $this->taxRateFixtures->add(taxRate: $taxRate, key: 'foo');
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxRateFixtures->get(key: 'bar');
    }

    /**
     * @throws \Exception
     */
    public function testRollbackRemovesTaxRatesFromPool(): void
    {
        $taxRate = $this->createTaxRateInDb();
        $this->taxRateFixtures->add(taxRate: $taxRate);
        $this->taxRateFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxRateFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackWorksWithKeys(): void
    {
        $taxRate = $this->createTaxRateInDb();
        $this->taxRateFixtures->add(taxRate: $taxRate, key: 'key');
        $this->taxRateFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxRateFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackDeletesTaxRatesFromDb(): void
    {
        $taxRate = $this->createTaxRateInDb();
        $this->taxRateFixtures->add(taxRate: $taxRate);
        $this->taxRateFixtures->rollback();
        $this->expectException(exception: NoSuchEntityException::class);
        $this->taxRateRepository->get(rateId: $taxRate->getId());
    }

    /**
     * Creates dummy tax rate object
     */
    private function createTaxRate(): TaxRateInterface
    {
        static $nextId = 1;
        /** @var TaxRateInterface $taxRate */
        $taxRate = $this->objectManager->create(type: TaxRateInterface::class);
        $taxRate->setId($nextId++);

        return $taxRate;
    }

    /**
     * Creates tax rate using builder
     *
     * @throws \Exception
     */
    private function createTaxRateInDb(): TaxRateInterface
    {
        return TaxRateBuilder::addTaxRate()->build();
    }
}
