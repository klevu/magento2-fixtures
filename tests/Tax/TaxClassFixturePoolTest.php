<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class TaxClassFixturePoolTest extends TestCase
{
    private TaxClassFixturePool $taxClassFixtures;
    private TaxClassRepositoryInterface $taxClassRepository;
    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->taxClassFixtures = new TaxClassFixturePool();
        $this->taxClassRepository = $this->objectManager->create(type: TaxClassRepositoryInterface::class);
    }

    public function testLastTaxClassFixtureReturnedByDefault(): void
    {
        $firstTaxClass = $this->createTaxClass();
        $lastTaxClass = $this->createTaxClass();
        $this->taxClassFixtures->add(taxClass: $firstTaxClass);
        $this->taxClassFixtures->add(taxClass: $lastTaxClass);
        $taxClassFixture = $this->taxClassFixtures->get();
        $this->assertEquals(expected: $lastTaxClass->getId(), actual: $taxClassFixture->getId());
    }

    public function testExceptionThrownWhenAccessingEmptyTaxClassPool(): void
    {
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxClassFixtures->get();
    }

    public function testTaxClassFixtureReturnedByKey(): void
    {
        $firstTaxClass = $this->createTaxClass();
        $lastTaxClass = $this->createTaxClass();
        $this->taxClassFixtures->add(taxClass: $firstTaxClass, key: 'first');
        $this->taxClassFixtures->add(taxClass: $lastTaxClass, key: 'last');
        $taxClassFixture = $this->taxClassFixtures->get(key: 'first');
        $this->assertEquals(expected: $firstTaxClass->getId(), actual: $taxClassFixture->getId());
    }

    public function testTaxClassFixtureReturnedByNumericKey(): void
    {
        $firstTaxClass = $this->createTaxClass();
        $lastTaxClass = $this->createTaxClass();
        $this->taxClassFixtures->add(taxClass: $firstTaxClass);
        $this->taxClassFixtures->add(taxClass: $lastTaxClass);
        $taxClassFixture = $this->taxClassFixtures->get(key: 0);
        $this->assertEquals(expected: $firstTaxClass->getId(), actual: $taxClassFixture->getId());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $taxClass = $this->createTaxClass();
        $this->taxClassFixtures->add(taxClass: $taxClass, key: 'foo');
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxClassFixtures->get(key: 'bar');
    }

    /**
     * @throws \Exception
     */
    public function testRollbackRemovesTaxClassesFromPool(): void
    {
        $taxClass = $this->createTaxClassInDb();
        $this->taxClassFixtures->add(taxClass: $taxClass);
        $this->taxClassFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxClassFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackWorksWithKeys(): void
    {
        $taxClass = $this->createTaxClassInDb();
        $this->taxClassFixtures->add(taxClass: $taxClass, key: 'key');
        $this->taxClassFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->taxClassFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackDeletesTaxClassesFromDb(): void
    {
        $taxClass = $this->createTaxClassInDb();
        $this->taxClassFixtures->add(taxClass: $taxClass);
        $this->taxClassFixtures->rollback();
        $this->expectException(exception: NoSuchEntityException::class);
        $this->taxClassRepository->get(taxClassId: $taxClass->getId());
    }

    /**
     * Creates dummy tax class object
     */
    private function createTaxClass(): TaxClassInterface
    {
        static $nextId = 1;
        /** @var TaxClassInterface $taxClass */
        $taxClass = $this->objectManager->create(type: TaxClassInterface::class);
        $taxClass->setId($nextId++);

        return $taxClass;
    }

    /**
     * Creates tax class using builder
     *
     * @throws \Exception
     */
    private function createTaxClassInDb(): TaxClassInterface
    {
        return TaxClassBuilder::addTaxClass()->build();
    }
}
