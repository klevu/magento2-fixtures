<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Tax\Api\Data\TaxRateInterface;

class TaxRateFixturePool
{
    /**
     * @var TaxRateFixture[]
     */
    private array $taxRateFixtures = [];

    public function add(TaxRateInterface $taxRate, ?string $key = null): void
    {
        if ($key === null) {
            $this->taxRateFixtures[] = new TaxRateFixture(taxRate: $taxRate);
        } else {
            $this->taxRateFixtures[$key] = new TaxRateFixture(taxRate: $taxRate);
        }
    }

    /**
     * Returns tax rate fixture by key, or last added if key not specified
     */
    public function get(string|int|null $key = null): TaxRateFixture
    {
        if ($key === null) {
            $key = array_key_last(array: $this->taxRateFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->taxRateFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching tax rate found in fixture pool');
        }

        return $this->taxRateFixtures[$key];
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        TaxRateFixtureRollback::create()->execute(
            ...array_values($this->taxRateFixtures),
        );
        $this->taxRateFixtures = [];
    }
}
