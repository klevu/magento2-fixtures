<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Tax\Model\ClassModel as TaxClass;
use TddWizard\Fixtures\Exception\FixturePoolMissingException;

trait TaxClassTrait
{
    private ?TaxClassFixturePool $taxClassFixturePool = null;

    /**
     * @param array<string, string> $taxClassData
     *
     * @throws \Exception
     */
    public function createTaxClass(array $taxClassData = []): void
    {
        if (null === $this->taxClassFixturePool) {
            throw new FixturePoolMissingException(
                message: 'taxClassFixturePool has not been created in your test setUp method.',
            );
        }
        $taxClassBuilder = TaxClassBuilder::addTaxClass();
        $taxClassBuilder->withClassName(
            className: $taxClassData['class_name'] ?? 'TDD Product Tax Class',
        );
        $taxClassBuilder->withClassType(
            classType: $taxClassData['class_type'] ?? TaxClass::TAX_CLASS_TYPE_PRODUCT,
        );

        $this->taxClassFixturePool->add(
            taxClass: $taxClassBuilder->build(),
            key: $taxClassData['key'] ?? 'tdd_tax_class',
        );
    }
}
