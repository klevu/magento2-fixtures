<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Model\ResourceModel\Group as GroupResourceModel;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\InvalidModelException;

class GroupFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly GroupRepositoryInterface $storeGroupRepository,
    ) {
    }

    public static function create(): GroupFixtureRollback //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(type: Registry::class),
            storeGroupRepository: $objectManager->get(type: GroupRepositoryInterface::class),
        );
    }

    /**
     * Rollback store groups.
     *
     * @throws \Exception
     */
    public function execute(GroupFixture ...$groupFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($groupFixtures as $groupFixture) {
            try {
                /** @var GroupInterface & AbstractModel $storeGroup */
                $storeGroup = $this->storeGroupRepository->get(id: (int)$groupFixture->getId());
                if (!method_exists(object_or_class: $storeGroup, method: 'getResource')) {
                    throw new InvalidModelException(
                        message: sprintf(
                            'Provided Model %s does not have require method %s.',
                            $storeGroup::class,
                            'getResource',
                        ),
                    );
                }
                // store repository has no delete methods so revert to resourceModel
                $storeGroupResourceModel = $storeGroup->getResource();
                if (!($storeGroupResourceModel instanceof GroupResourceModel)) {
                    throw new InvalidModelException(
                        message: sprintf(
                            'Resource Model %s is not an instance of %s.',
                            $storeGroupResourceModel::class,
                            GroupResourceModel::class,
                        ),
                    );
                }
                $storeGroupResourceModel->delete($storeGroup);
            } catch (NoSuchEntityException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // store group has already been removed
            }
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
