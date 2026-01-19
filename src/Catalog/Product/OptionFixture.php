<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Eav\Model\Entity\Attribute\Option as AttributeOption;
use Magento\Framework\Exception\LocalizedException;

class OptionFixture
{
    public function __construct(
        private readonly AttributeOption $option,
        private readonly string $attributeCode,
    ) {
    }

    public function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    public function getOption(): AttributeOption
    {
        return $this->option;
    }

    /**
     * @throws LocalizedException
     */
    public function rollback(): void
    {
        OptionFixtureRollback::create()->execute(optionFixtures: $this);
    }
}
