<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Tax\Api\Data\TaxRateInterface;

class TaxRateFixture
{
    public function __construct(
        private readonly TaxRateInterface $taxRate,
    ) {
    }

    public function getTaxRate(): TaxRateInterface
    {
        return $this->taxRate;
    }

    public function getId(): int
    {
        return (int)$this->taxRate->getId();
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        TaxRateFixtureRollback::create()->execute(taxRateFixtures: $this);
    }
}
