<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use TddWizard\Fixtures\Exception\FixturePoolMissingException;

trait GroupTrait
{
    private ?GroupFixturePool $storeGroupFixturePool = null;

    /**
     * @param array<string, mixed> $groupData
     *
     * @throws FixturePoolMissingException
     * @throws \Exception
     */
    private function createStoreGroup(array $groupData = []): void
    {
        if (null === $this->storeGroupFixturePool) {
            throw new FixturePoolMissingException(
                message: 'storeGroupFixturePool has not been created in your test setUp method.',
            );
        }
        $storeGroupBuilder = GroupBuilder::addGroup();
        if (!empty($groupData['code'])) {
            $storeGroupBuilder = $storeGroupBuilder->withCode(code: $groupData['code']);
        }
        if (!empty($groupData['name'])) {
            $storeGroupBuilder = $storeGroupBuilder->withName(name: $groupData['name']);
        }
        if (isset($groupData['website_id'])) {
            $storeGroupBuilder = $storeGroupBuilder->withWebsiteId(websiteId: $groupData['website_id']);
        }
        if (isset($groupData['root_category_id'])) {
            $storeGroupBuilder = $storeGroupBuilder->withRootCategoryId(categoryId: $groupData['root_category_id']);
        }

        $this->storeGroupFixturePool->add(
            group: $storeGroupBuilder->build(),
            key: $groupData['key'] ?? GroupBuilder::DEFAULT_CODE,
        );
    }
}
