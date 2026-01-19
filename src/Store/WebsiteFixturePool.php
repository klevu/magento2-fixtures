<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Store\Api\Data\WebsiteInterface;

class WebsiteFixturePool
{
    /**
     * @var WebsiteFixture[]
     */
    private array $websiteFixtures = [];

    public function add(WebsiteInterface $website, ?string $key = null): void
    {
        if ($key === null) {
            $this->websiteFixtures[] = new WebsiteFixture(website: $website);
        } else {
            $this->websiteFixtures[$key] = new WebsiteFixture(website: $website);
        }
    }

    /**
     * Returns website fixture by key, or last added if key not specified
     *
     * @throws \OutOfBoundsException
     */
    public function get(string|int|null $key = null): WebsiteFixture
    {
        if ($key === null) {
            $key = array_key_last(array: $this->websiteFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->websiteFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching website found in fixture pool');
        }

        return $this->websiteFixtures[$key];
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        WebsiteFixtureRollback::create()->execute(
            ...array_values(array: $this->websiteFixtures),
        );
        $this->websiteFixtures = [];
    }
}

