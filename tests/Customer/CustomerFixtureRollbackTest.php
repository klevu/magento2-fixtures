<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation  enabled
 */
class CustomerFixtureRollbackTest extends TestCase
{
    private CustomerRepositoryInterface $customerRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = Bootstrap::getObjectManager()->create(CustomerRepositoryInterface::class);
    }

    public function testRollbackSingleCustomerFixture(): void
    {
        $customerFixture = new CustomerFixture(
            CustomerBuilder::aCustomer()->build(),
        );
        CustomerFixtureRollback::create()->execute($customerFixture);
        $this->expectException(NoSuchEntityException::class);
        $this->customerRepository->getById($customerFixture->getId());
    }

    public function testRollbackMultipleCustomerFixtures(): void
    {
        $customerFixture = new CustomerFixture(
            CustomerBuilder::aCustomer()->build(),
        );
        $otherCustomerFixture = new CustomerFixture(
            CustomerBuilder::aCustomer()->build(),
        );
        CustomerFixtureRollback::create()->execute($customerFixture, $otherCustomerFixture);
        $customerDeleted = false;
        try {
            $this->customerRepository->getById($customerFixture->getId());
        } catch (NoSuchEntityException $e) {
            $customerDeleted = true;
        }
        $this->assertTrue($customerDeleted, 'First customer should be deleted');
        $this->expectException(NoSuchEntityException::class);
        $this->customerRepository->getById($otherCustomerFixture->getId());
    }
}
