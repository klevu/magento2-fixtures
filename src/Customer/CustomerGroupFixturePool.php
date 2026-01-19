<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\Data\GroupInterface;

class CustomerGroupFixturePool
{
    /**
     * @var CustomerGroupFixture[]
     */
    private array $customerGroupFixtures = [];

    public function add(GroupInterface $customerGroup, ?string $key = null): void
    {
        if ($key === null) {
            $this->customerGroupFixtures[] = new CustomerGroupFixture(group: $customerGroup);
        } else {
            $this->customerGroupFixtures[$key] = new CustomerGroupFixture(group: $customerGroup);
        }
    }

    /**
     * Returns customer group fixture by key, or last added if key not specified
     *
     * @throws \OutOfBoundsException
     */
    public function get(string|int|null $key = null): CustomerGroupFixture
    {
        if ($key === null) {
            $key = array_key_last(array: $this->customerGroupFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->customerGroupFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching customer group found in fixture pool');
        }

        return $this->customerGroupFixtures[$key];
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        CustomerGroupFixtureRollback::create()->execute(
            ...array_values(array: $this->customerGroupFixtures),
        );
        $this->customerGroupFixtures = [];
    }
}
