<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class TaxClassBuilderTest extends TestCase
{
    private TaxClassRepositoryInterface $taxClassRepository;
    /**
     * @var TaxClassFixture[]
     */
    private array $taxClasses;

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->taxClassRepository = $objectManager->create(type: TaxClassRepositoryInterface::class);
        $this->taxClasses = [];
    }

    /**
     * @throws CouldNotDeleteException
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteTaxClasses();
    }

    public function testTaxClass_DefaultValues(): void
    {
        $taxClassFixture = new TaxClassFixture(
            taxClass: TaxClassBuilder::addTaxClass()->build(),
        );
        $this->taxClasses[] = $taxClassFixture;
        $taxClass = $this->taxClassRepository->get(taxClassId: $taxClassFixture->getId());

        $this->assertSame(expected: 'TDD Product Tax Class', actual: $taxClass->getClassName());
        $this->assertSame(expected: 'PRODUCT', actual: $taxClass->getClassType());
    }

    public function testTaxClass_CustomValues(): void
    {
        $taxClassBuilder = TaxClassBuilder::addTaxClass();
        $taxClassBuilder->withClassName(className: 'TDD Customer Tax Class');
        $taxClassBuilder->withClassType(classType: 'CUSTOMER');

        $taxClassFixture = new TaxClassFixture(
            taxClass: $taxClassBuilder->build(),
        );
        $this->taxClasses[] = $taxClassFixture;
        $taxClass = $this->taxClassRepository->get(taxClassId: $taxClassFixture->getId());

        $this->assertSame(expected: 'TDD Customer Tax Class', actual: $taxClass->getClassName());
        $this->assertSame(expected: 'CUSTOMER', actual: $taxClass->getClassType());
    }

    /**
     * @throws CouldNotDeleteException
     */
    private function deleteTaxClasses(): void
    {
        foreach ($this->taxClasses as $taxClass) {
            try {
                $this->taxClassRepository->delete(taxClass: $taxClass->getTaxClass());
            } catch (NoSuchEntityException) {
                // tax class already removed
            }
        }
    }
}
