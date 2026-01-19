<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Cms;

use Magento\Framework\Exception\LocalizedException;
use TddWizard\Fixtures\Exception\FixturePoolMissingException;

trait CmsPageTrait
{
    private ?CmsPageFixturePool $cmsPageFixturePool = null;

    /**
     * @param array<string, mixed> $pageData
     *
     * @throws FixturePoolMissingException
     * @throws LocalizedException
     */
    private function createPage(array $pageData = []): void
    {
        if (null === $this->cmsPageFixturePool) {
            throw new FixturePoolMissingException(
                message: 'cmsPageFixturePool has not been created in your test setUp method.',
            );
        }

        $pageBuilder = CmsPageBuilder::addPage();
        if (!empty($pageData['identifier'])) {
            $pageBuilder->withIdentifier(identifier: $pageData['identifier']);
        }
        if (!empty($pageData['title'])) {
            $pageBuilder->withTitle(title: $pageData['title']);
        }
        if (isset($pageData['is_active'])) {
            $pageBuilder->withIsActive(isActive: $pageData['is_active']);
        }
        if (isset($pageData['store_id'])) {
            $pageBuilder->withStoreId(storeId: $pageData['store_id']);
        }
        if (isset($pageData['stores'])) {
            $pageBuilder->withStores(stores: $pageData['stores']);
        }
        if (isset($pageData['data'])) {
            $pageBuilder->withData(data: $pageData['data']);
        }

        $this->cmsPageFixturePool->add(
            page: $pageBuilder->build(),
            key: $pageData['key'] ?? 'tdd_page',
        );
    }
}
