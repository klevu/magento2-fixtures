<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\InvalidModelException;

class StoreFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly StoreRepositoryInterface $storeRepository,
    ) {
    }

    public static function create(): StoreFixtureRollback //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(type: Registry::class),
            storeRepository: $objectManager->get(type: StoreRepositoryInterface::class),
        );
    }

    /**
     * @throws \Exception
     */
    public function execute(StoreFixture ...$storeFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($storeFixtures as $storeFixture) {
            try {
                /** @var StoreInterface & AbstractModel $store */
                $store = $this->storeRepository->get(code: (string)$storeFixture->getCode());
                if (!method_exists(object_or_class: $store, method: 'getResource')) {
                    throw new InvalidModelException(
                        message: sprintf(
                            'Provided Model %s does not have require method %s.',
                            $store::class,
                            'getResource',
                        ),
                    );
                }
                // store repository has no delete methods so revert to resourceModel
                $storeResourceModel = $store->getResource();
                if (!($storeResourceModel instanceof StoreResourceModel)) {
                    throw new InvalidModelException(
                        message: sprintf(
                            'Resource Model %s is not an instance of %s.',
                            $storeResourceModel::class,
                            StoreResourceModel::class,
                        ),
                    );
                }
                $storeResourceModel->delete($store);
            } catch (NoSuchEntityException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // store has already been removed
            }
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
