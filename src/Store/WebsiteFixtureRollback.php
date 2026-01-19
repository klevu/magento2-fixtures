<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\ResourceModel\Website as WebsiteResourceModel;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\InvalidModelException;

class WebsiteFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly WebsiteRepositoryInterface $websiteRepository,
    ) {
    }

    public static function create(): WebsiteFixtureRollback //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(type: Registry::class),
            websiteRepository: $objectManager->get(type: WebsiteRepositoryInterface::class),
        );
    }

    /**
     * @throws InvalidModelException
     * @throws \Exception
     */
    public function execute(WebsiteFixture ...$websiteFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($websiteFixtures as $websiteFixture) {
            try {
                /** @var WebsiteInterface|AbstractModel $website */
                $website = $this->websiteRepository->getById(id: (int)$websiteFixture->getId());
                if (!method_exists(object_or_class: $website, method: 'getResource')) {
                    throw new InvalidModelException(
                        message: sprintf(
                            'Provided Model %s does not have require method %s.',
                            $website::class,
                            'getResource',
                        ),
                    );
                }
                // website repository has no delete methods so revert to resourceModel
                $websiteResourceModel = $website->getResource();
                if (!($websiteResourceModel instanceof WebsiteResourceModel)) {
                    throw new InvalidModelException(
                        message: sprintf(
                            'Resource Model %s is not an instance of %s.',
                            $websiteResourceModel::class,
                            WebsiteResourceModel::class,
                        ),
                    );
                }
                $websiteResourceModel->delete($website);
            } catch (NoSuchEntityException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // website has already been removed
            }
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
