<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use TddWizard\Fixtures\Exception\FixturePoolMissingException;

trait CustomerGroupTrait
{
    private ?CustomerGroupFixturePool $customerGroupFixturePool = null;

    /**
     * @param array<string, mixed> $customerGroupData
     *
     * @throws \Exception
     */
    public function createCustomerGroup(array $customerGroupData = []): void
    {
        if (null === $this->customerGroupFixturePool) {
            throw new FixturePoolMissingException(
                message: 'customerGroupFixturePool has not been created in your test setUp method.',
            );
        }
        $customerGroupBuilder = CustomerGroupBuilder::addCustomerGroup();
        if (!empty($customerGroupData['code'])) {
            $customerGroupBuilder = $customerGroupBuilder->withCode(
                code: $customerGroupData['code'],
            );
        }
        if (!empty($customerGroupData['tax_class_id'])) {
            $customerGroupBuilder = $customerGroupBuilder->withTaxClassId(
                taxClassId: $customerGroupData['tax_class_id'],
            );
        }
        if (!empty($customerGroupData['excluded_website_ids'])) {
            $customerGroupBuilder = $customerGroupBuilder->withExcludedWebsiteIds(
                excludedIds: $customerGroupData['excluded_website_ids'],
            );
        }

        $this->customerGroupFixturePool->add(
            customerGroup: $customerGroupBuilder->build(),
            key: $customerGroupData['key'] ?? 'tdd_customer_group',
        );
    }
}
