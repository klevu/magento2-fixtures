<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Category;

use TddWizard\Fixtures\Exception\FixturePoolMissingException;

trait CategoryTrait
{
    private ?CategoryFixturePool $categoryFixturePool = null;

    /**
     * Example usage setting store level data
     * $this->createCategory(
     *   categoryData: [
     *     'name' => 'GLOBAL NAME',
     *     'stores' => [
     *        $store1->getId() => [
     *          'name' => 'NAME IN STORE 1',
     *        ],
     *        $store2->getId() => [
     *          'name' => 'NAME IN STORE 2',
     *        ],
     *      ],
     *   ],
     * );
     *
     * @param array<string, mixed> $categoryData
     *
     * @throws FixturePoolMissingException
     * @throws \Exception
     */
    public function createCategory(array $categoryData = []): void
    {
        if (null === $this->categoryFixturePool) {
            throw new FixturePoolMissingException(
                message: 'categoryFixturePool has not been created in your test setUp method.',
            );
        }
        if ($categoryData['parent'] ?? null) {
            $categoryBuilder = CategoryBuilder::childCategoryOf(parent: $categoryData['parent']);
        } else {
            $categoryBuilder = ($categoryData['root_id'] ?? null) === 1
                ? CategoryBuilder::rootCategory()
                : CategoryBuilder::topLevelCategory(rootCategoryId: $categoryData['root_id'] ?? null);
        }

        if (!empty($categoryData['name'])) {
            $categoryBuilder = $categoryBuilder->withName(Name: $categoryData['name']);
        }
        if (!empty($categoryData['description'])) {
            $categoryBuilder = $categoryBuilder->withDescription(Description: $categoryData['description']);
        }
        if (!empty($categoryData['url_key'])) {
            $categoryBuilder = $categoryBuilder->withUrlKey(urlKey: $categoryData['url_key']);
        }
        if (isset($categoryData['is_active'])) {
            $categoryBuilder = $categoryBuilder->withIsActive(isActive: $categoryData['is_active']);
        }
        if (isset($categoryData['is_anchor'])) {
            $categoryBuilder = $categoryBuilder->withIsAnchor(isAnchor: $categoryData['is_anchor']);
        }
        if (isset($categoryData['display_mode'])) {
            $categoryBuilder = $categoryBuilder->withDisplayMode(displayMode: $categoryData['display_mode']);
        }
        if (!empty($categoryData['products'])) {
            $categoryBuilder = $categoryBuilder->withProducts(skus: $categoryData['products']);
        }
        if (!empty($categoryData['custom_attributes'])) {
            $categoryBuilder = $categoryBuilder->withCustomAttributes(values: $categoryData['custom_attributes']);
        }
        if (!empty($categoryData['image'])) {
            $categoryBuilder = $categoryBuilder->withImage(fileName: $categoryData['image']);
        }
        if (isset($categoryData['store_id'])) {
            $categoryBuilder = $categoryBuilder->withStoreId(storeId: $categoryData['store_id']);
        }
        if (!empty($categoryData['stores'])) {
            foreach ($categoryData['stores'] as $storeIdKey => $categoryStoreData) {
                if (!empty($categoryData['name'])) {
                    $categoryBuilder = $categoryBuilder->withName(
                        name: $categoryStoreData['name'],
                        storeId: $storeIdKey,
                    );
                }
                if (!empty($categoryStoreData['description'])) {
                    $categoryBuilder = $categoryBuilder->withDescription(
                        description: $categoryStoreData['description'],
                        storeId: $storeIdKey,
                    );
                }
                if (!empty($categoryStoreData['url_key'])) {
                    $categoryBuilder = $categoryBuilder->withUrlKey(
                        urlKey: $categoryStoreData['url_key'],
                        storeId: $storeIdKey,
                    );
                }
                if (isset($categoryData['is_active'])) {
                    $categoryBuilder = $categoryBuilder->withIsActive(
                        isActive: $categoryStoreData['is_active'],
                        storeId: $storeIdKey,
                    );
                }
                if (!empty($categoryStoreData['custom_attributes'])) {
                    $categoryBuilder = $categoryBuilder->withCustomAttributes(
                        values: $categoryStoreData['custom_attributes'],
                        storeId: $storeIdKey,
                    );
                }
            }
        }

        $this->categoryFixturePool->add(
            category: $categoryBuilder->build(),
            key: $categoryData['key'] ?? 'tdd_category',
        );
    }
}
