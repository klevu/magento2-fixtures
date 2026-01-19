<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Cms;

use Magento\Cms\Api\Data\PageInterface;

class CmsPageFixturePool
{
    /**
     * @var CmsPageFixture[]
     */
    private array $pageFixtures = [];

    public function add(PageInterface $page, ?string $key = null): void
    {
        if ($key === null) {
            $this->pageFixtures[] = new CmsPageFixture(page: $page);
        } else {
            $this->pageFixtures[$key] = new CmsPageFixture(page: $page);
        }
    }

    /**
     * Returns page fixture by key, or last added if key not specified
     */
    public function get(string|int|null $key = null): CmsPageFixture
    {
        if ($key === null) {
            $key = array_key_last(array: $this->pageFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->pageFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching page found in fixture pool');
        }

        return $this->pageFixtures[$key];
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        CmsPageFixtureRollback::create()->execute(
            ...array_values(array: $this->pageFixtures),
        );
        $this->pageFixtures = [];
    }
}
