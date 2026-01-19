<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Attribute;

use Magento\Eav\Api\Data\AttributeInterface;

class AttributeFixturePool
{
    /**
     * @var array<int|string, AttributeFixture>
     */
    private array $attributeFixtures = [];

    public function add(AttributeInterface $attribute, ?string $key = null): void
    {
        if ($key === null) {
            $this->attributeFixtures[] = new AttributeFixture(attribute: $attribute);
        } else {
            $this->attributeFixtures[$key] = new AttributeFixture(attribute: $attribute);
        }
    }

    /**
     * Returns store fixture by key, or last added if key not specified
     *
     * @throws \OutOfBoundsException
     */
    public function get(string|int|null $key = null): AttributeFixture
    {
        if ($key === null) {
            $key = array_key_last(array: $this->attributeFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->attributeFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching attribute found in fixture pool');
        }

        return $this->attributeFixtures[$key];
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        AttributeFixtureRollback::create()->execute(
            ...array_values(array: $this->attributeFixtures),
        );
        $this->attributeFixtures = [];
    }
}
