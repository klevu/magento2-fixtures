<?php
declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use function array_values as values;

class CustomerFixturePool
{

    /**
     * @var CustomerFixture[]
     */
    private $customerFixtures = [];

    /**
     * @param CustomerInterface $customer
     * @param string|null $key
     *
     * @return void
     */
    public function add(CustomerInterface $customer, ?string $key = null): void
    {
        if ($key === null) {
            $this->customerFixtures[] = new CustomerFixture($customer);
        } else {
            $this->customerFixtures[$key] = new CustomerFixture($customer);
        }
    }

    /**
     * Returns customer fixture by key, or last added if key not specified
     *
     * @param string|null $key
     * @return CustomerFixture
     */
    public function get(?string $key = null): CustomerFixture
    {
        if ($key === null) {
            $key = \array_key_last($this->customerFixtures);
        }
        if ($key === null || !array_key_exists($key, $this->customerFixtures)) {
            throw new \OutOfBoundsException('No matching customer found in fixture pool');
        }
        return $this->customerFixtures[$key];
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function rollback(): void
    {
        CustomerFixtureRollback::create()->execute(...values($this->customerFixtures));
        $this->customerFixtures = [];
    }
}
