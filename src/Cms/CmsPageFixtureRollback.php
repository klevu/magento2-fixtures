<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

//phpcs:disable Magento2.Annotation.MethodArguments.ArgumentMissing

namespace TddWizard\Fixtures\Cms;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

class CmsPageFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly PageRepositoryInterface $pageRepository,
    ) {
    }

    public static function create(): CmsPageFixtureRollback //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(type: Registry::class),
            pageRepository: $objectManager->get(type: PageRepositoryInterface::class),
        );
    }

    public function execute(CmsPageFixture ...$pageFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($pageFixtures as $pageFixture) {
            try {
                $this->pageRepository->deleteById(pageId: $pageFixture->getId());
            } catch (LocalizedException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // CMS page has already been removed
            }
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
