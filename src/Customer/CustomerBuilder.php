<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Builder to be used by fixtures
 */
class CustomerBuilder
{
    /**
     * @var AddressBuilder[]
     */
    private array $addressBuilders;

    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private CustomerInterface $customer,
        private readonly Encryptor $encryptor,
        private readonly string $password,
        AddressBuilder ...$addressBuilders
    ) {
        $this->addressBuilders = $addressBuilders;
    }

    public function __clone(): void
    {
        $this->customer = clone $this->customer;
    }

    public static function aCustomer(): CustomerBuilder
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var CustomerInterface $customer */
        $customer = $objectManager->create(type: CustomerInterface::class);
        $customer->setWebsiteId(websiteId: 1);
        $customer->setGroupId(groupId: 1);
        $customer->setStoreId(storeId: 1);
        $customer->setPrefix(prefix: 'Mr.');
        $customer->setFirstname(firstname: 'John');
        $customer->setMiddlename(middlename: 'A');
        $customer->setLastname(lastname: 'Smith');
        $customer->setSuffix(suffix: 'Esq.');
        $customer->setTaxvat(taxvat: '12');
        $customer->setGender(gender: 1);
        $password = 'Test#123';

        return new self(
            customerRepository: $objectManager->create(type: CustomerRepositoryInterface::class),
            customer: $customer,
            encryptor: $objectManager->create(type: Encryptor::class),
            password: $password,
        );
    }

    public function withAddresses(AddressBuilder ...$addressBuilders): CustomerBuilder
    {
        $builder = clone $this;
        $builder->addressBuilders = $addressBuilders;

        return $builder;
    }

    public function withEmail(string $email): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setEmail(email: $email);

        return $builder;
    }

    public function withGroupId(int $groupId): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setGroupId(groupId: $groupId);

        return $builder;
    }

    public function withStoreId(int $storeId): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setStoreId(storeId: $storeId);

        return $builder;
    }

    public function withWebsiteId(int $websiteId): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setWebsiteId(websiteId: $websiteId);

        return $builder;
    }

    public function withPrefix(string $prefix): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setPrefix(prefix: $prefix);

        return $builder;
    }

    public function withFirstname(string $firstname): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setFirstname(firstname: $firstname);

        return $builder;
    }

    public function withMiddlename(string $middlename): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setMiddlename(middlename: $middlename);

        return $builder;
    }

    public function withLastname(string $lastname): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setLastname(lastname: $lastname);

        return $builder;
    }

    public function withSuffix(string $suffix): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setSuffix(suffix: $suffix);

        return $builder;
    }

    public function withTaxvat(string $taxvat): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setTaxvat(taxvat: $taxvat);

        return $builder;
    }

    public function withDob(string $dob): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setDob(dob: $dob);

        return $builder;
    }

    /**
     * @param mixed[] $values
     *
     * @return CustomerBuilder
     */
    public function withCustomAttributes(array $values): CustomerBuilder
    {
        $builder = clone $this;
        foreach ($values as $code => $value) {
            $builder->customer->setCustomAttribute(
                attributeCode: $code,
                attributeValue: $value,
            );
        }

        return $builder;
    }

    public function withConfirmation(string $confirmation): CustomerBuilder
    {
        $builder = clone $this;
        $builder->customer->setConfirmation(confirmation: $confirmation);

        return $builder;
    }

    /**
     * @throws LocalizedException
     */
    public function build(): CustomerInterface
    {
        $builder = clone $this;
        if (!$builder->customer->getEmail()) {
            $builder->customer->setEmail(
                email: sha1(uniqid(prefix: '', more_entropy: true)) . '@example.com',
            );
        }
        $customer = $builder->saveNewCustomer();
        // To save the customer addresses, the customer must exist first
        $addresses = [];
        foreach ($this->addressBuilders as $addressBuilder) {
            if ($customer->getId()) {
                $addressBuilder = $addressBuilder->withCustomerId(customerId: (int)$customer->getId());
            }
            $setAsDefaultBilling = $addressBuilder->isDefaultBilling();
            $setAsDefaultShipping = $addressBuilder->isDefaultShipping();
            $address = $addressBuilder->build();
            $addresses[] = $address;
            if ($setAsDefaultBilling) {
                $defaultBillingId = $address->getId();
            }
            if ($setAsDefaultShipping) {
                $defaultShippingId = $address->getId();
            }
        }
        $customer->setDefaultBilling(defaultBilling: $defaultBillingId ?? null);
        $customer->setDefaultShipping(defaultShipping: $defaultShippingId ?? null);
        $customer->setAddresses(addresses: $addresses);
        /*
         * Magento automatically sets random confirmation key for new account with password.
         * We need to save again with our own confirmation (null for confirmed customer)
         */
        $customer->setConfirmation(confirmation: (string)$builder->customer->getConfirmation());

        return $builder->customerRepository->save(customer: $customer);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod) False positive: the method is used in build() on the cloned builder
     * @throws LocalizedException
     */
    private function saveNewCustomer(): CustomerInterface
    {
        return $this->customerRepository->save(
            customer: $this->customer,
            passwordHash: $this->encryptor->getHash(password: $this->password, salt: true),
        );
    }
}
