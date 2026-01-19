<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class StoreFixturePoolTest extends TestCase
{
    private ObjectManagerInterface $objectManager;
    private StoreFixturePool $storeFixturesPool;

    private StoreRepositoryInterface $storeRepository;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->storeFixturesPool = new StoreFixturePool();
        $this->storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);
    }

    public function testLastStoreFixtureReturnedByDefault(): void
    {
        $firstStore = $this->createStore();
        $lastStore = $this->createStore();

        $this->storeFixturesPool->add(store: $firstStore);
        $this->storeFixturesPool->add(store: $lastStore);
        $storeFixture = $this->storeFixturesPool->get();
        $this->assertSame(expected: $lastStore->getCode(), actual: $storeFixture->getCode());
    }

    public function testStoreFixtureReturnedByKey(): void
    {
        $firstStore = $this->createStore();
        $lastStore = $this->createStore();

        $this->storeFixturesPool->add(store: $firstStore, key: 'first');
        $this->storeFixturesPool->add(store: $lastStore, key: 'last');
        $storeFixture = $this->storeFixturesPool->get(key: 'first');
        $this->assertSame(expected: $firstStore->getCode(), actual: $storeFixture->getCode());
    }

    public function testStoreFixtureReturnedByNumericalKey(): void
    {
        $firstStore = $this->createStore();
        $lastStore = $this->createStore();

        $this->storeFixturesPool->add(store: $firstStore);
        $this->storeFixturesPool->add(store: $lastStore);
        $storeFixture = $this->storeFixturesPool->get(key: 0);
        $this->assertSame(expected: $firstStore->getCode(), actual: $storeFixture->getCode());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $store = $this->createStore();
        $this->storeFixturesPool->add(store: $store, key: 'foo');
        $this->storeFixturesPool->get(key: 'bar');
    }

    public function testRollbackRemovesStoresFromPool(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $this->storeFixturesPool->add(store: $this->createStore());
        $this->storeFixturesPool->rollback();
        $this->storeFixturesPool->get();
    }

    public function testRollbackWorksWithKeys(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $this->storeFixturesPool->add(store: $this->createStore(), key: 'first');
        $this->storeFixturesPool->rollback();
        $this->storeFixturesPool->get();
    }

    /**
     * @magentoDbIsolation disabled
     */
    public function testRollbackDeletesStoresFromDb(): void
    {
        $store = $this->createStoreInDb();
        $this->storeFixturesPool->add(store: $store);
        $storeInDb = $this->storeRepository->getById(id: (int)$store->getId());
        $this->assertSame(expected: $store->getCode(), actual: $storeInDb->getCode());

        $this->storeFixturesPool->rollback();
        $this->storeRepository->clean();
        $this->expectException(NoSuchEntityException::class);
        $this->storeRepository->getById(id: (int)$store->getId());
    }

    private function createStore(): StoreInterface & AbstractModel
    {
        $lastStoreId = array_key_last(array: $this->getStoresById());
        $store = $this->objectManager->create(type: StoreInterface::class);
        $store->setCode(code: 'tdd_store_' . ($lastStoreId + 1));
        $store->setName(name: 'Tdd store');

        return $store;
    }

    /**
     * @throws AlreadyExistsException
     */
    private function createStoreInDb(): StoreInterface & AbstractModel
    {
        $store = $this->createStore();
        // store repository does not have save method so reverting to resourceModel
        $storeResourceModel = $store->getResource();
        $storeResourceModel->save($store);

        return $store;
    }

    /**
     * @return array<int, StoreInterface>
     */
    private function getStoresById(): array
    {
        $stores = $this->storeRepository->getList();

        return array_combine(
            keys: array_map(
                callback: static fn (StoreInterface $store): int => (int)$store->getId(),
                array: $stores,
            ),
            values: $stores,
        );
    }
}
