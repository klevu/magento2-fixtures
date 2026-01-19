<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class TaxRateBuilderTest extends TestCase
{
    private TaxRateRepositoryInterface $taxRateRepository;
    /**
     * @var TaxRateFixture[]
     */
    private array $taxRates;

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->taxRateRepository = $objectManager->create(type: TaxRateRepositoryInterface::class);
        $this->taxRates = [];
    }

    /**
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteTaxRates();
    }

    public function testTaxClass_DefaultValues(): void
    {
        $taxRateFixture = new TaxRateFixture(
            taxRate: TaxRateBuilder::addTaxRate()->build(),
        );
        $this->taxRates[] = $taxRateFixture;
        $taxRate = $this->taxRateRepository->get(rateId: $taxRateFixture->getId());

        $this->assertSame(expected: 'tdd_tax_code', actual: $taxRate->getCode());
        $this->assertEquals(expected: 20.00, actual: $taxRate->getRate());
        $this->assertSame(expected: 'GB', actual: $taxRate->getTaxCountryId());
        $this->assertSame(expected: '*', actual: $taxRate->getTaxRegionId());
        $this->assertNull(actual: $taxRate->getZipIsRange());
        $this->assertNull(actual: $taxRate->getZipFrom());
        $this->assertNull(actual: $taxRate->getZipTo());
        $this->assertSame(expected: '*', actual: $taxRate->getTaxPostcode());
    }

    public function testTaxClass_CustomValues(): void
    {
        $taxRateBuilder = TaxRateBuilder::addTaxRate();
        $taxRateBuilder->withCode(code: 'tdd_tax_code-US-CA');
        $taxRateBuilder->withRate(rate: 8.25);
        $taxRateBuilder->withCountryId(countryId: 'US');
        $taxRateBuilder->withRegionId(taxRegionId: '12');
        $taxRateBuilder->withZipIsRange(zipIsRange: 1);
        $taxRateBuilder->withZipFrom(zipFrom: '91210');
        $taxRateBuilder->withZipTo(zipTo: '91219');
        $taxRateFixture = new TaxRateFixture(
            taxRate: $taxRateBuilder->build(),
        );
        $this->taxRates[] = $taxRateFixture;
        $taxRate = $this->taxRateRepository->get(rateId: $taxRateFixture->getId());

        $this->assertSame(expected: 'tdd_tax_code-US-CA', actual: $taxRate->getCode());
        $this->assertEquals(expected: 8.25, actual: $taxRate->getRate());
        $this->assertSame(expected: 'US', actual: $taxRate->getTaxCountryId());
        $this->assertEquals(expected: 12, actual: $taxRate->getTaxRegionId());
        $this->assertEquals(expected: 1, actual: $taxRate->getZipIsRange());
        $this->assertEquals(expected: 91210, actual: $taxRate->getZipFrom());
        $this->assertEquals(expected: 91219, actual: $taxRate->getZipTo());
        $this->assertSame(expected: '91210-91219', actual: $taxRate->getTaxPostcode());
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
}
