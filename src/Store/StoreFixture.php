<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Store\Api\Data\StoreInterface;

class StoreFixture
{
    public function __construct(
        private readonly StoreInterface $store,
    ) {
    }

    public function getStore(): StoreInterface
    {
        return $this->store;
    }

    public function getId(): int
    {
        return (int)$this->store->getId();
    }

    public function getCode(): string
    {
        return $this->store->getCode();
    }

    public function getName(): string
    {
        return $this->store->getName();
    }

    public function getWebsiteId(): int
    {
        return (int)$this->store->getWebsiteId();
    }

    public function getStoreGroupId(): int
    {
        return (int)$this->store->getStoreGroupId();
    }

    public function getIsActive(): bool
    {
        return (bool)$this->store->getIsActive();
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        StoreFixtureRollback::create()->execute(storeFixtures: $this);
    }
}
