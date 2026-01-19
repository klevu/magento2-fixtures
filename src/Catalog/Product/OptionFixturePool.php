<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Eav\Model\Entity\Attribute\Option as AttributeOption;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;

class OptionFixturePool
{
    /**
     * @var array<int|string, OptionFixture>
     */
    private array $optionFixtures = [];

    public function add(AttributeOption $option, string $attributeCode, string $key = null): void
    {
        if ($key === null) {
            $this->optionFixtures[] = new OptionFixture(option: $option, attributeCode: $attributeCode);
        } else {
            $this->optionFixtures[$key] = new OptionFixture(option: $option, attributeCode: $attributeCode);
        }
    }

    /**
     * Returns option fixture by key, or last added if key not specified
     */
    public function get(string|int|null $key = null): OptionFixture
    {
        if ($key === null) {
            $key = \array_key_last(array: $this->optionFixtures);
        }
        if ($key === null || !\array_key_exists(key: $key, array: $this->optionFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching option found in fixture pool');
        }

        return $this->optionFixtures[$key];
    }

    /**
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws InputException
     */
    public function rollback(): void
    {
        OptionFixtureRollback::create()->execute(
            ...array_values(array: $this->optionFixtures),
        );
        $this->optionFixtures = [];
    }
}
