<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Store\Api\Data\GroupInterface;

class GroupFixturePool
{
    /**
     * @var GroupFixture[]
     */
    private array $groupFixtures = [];

    public function add(GroupInterface $group, ?string $key = null): void
    {
        if ($key === null) {
            $this->groupFixtures[] = new GroupFixture(group: $group);
        } else {
            $this->groupFixtures[$key] = new GroupFixture(group: $group);
        }
    }

    /**
     * Returns store group fixture by key, or last added if key not specified
     *
     * @throws \OutOfBoundsException
     */
    public function get(string|int|null $key = null): GroupFixture
    {
        if ($key === null) {
            $key = array_key_last(array: $this->groupFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->groupFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching store group found in fixture pool');
        }

        return $this->groupFixtures[$key];
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        GroupFixtureRollback::create()->execute(
            ...array_values(array: $this->groupFixtures),
        );
        $this->groupFixtures = [];
    }
}
