<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\Data\CustomerInterface;

class CustomerFixturePool
{
    /**
     * @var CustomerFixture[]
     */
    private array $customerFixtures = [];

    public function add(CustomerInterface $customer, string $key = null): void
    {
        if ($key === null) {
            $this->customerFixtures[] = new CustomerFixture(customer: $customer);
        } else {
            $this->customerFixtures[$key] = new CustomerFixture(customer: $customer);
        }
    }

    /**
     * Returns customer fixture by key, or last added if key not specified
     *
     * @throws \OutOfBoundsException
     */
    public function get(string|int|null $key = null): CustomerFixture
    {
        if ($key === null) {
            $key = \array_key_last(array: $this->customerFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->customerFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching customer found in fixture pool');
        }

        return $this->customerFixtures[$key];
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        CustomerFixtureRollback::create()->execute(
            ...array_values(array: $this->customerFixtures),
        );
        $this->customerFixtures = [];
    }
}
