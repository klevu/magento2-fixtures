<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\Data\GroupInterface as CustomerGroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface as CustomerGroupRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CustomerGroupFixturePoolTest extends TestCase
{
    private CustomerGroupFixturePool $customerGroupFixtures;
    private CustomerGroupRepositoryInterface $customerGroupRepository;
    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerGroupFixtures = new CustomerGroupFixturePool();
        $this->customerGroupRepository = $this->objectManager->create(type: CustomerGroupRepositoryInterface::class);
    }

    public function testLastCustomerGroupFixtureReturnedByDefault(): void
    {
        $firstCustomerGroup = $this->createCustomerGroup();
        $lastCustomerGroup = $this->createCustomerGroup();
        $this->customerGroupFixtures->add(customerGroup: $firstCustomerGroup);
        $this->customerGroupFixtures->add(customerGroup: $lastCustomerGroup);
        $customerGroupFixture = $this->customerGroupFixtures->get();
        $this->assertEquals($lastCustomerGroup->getId(), $customerGroupFixture->getId());
    }

    public function testExceptionThrownWhenAccessingEmptyCustomerGroupPool(): void
    {
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->customerGroupFixtures->get();
    }

    public function testCustomerGroupFixtureReturnedByKey(): void
    {
        $firstCustomerGroup = $this->createCustomerGroup();
        $lastCustomerGroup = $this->createCustomerGroup();
        $this->customerGroupFixtures->add(customerGroup: $firstCustomerGroup, key: 'first');
        $this->customerGroupFixtures->add(customerGroup: $lastCustomerGroup, key: 'last');
        $customerGroupFixture = $this->customerGroupFixtures->get(key: 'first');
        $this->assertEquals($firstCustomerGroup->getId(), $customerGroupFixture->getId());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $customerGroup = $this->createCustomerGroup();
        $this->customerGroupFixtures->add(customerGroup: $customerGroup, key: 'foo');
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->customerGroupFixtures->get(key: 'bar');
    }

    public function testRollbackRemovesCustomerGroupsFromPool(): void
    {
        $customerGroup = $this->createCustomerGroupInDb();
        $this->customerGroupFixtures->add(customerGroup: $customerGroup);
        $this->customerGroupFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->customerGroupFixtures->get();
    }

    public function testRollbackDeletesCustomerGroupsFromDb(): void
    {
        $customerGroup = $this->createCustomerGroupInDb();
        $this->customerGroupFixtures->add(customerGroup: $customerGroup);
        $this->customerGroupFixtures->rollback();
        $this->expectException(exception: NoSuchEntityException::class);
        $this->customerGroupRepository->getById(id: $customerGroup->getId());
    }

    /**
     * Creates a dummy customerGroup object
     */
    private function createCustomerGroup(): CustomerGroupInterface
    {
        static $nextId = 1;
        $customerGroup = $this->objectManager->create(type: CustomerGroupInterface::class);
        $customerGroup->setId($nextId++);

        return $customerGroup;
    }

    private function createCustomerGroupInDb(): CustomerGroupInterface
    {
        return CustomerGroupBuilder::addCustomerGroup()->build();
    }
}
