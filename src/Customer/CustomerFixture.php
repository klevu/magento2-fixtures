<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Object that can be returned from customer fixture, contains ids for test expectations
 */
class CustomerFixture
{
    public function __construct(
        private readonly CustomerInterface $customer,
    ) {
    }

    public function getCustomer(): CustomerInterface
    {
        return $this->customer;
    }

    public function getDefaultShippingAddressId(): int
    {
        return (int)$this->customer->getDefaultShipping();
    }

    public function getDefaultBillingAddressId(): int
    {
        return (int)$this->customer->getDefaultBilling();
    }

    public function getOtherAddressId(): int
    {
        return $this->getNonDefaultAddressIds()[0];
    }

    /**
     * @return int[]
     */
    public function getNonDefaultAddressIds(): array
    {
        return array_values(
            array: array_diff(
                $this->getAllAddressIds(),
                [$this->getDefaultBillingAddressId(), $this->getDefaultShippingAddressId()],
            ),
        );
    }

    /**
     * @return int[]
     */
    public function getAllAddressIds(): array
    {
        return array_map(
            callback: static fn (AddressInterface $address): int => (int)$address->getId(),
            array: (array)$this->customer->getAddresses(),
        );
    }

    public function getId(): int
    {
        return (int)$this->customer->getId();
    }

    public function getConfirmation(): string
    {
        return (string)$this->customer->getConfirmation();
    }

    public function getEmail(): string
    {
        return $this->customer->getEmail();
    }

    public function login(Session $session = null): void
    {
        if ($session === null) {
            $objectManager = Bootstrap::getObjectManager();
            $objectManager->removeSharedInstance(className: Session::class);
            $session = $objectManager->get(type: Session::class);
        }
        $session->setCustomerId(id: $this->getId());
    }

    public function logout(Session $session = null): void
    {
        if ($session === null) {
            $objectManager = Bootstrap::getObjectManager();
            $session = $objectManager->get(type: Session::class);
        }

        $session->logout();
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        CustomerFixtureRollback::create()->execute(customerFixtures: $this);
    }
}
