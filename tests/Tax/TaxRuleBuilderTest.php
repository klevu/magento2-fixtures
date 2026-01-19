<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class TaxRuleBuilderTest extends TestCase
{
    private TaxRuleRepositoryInterface $taxRuleRepository;
    private TaxRateRepositoryInterface $taxRateRepository;
    private TaxClassRepositoryInterface $taxClassRepository;
    /**
     * @var TaxClassFixture[]
     */
    private array $taxClasses;

    /**
     * @var TaxRuleFixture[]
     */
    private array $taxRules;
    /**
     * @var TaxRateFixture[]
     */
    private array $taxRates;

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->taxRuleRepository = $objectManager->create(type: TaxRuleRepositoryInterface::class);
        $this->taxRateRepository = $objectManager->create(type: TaxRateRepositoryInterface::class);
        $this->taxClassRepository = $objectManager->create(type: TaxClassRepositoryInterface::class);
        $this->taxClasses = [];
        $this->taxRules = [];
        $this->taxRates = [];
    }

    /**
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteTaxRules();
        $this->deleteTaxRates();
        $this->deleteTaxClasses();
    }

    public function testTaxRule_DefaultValues(): void
    {
        $taxRateFixture = new TaxRateFixture(
            taxRate: TaxRateBuilder::addTaxRate()->build(),
        );
        $this->taxRates[] = $taxRateFixture;

        $taxRuleBuilder = TaxRuleBuilder::addTaxRule();
        $taxRuleBuilder->withTaxRateIds(taxRateIds: [$taxRateFixture->getId()]);
        $taxRuleFixture = new TaxRuleFixture(
            taxRule: $taxRuleBuilder->build(),
        );
        $this->taxRules[] = $taxRuleFixture;
        $taxRule = $this->taxRuleRepository->get(ruleId: $taxRuleFixture->getId());

        $this->assertSame(expected: 'tdd_tax_rule_code', actual: $taxRule->getCode());
        $this->assertContains(needle: $taxRateFixture->getId(), haystack: $taxRule->getTaxRateIds());
        $this->assertNotCount(expectedCount: 0, haystack: $taxRule->getCustomerTaxClassIds());
        $this->assertNotCount(expectedCount: 0, haystack: $taxRule->getProductTaxClassIds());
        $this->assertFalse(condition: $taxRule->getCalculateSubtotal());
        $this->assertSame(expected: 0, actual: $taxRule->getPriority());
    }

    public function testTaxRule_CustomValues(): void
    {
        $taxClassFixtureProduct = new TaxClassFixture(
            taxClass: TaxClassBuilder::addTaxClass()->build(),
        );
        $this->taxClasses[] = $taxClassFixtureProduct;
        $taxClassBuilder = TaxClassBuilder::addTaxClass();
        $taxClassBuilder->withClassName(className: 'TDD Customer Tax Class');
        $taxClassBuilder->withClassType(classType: 'CUSTOMER');
        $taxClassFixtureCustomer = new TaxClassFixture(
            taxClass: $taxClassBuilder->build(),
        );
        $this->taxClasses[] = $taxClassFixtureCustomer;

        $taxRateFixture = new TaxRateFixture(
            taxRate: TaxRateBuilder::addTaxRate()->build(),
        );
        $this->taxRates[] = $taxRateFixture;

        $taxRuleBuilder = TaxRuleBuilder::addTaxRule();
        $taxRuleBuilder->withCode(code: 'tdd_tax_rule_code_custom');
        $taxRuleBuilder->withTaxRateIds(taxRateIds: [$taxRateFixture->getId()]);
        $taxRuleBuilder->withCustomerTaxClassIds(customerTaxClassIds: [$taxClassFixtureCustomer->getId()]);
        $taxRuleBuilder->withProductTaxClassIds(productTaxClassIds: [$taxClassFixtureProduct->getId()]);
        $taxRuleBuilder->withPriority(priority: 5);
        $taxRuleBuilder->withCalculateSubtotal(calculateSubtotal: true);
        $taxRuleFixture = new TaxRuleFixture(
            taxRule: $taxRuleBuilder->build(),
        );
        $this->taxRules[] = $taxRuleFixture;
        $taxRule = $this->taxRuleRepository->get(ruleId: $taxRuleFixture->getId());

        $this->assertSame(expected: 'tdd_tax_rule_code_custom', actual: $taxRule->getCode());
        $this->assertContains(needle: $taxRateFixture->getId(), haystack: $taxRule->getTaxRateIds());
        $this->assertContains(needle: $taxClassFixtureCustomer->getId(), haystack: $taxRule->getCustomerTaxClassIds());
        $this->assertContains(needle: $taxClassFixtureProduct->getId(), haystack: $taxRule->getProductTaxClassIds());
        $this->assertTrue(condition: $taxRule->getCalculateSubtotal());
        $this->assertSame(expected: 5, actual: $taxRule->getPriority());
    }

    /**
     * @throws \Exception
     */
    private function deleteTaxRules(): void
    {
        foreach ($this->taxRules as $taxRule) {
            try {
                $this->taxRuleRepository->delete(rule: $taxRule->getTaxRule());
            } catch (NoSuchEntityException) {
                // tax class already removed
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function deleteTaxRates(): void
    {
        foreach ($this->taxRates as $taxRate) {
            try {
                $this->taxRateRepository->delete(taxRate: $taxRate->getTaxRate());
            } catch (NoSuchEntityException) {
                // tax class already removed
            }
        }
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
