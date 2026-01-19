<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class CustomerFixturePoolTest extends TestCase
{
    private CustomerFixturePool $customerFixtures;
    private CustomerRepositoryInterface $customerRepository;
    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerFixtures = new CustomerFixturePool();
        $this->customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);
    }

    public function testLastCustomerFixtureReturnedByDefault(): void
    {
        $firstCustomer = $this->createCustomer();
        $lastCustomer = $this->createCustomer();
        $this->customerFixtures->add($firstCustomer);
        $this->customerFixtures->add($lastCustomer);
        $customerFixture = $this->customerFixtures->get();
        $this->assertEquals($lastCustomer->getId(), $customerFixture->getId());
    }

    public function testExceptionThrownWhenAccessingEmptyCustomerPool(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->customerFixtures->get();
    }

    public function testCustomerFixtureReturnedByKey(): void
    {
        $firstCustomer = $this->createCustomer();
        $lastCustomer = $this->createCustomer();
        $this->customerFixtures->add($firstCustomer, 'first');
        $this->customerFixtures->add($lastCustomer, 'last');
        $customerFixture = $this->customerFixtures->get('first');
        $this->assertEquals($firstCustomer->getId(), $customerFixture->getId());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $customer = $this->createCustomer();
        $this->customerFixtures->add($customer, 'foo');
        $this->expectException(\OutOfBoundsException::class);
        $this->customerFixtures->get('bar');
    }

    /**
     * @throws LocalizedException
     */
    public function testRollbackRemovesCustomersFromPool(): void
    {
        $customer = $this->createCustomerInDb();
        $this->customerFixtures->add($customer);
        $this->customerFixtures->rollback();
        $this->expectException(\OutOfBoundsException::class);
        $this->customerFixtures->get();
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testRollbackDeletesCustomersFromDb(): void
    {
        $customer = $this->createCustomerInDb();
        $this->customerFixtures->add($customer);
        $this->customerFixtures->rollback();
        $this->expectException(NoSuchEntityException::class);
        $this->customerRepository->get($customer->getId());
    }

    /**
     * Creates a dummy customer object
     */
    private function createCustomer(): CustomerInterface
    {
        static $nextId = 1;
        /** @var CustomerInterface $customer */
        $customer = $this->objectManager->create(CustomerInterface::class);
        $customer->setId($nextId++);

        return $customer;
    }

    /**
     * Uses builder to create a customer
     *
     * @throws LocalizedException
     */
    private function createCustomerInDb(): CustomerInterface
    {
        return CustomerBuilder::aCustomer()->build();
    }
}
