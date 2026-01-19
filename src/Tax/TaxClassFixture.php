<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Tax\Api\Data\TaxClassInterface;

class TaxClassFixture
{
    public function __construct(
        private readonly TaxClassInterface $taxClass,
    ) {
    }

    public function getTaxClass(): TaxClassInterface
    {
        return $this->taxClass;
    }

    public function getId(): int
    {
        return (int)$this->taxClass->getId();
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        TaxClassFixtureRollback::create()->execute(taxClassFixtures: $this);
    }
}
