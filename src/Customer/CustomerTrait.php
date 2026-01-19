<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Framework\Exception\LocalizedException;
use TddWizard\Fixtures\Exception\FixturePoolMissingException;

trait CustomerTrait
{
    private ?CustomerFixturePool $customerFixturePool = null;

    /**
     * @param array<string, mixed> $customerData
     *
     * @throws LocalizedException
     */
    private function createCustomer(array $customerData = []): void
    {
        if (null === $this->customerFixturePool) {
            throw new FixturePoolMissingException(
                'customerFixturePool has not been created in your test setUp method.',
            );
        }
        $customerBuilder = CustomerBuilder::aCustomer();
        if (!empty($customerData['email'])) {
            $customerBuilder = $customerBuilder->withEmail(email: $customerData['email']);
        }
        if (!empty($customerData['addresses'])) {
            // @see \TddWizard\Fixtures\Customer\AddressBuilder
            $customerBuilder = $customerBuilder->withAddresses(addressBuilders: $customerData['addresses']);
        }
        if (!empty($customerData['group_id'])) {
            $customerBuilder = $customerBuilder->withGroupId(groupId: $customerData['group_id']);
        }
        if (!empty($customerData['store_id'])) {
            $customerBuilder = $customerBuilder->withStoreId(storeId: $customerData['store_id']);
        }
        if (!empty($customerData['website_id'])) {
            $customerBuilder = $customerBuilder->withWebsiteId(websiteId: $customerData['website_id']);
        }
        if (!empty($customerData['first_name'])) {
            $customerBuilder = $customerBuilder->withFirstname(firstname: $customerData['first_name']);
        }
        if (!empty($customerData['middle_name'])) {
            $customerBuilder = $customerBuilder->withMiddlename(middlename: $customerData['middle_name']);
        }
        if (!empty($customerData['last_name'])) {
            $customerBuilder = $customerBuilder->withLastname(lastname: $customerData['last_name']);
        }
        if (!empty($customerData['prefix'])) {
            $customerBuilder = $customerBuilder->withPrefix(prefix: $customerData['prefix']);
        }
        if (!empty($customerData['suffix'])) {
            $customerBuilder = $customerBuilder->withSuffix(suffix: $customerData['suffix']);
        }
        if (!empty($customerData['dob'])) {
            $customerBuilder = $customerBuilder->withDob(dob: $customerData['dob']);
        }
        if (!empty($customerData['custom_attributes'])) {
            $customerBuilder = $customerBuilder->withCustomAttributes(values: $customerData['custom_attributes']);
        }
        if (!empty($customerData['confirmation'])) {
            $customerBuilder = $customerBuilder->withConfirmation(confirmation: $customerData['confirmation']);
        }
        if (!empty($customerData['tax_vat'])) {
            $customerBuilder = $customerBuilder->withTaxvat(taxvat: $customerData['tax_vat']);
        }

        $this->customerFixturePool->add(
            customer: $customerBuilder->build(),
            key: $customerData['key'] ?? 'tdd_customer',
        );
    }
}

