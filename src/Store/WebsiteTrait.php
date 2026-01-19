<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use TddWizard\Fixtures\Exception\FixturePoolMissingException;

trait WebsiteTrait
{
    private ?WebsiteFixturePool $websiteFixturePool = null;

    /**
     * @param array<string, mixed> $websiteData
     *
     * @throws FixturePoolMissingException
     * @throws \Exception
     */
    public function createWebsite(array $websiteData = []): void
    {
        if (null === $this->websiteFixturePool) {
            throw new FixturePoolMissingException(
                message: 'websiteFixturePool has not been created in your test setUp method.',
            );
        }
        $this->removeExistingWebsiteWithSameCode(websiteData: $websiteData);

        $websiteBuilder = WebsiteBuilder::addWebsite();
        if (!empty($websiteData['code'])) {
            $websiteBuilder->withCode(code: $websiteData['code']);
        }
        if (!empty($websiteData['name'])) {
            $websiteBuilder->withName(name: $websiteData['name']);
        }
        if (!empty($websiteData['default_group_id'])) {
            $websiteBuilder->withDefaultGroupId(groupId: $websiteData['default_group_id']);
        }
        $this->websiteFixturePool->add(
            website: $websiteBuilder->build(),
            key: $websiteData['key'] ?? WebsiteBuilder::DEFAULT_CODE,
        );
    }

    /**
     * @param mixed[] $websiteData
     *
     * @throws \Exception
     */
    private function removeExistingWebsiteWithSameCode(array $websiteData): void
    {
        try {
            $websiteFixture = $this->websiteFixturePool->get(
                key: $websiteData['code'] ?? WebsiteBuilder::DEFAULT_CODE,
            );
            $websiteFixture->rollback();
        } catch (\OutOfBoundsException) {
            // this is fine website with code could not be loaded
        }
    }
}
