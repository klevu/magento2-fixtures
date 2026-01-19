<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use TddWizard\Fixtures\Exception\FixturePoolMissingException;

trait TaxRateTrait
{
    private ?TaxRateFixturePool $taxRateFixturePool = null;

    /**
     * @param array<string, mixed> $taxRateData
     *
     * @throws \Exception
     */
    public function createTaxRate(array $taxRateData = []): void
    {
        if (null === $this->taxRateFixturePool) {
            throw new FixturePoolMissingException(
                message: 'taxRateFixturePool has not been created in your test setUp method.',
            );
        }
        $taxRateBuilder = TaxRateBuilder::addTaxRate();
        if ($taxRateData['code'] ?? null) {
            $taxRateBuilder->withCode(code: $taxRateData['code']);
        }
        if ($taxRateData['rate'] ?? null) {
            $taxRateBuilder->withRate(rate: $taxRateData['rate']);
        }
        if ($taxRateData['tax_country_id'] ?? null) {
            $taxRateBuilder->withCountryId(countryId: $taxRateData['tax_country_id']);
        }
        if (null !== ($taxRateData['tax_region_id'] ?? null)) {
            $taxRateBuilder->withRegionId(taxRegionId: $taxRateData['tax_region_id']);
        }
        if (null !== ($taxRateData['zip_is_range'] ?? null)) {
            $taxRateBuilder->withZipIsRange(zipIsRange: $taxRateData['zip_is_range']);
        }
        if (null !== ($taxRateData['zip_from'] ?? null)) {
            $taxRateBuilder->withZipFrom(zipFrom: $taxRateData['zip_from']);
        }
        if (null !== ($taxRateData['zip_to'] ?? null)) {
            $taxRateBuilder->withZipTo(zipTo: $taxRateData['zip_to']);
        }
        if ($taxRateData['tax_postcode'] ?? null) {
            $taxRateBuilder->withTaxPostCode(taxPostCode: $taxRateData['tax_postcode']);
        }
        $this->taxRateFixturePool->add(
            taxRate: $taxRateBuilder->build(),
            key: $taxRateData['key'] ?? 'tdd_tax_rate',
        );
    }
}
