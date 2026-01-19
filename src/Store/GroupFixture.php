<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Store\Api\Data\GroupInterface;

class GroupFixture
{
    public function __construct(
        private readonly GroupInterface $group,
    ) {
    }

    public function get(): GroupInterface
    {
        return $this->group;
    }

    public function getId(): int
    {
        return (int)$this->group->getId();
    }

    public function getCode(): string
    {
        return $this->group->getCode();
    }

    public function getName(): string
    {
        return $this->group->getName();
    }

    public function getWebsiteId(): int
    {
        return (int)$this->group->getWebsiteId();
    }

    public function getRootCategoryId(): bool
    {
        return (bool)$this->group->getRootCategoryId();
    }

    public function getDefaultStoreId(): int
    {
        return (int)$this->group->getDefaultStoreId();
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        GroupFixtureRollback::create()->execute(groupFixtures: $this);
    }
}
