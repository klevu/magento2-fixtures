<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use TddWizard\Fixtures\Exception\FixturePoolMissingException;

trait StoreTrait
{
    private ?StoreFixturePool $storeFixturePool = null;

    /**
     * @param array<string, mixed> $storeData
     *
     * @throws FixturePoolMissingException
     * @throws \Exception
     */
    private function createStore(array $storeData = []): void
    {
        if (null === $this->storeFixturePool) {
            throw new FixturePoolMissingException(
                message: 'storeFixturePool has not been created in your test setUp method.',
            );
        }
        $storeBuilder = StoreBuilder::addStore();
        if (!empty($storeData['code'])) {
            $storeBuilder = $storeBuilder->withCode(code: $storeData['code']);
        }
        if (!empty($storeData['name'])) {
            $storeBuilder = $storeBuilder->withName(name: $storeData['name']);
        }
        if (isset($storeData['website_id'])) {
            $storeBuilder = $storeBuilder->withWebsiteId(websiteId: $storeData['website_id']);
        }
        if (isset($storeData['group_id'])) {
            $storeBuilder = $storeBuilder->withGroupId(groupId: $storeData['group_id']);
        }
        if (isset($storeData['is_active'])) {
            $storeBuilder = $storeBuilder->withIsActive(isActive: $storeData['is_active']);
        }
        if (isset($storeData['with_sequence'])) {
            $storeBuilder->withSequence(withSequence: $storeData['with_sequence']);
        }

        $this->storeFixturePool->add(
            store: $storeBuilder->build(),
            key: $storeData['key'] ?? StoreBuilder::DEFAULT_CODE,
        );
    }
}
