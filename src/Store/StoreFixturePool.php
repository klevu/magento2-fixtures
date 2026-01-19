<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Store\Api\Data\StoreInterface;

class StoreFixturePool
{
    /**
     * @var StoreFixture[]
     */
    private array $storeFixtures = [];

    public function add(StoreInterface $store, ?string $key = null): void
    {
        if ($key === null) {
            $this->storeFixtures[] = new StoreFixture(store: $store);
        } else {
            $this->storeFixtures[$key] = new StoreFixture(store: $store);
        }
    }

    /**
     * Returns store fixture by key, or last added if key not specified
     *
     * @throws \OutOfBoundsException
     */
    public function get(string|int|null $key = null): StoreFixture
    {
        if ($key === null) {
            $key = array_key_last(array: $this->storeFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->storeFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching store found in fixture pool');
        }

        return $this->storeFixtures[$key];
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        StoreFixtureRollback::create()->execute(
            ...array_values(array: $this->storeFixtures),
        );
        $this->storeFixtures = [];
    }
}
