<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class StoreBuilderTest extends TestCase
{
    private ObjectManagerInterface $objectManager;
    private StoreRepositoryInterface $storeRepository;
    /**
     * @var StoreFixture[]
     */
    private array $stores = [];

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->storeRepository = $this->objectManager->create(StoreRepositoryInterface::class);
        $this->stores = [];
    }

    /**
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        if (!empty($this->stores)) {
            foreach ($this->stores as $store) {
                StoreFixtureRollback::create()->execute($store);
            }
        }
    }

    public function testDefaultStore(): void
    {
        $storeFixture = new StoreFixture(
            store: StoreBuilder::addStore()->build(),
        );
        $this->stores[] = $storeFixture;

        $store = $this->storeRepository->getById(id: (int)$storeFixture->getId());

        $this->assertSame(expected: StoreBuilder::DEFAULT_CODE, actual: $store->getCode());
        $this->assertSame(expected: $storeFixture->getName(), actual: $store->getName());
        $this->assertEquals(expected: $storeFixture->getWebsiteId(), actual: $store->getWebsiteId());
        $this->assertEquals(expected: $storeFixture->getStoreGroupId(), actual: $store->getStoreGroupId());
        $this->assertEquals(expected: $storeFixture->getIsActive(), actual: $store->getIsActive());
    }

    public function testStoreWithSpecificAttributes(): void
    {
        $storeBuilder = StoreBuilder::addStore();
        $storeBuilder->withCode(code: 'tdd_test_store_abc');
        $storeBuilder->withName(name: 'ABC TDD Test Store');
        $storeBuilder->withIsActive(isActive: false);

        $storeFixture = new StoreFixture(
            store: $storeBuilder->build(),
        );
        $this->stores[] = $storeFixture;

        $store = $this->storeRepository->getById(id: (int)$storeFixture->getId());

        $this->assertSame(expected: 'tdd_test_store_abc', actual: $store->getCode());
        $this->assertSame(expected: 'ABC TDD Test Store', actual: $store->getName());
        $this->assertEquals(expected: $storeFixture->getWebsiteId(), actual: $store->getWebsiteId());
        $this->assertEquals(expected: $storeFixture->getStoreGroupId(), actual: $store->getStoreGroupId());
        $this->assertEquals(expected: 0, actual: $store->getIsActive());
    }
}
