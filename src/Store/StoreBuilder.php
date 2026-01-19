<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Sequence as DdlSequence;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\SalesSequence\Model\EntityPool as SalesSequenceEntityPool;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\IndexFailedException;
use TddWizard\Fixtures\Traits\IsTransactionExceptionTrait;

class StoreBuilder
{
    use IsTransactionExceptionTrait;

    public const DEFAULT_CODE = 'tdd_store';

    private bool $withSequence = false;

    public function __construct(
        private readonly StoreInterface & AbstractModel $store,
        private readonly ResourceConnection $resourceConnection,
        private readonly DdlSequence $ddlSequence,
        private readonly SalesSequenceEntityPool $salesSequenceEntityPool,
        private readonly WebsiteRepositoryInterface $websiteRepository,
        private readonly GroupRepositoryInterface $groupRepository,
    ) {
    }

    public static function addStore(): StoreBuilder //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            $objectManager->create(type: StoreInterface::class),
            $objectManager->create(type: ResourceConnection::class),
            $objectManager->create(type: DdlSequence::class),
            $objectManager->get(type: SalesSequenceEntityPool::class),
            $objectManager->get(type: WebsiteRepositoryInterface::class),
            $objectManager->get(type: GroupRepositoryInterface::class),
        );
    }

    public function withCode(string $code): StoreBuilder
    {
        $builder = clone $this;
        $builder->store->setCode(code: $code);

        return $builder;
    }

    public function withName(string $name): StoreBuilder
    {
        $builder = clone $this;
        $builder->store->setName(name: $name);

        return $builder;
    }

    public function withWebsiteId(int $websiteId): StoreBuilder
    {
        $builder = clone $this;
        $builder->store->setWebsiteId(websiteId: $websiteId);

        return $builder;
    }

    public function withGroupId(int $groupId): StoreBuilder
    {
        $builder = clone $this;
        $builder->store->setStoreGroupId(storeGroupId: $groupId);

        return $builder;
    }

    public function withIsActive(bool $isActive): StoreBuilder
    {
        $builder = clone $this;
        $builder->store->setIsActive(isActive: $isActive);

        return $builder;
    }

    public function withSequence(bool $withSequence = false): void
    {
        $this->withSequence = $withSequence;
    }

    /**
     * @throws \Exception
     */
    public function build(): StoreInterface
    {
        try {
            return $this->saveStore(
                builder: $this->createStore(),
            );
        } catch (\Exception $exception) {
            if (
                self::isTransactionException(exception: $exception)
                || self::isTransactionException(exception: $exception->getPrevious())
            ) {
                throw IndexFailedException::becauseInitiallyTriggeredInTransaction(previous: $exception);
            }
            throw $exception;
        }
    }

    /**
     * @throws NoSuchEntityException
     */
    public function buildWithoutSave(): StoreInterface
    {
        $builder = $this->createStore();

        return $builder->store;
    }

    /**
     * @throws NoSuchEntityException
     */
    private function createStore(): StoreBuilder
    {
        $builder = clone $this;

        if (!$builder->store->getCode()) {
            $builder->store->setCode(code: static::DEFAULT_CODE);
        }
        if (!$builder->store->getName()) {
            $builder->store->setName(
                name: ucwords(
                    string: str_replace(search: ['_', '-'], replace: ' ', subject: $builder->store->getCode()),
                ),
            );
        }
        if (null === $builder->store->getIsActive()) {
            $builder->store->setIsActive(isActive: true);
        }
        if (null === $builder->store->getWebsiteId()) {
            if (null !== $builder->store->getStoreGroupId()) {
                $group = $this->groupRepository->get(id: (int)$builder->store->getStoreGroupId());
                $websiteId = $group->getWebsiteId();
            } else {
                $defaultWebsite = $this->websiteRepository->getDefault();
                $websiteId = $defaultWebsite->getId();
            }
            $builder->store->setWebsiteId(websiteId: (int)$websiteId);
        }
        if (null === $builder->store->getStoreGroupId()) {
            $website = $this->websiteRepository->getById(id: (int)$builder->store->getWebsiteId());
            $builder->store->setStoreGroupId(storeGroupId: (int)$website->getDefaultGroupId());
        }

        return $builder;
    }

    /**
     * @throws AlreadyExistsException
     */
    private function saveStore(StoreBuilder $builder): StoreInterface
    {
        // store repository has no save methods so revert to resourceModel
        /** @var StoreResourceModel $storeResourceModel */
        $storeResourceModel = $this->store->getResource();
        $storeResourceModel->save(object: $builder->store);
        if ($this->withSequence) {
            $this->createSequenceTables(store: $builder->store);
        }

        return $builder->store;
    }

    private function createSequenceTables(StoreInterface $store): void
    {
        $connection = $this->resourceConnection->getConnection(resourceName: 'sales');
        foreach ($this->salesSequenceEntityPool->getEntities() as $entityType) {
            $sequenceTableName = $this->resourceConnection->getTableName(
                modelEntity: sprintf(
                    'sequence_%s_%s',
                    $entityType,
                    $store->getId(),
                ),
            );

            if (!$connection->isTableExists($sequenceTableName)) {
                $connection->query(
                    sql: $this->ddlSequence->getCreateSequenceDdl(
                        name: $sequenceTableName,
                    ),
                );
                $connection->insertOnDuplicate(
                    table: $this->resourceConnection->getTableName(
                        modelEntity: 'sales_sequence_meta',
                    ),
                    data: [
                        'entity_type' => $entityType,
                        'store_id' => $store->getId(),
                        'sequence_table' => $sequenceTableName,
                    ],
                );
                $select = $connection->select()
                    ->from(
                        name: $this->resourceConnection->getTableName(
                            modelEntity: 'sales_sequence_meta',
                        ),
                        cols: ['meta_id'],
                    )->where(
                        cond: 'store_id = ?',
                        value: $store->getId(),
                    )->where(
                        cond: 'sequence_table = ?',
                        value: $sequenceTableName,
                    );
                $result = $connection->fetchRow($select);

                $connection->insertOnDuplicate(
                    table: $this->resourceConnection->getTableName(
                        modelEntity: 'sales_sequence_profile',
                    ),
                    data: [
                        'meta_id' => $result['meta_id'],
                        'is_active' => 1,
                    ],
                );
            }
        }
    }
}
