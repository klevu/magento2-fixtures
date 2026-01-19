<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\Data\GroupInterface;

class CustomerGroupFixture
{
    public function __construct(
        private readonly GroupInterface $group,
    ) {
    }

    public function getCustomerGroup(): GroupInterface
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

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        CustomerGroupFixtureRollback::create()->execute(customerGroupFixtures: $this);
    }
}
