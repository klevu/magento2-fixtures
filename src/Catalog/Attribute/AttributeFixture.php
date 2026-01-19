<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Attribute;

use Magento\Eav\Api\Data\AttributeInterface;

class AttributeFixture
{
    public function __construct(
        private readonly AttributeInterface $attribute,
    ) {
    }

    public function getAttribute(): AttributeInterface
    {
        return $this->attribute;
    }

    public function getAttributeId(): int
    {
        return (int)$this->attribute->getAttributeId();
    }

    public function getAttributeCode(): string
    {
        return $this->attribute->getAttributeCode();
    }

    public function rollback(): void
    {
        AttributeFixtureRollback::create()->execute($this);
    }
}
