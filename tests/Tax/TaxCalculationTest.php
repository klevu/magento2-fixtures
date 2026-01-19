<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Catalog\Model\Product;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Magento\Tax\Model\ClassModel as TaxClass;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\Product\ProductFixturePool;
use TddWizard\Fixtures\Catalog\Product\ProductTrait;
use TddWizard\Fixtures\Core\ConfigFixture;

class TaxCalculationTest extends TestCase
{
    use ProductTrait;
    use TaxClassTrait;
    use TaxRateTrait;
    use TaxRuleTrait;

    private TaxCalculation $taxCalculation;

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->taxClassFixturePool = $objectManager->get(type: TaxClassFixturePool::class);
        $this->taxRateFixturePool = $objectManager->get(type: TaxRateFixturePool::class);
        $this->taxRuleFixturePool = $objectManager->get(type: TaxRuleFixturePool::class);
        $this->productFixturePool = $objectManager->get(type: ProductFixturePool::class);
        $this->taxCalculation = $objectManager->get(type: TaxCalculation::class);
    }

    /**
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->productFixturePool->rollback();
        $this->taxRuleFixturePool->rollback();
        $this->taxRateFixturePool->rollback();
        $this->taxClassFixturePool->rollback();
    }

    /**
     * @magentoDbIsolation disabled
     */
    #[DataProvider('dataProvider_testTaxCalculatedCorrectly_WhenCreatedViaFixtures')]
    public function testTaxCalculatedCorrectly_WhenCreatedViaFixtures(
        bool $productIsTaxable,
        bool $catalogIncludeTax,
        string $countryId,
        float $productPrice,
        float $expectedTaxValue,
    ): void {
        ConfigFixture::setGlobal(
            path: TaxConfig::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
            value: (int)$catalogIncludeTax,
        );
        ConfigFixture::setGlobal(
            path: TaxConfig::CONFIG_XML_PATH_BASED_ON,
            value: 'shipping',
        );
        ConfigFixture::setGlobal(
            path: TaxConfig::CONFIG_XML_PATH_DEFAULT_COUNTRY,
            value: $countryId,
        );
        ConfigFixture::setGlobal(
            path: TaxConfig::CONFIG_XML_PATH_DEFAULT_REGION,
            value: '*',
        );
        ConfigFixture::setGlobal(
            path: TaxConfig::CONFIG_XML_PATH_DEFAULT_POSTCODE,
            value: '*',
        );

        $this->createTaxClass([
            'key' => 'tdd_product_tax_class',
        ]);
        $productTaxClassFixture = $this->taxClassFixturePool->get(key: 'tdd_product_tax_class');

        $this->createTaxClass([
            'key' => 'tdd_customer_tax_class',
            'class_name' => 'TDD Customer Tax Class',
            'class_type' => TaxClass::TAX_CLASS_TYPE_CUSTOMER,
        ]);
        $customerTaxClassFixture = $this->taxClassFixturePool->get(key: 'tdd_customer_tax_class');

        $this->createTaxRate(taxRateData: [
            'key' => 'tdd_tax_rate_gb',
            'code' => 'tdd_tax_rate_gb',
            'rate' => 20.00,
            'tax_country_id' => 'GB',
        ]);
        $taxRateFixtureGB = $this->taxRateFixturePool->get('tdd_tax_rate_gb');
        $this->createTaxRate(taxRateData: [
            'key' => 'tdd_tax_rate_us',
            'code' => 'tdd_tax_rate_us',
            'rate' => 10.00,
            'tax_country_id' => 'US',
        ]);
        $taxRateFixtureUS = $this->taxRateFixturePool->get('tdd_tax_rate_us');
        $this->createTaxRule(taxRuleData: [
            'tax_rate_ids' => [$taxRateFixtureGB->getId(), $taxRateFixtureUS->getId()],
            'product_tax_class_ids' => [$productTaxClassFixture->getId()],
            'customer_tax_class_ids' => [$customerTaxClassFixture->getId()],
        ]);

        $this->createProduct(productData: [
            'tax_class_id' => $productIsTaxable ? $productTaxClassFixture->getId() : 0,
            'price' => $productPrice,
        ]);
        $productFixture = $this->productFixturePool->get('tdd_product');
        /** @var Product $product */
        $product = $productFixture->getProduct();

        $request = $this->taxCalculation->getRateRequest(
            customerTaxClass: $customerTaxClassFixture->getId(),
        );
        $request->setData(
            key: 'product_class_id',
            value: $productIsTaxable ? $productTaxClassFixture->getId() : 0,
        );
        $taxRate = $this->taxCalculation->getRate(request: $request);

        $taxAmount = $this->taxCalculation->calcTaxAmount(
            price: $product->getFinalPrice(qty: 1),
            taxRate: $taxRate,
            priceIncludeTax: $catalogIncludeTax,
            round: true,
        );

        $this->assertSame(expected: $expectedTaxValue, actual: $taxAmount);
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testTaxCalculatedCorrectly_WhenCreatedViaFixtures(): array
    {
        // [$productIsTaxable, $catalogIncludeTax, $countryId, $productPrice, $expectedTaxValue]
        return [
            [true, false, 'GB', 100.00, 20.00],
            [true, true, 'GB', 100.00, 16.67],
            [false, false, 'GB', 100.00, 0.00],
            [false, true, 'GB', 100.00, 0.00],
            [true, false, 'US', 100.00, 10.00],
            [true, true, 'US', 100.00, 9.09],
            [false, false, 'US', 100.00, 0.00],
            [false, true, 'US', 100.00, 0.00],
        ];
    }
}
