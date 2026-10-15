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
    /**
     * @var CustomerInterface
     */
    private $customer;

    /**
     * @param CustomerInterface $customer
     */
    public function __construct(CustomerInterface $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return CustomerInterface
     */
    public function getCustomer(): CustomerInterface
    {
        return $this->customer;
    }

    /**
     * @return int
     */
    public function getDefaultShippingAddressId(): int
    {
        return (int) $this->customer->getDefaultShipping();
    }

    /**
     * @return int
     */
    public function getDefaultBillingAddressId(): int
    {
        return (int) $this->customer->getDefaultBilling();
    }

    /**
     * @return int
     */
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
            array_diff(
                $this->getAllAddressIds(),
                [$this->getDefaultBillingAddressId(), $this->getDefaultShippingAddressId()]
            )
        );
    }

    /**
     * @return int[]
     */
    public function getAllAddressIds(): array
    {
        return array_map(
            function (AddressInterface $address): int {
                return (int)$address->getId();
            },
            (array)$this->customer->getAddresses()
        );
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->customer->getId();
    }

    /**
     * @return string
     */
    public function getConfirmation(): string
    {
        return (string)$this->customer->getConfirmation();
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->customer->getEmail();
    }

    /**
     * @param Session|null $session
     *
     * @return void
     */
    public function login(?Session $session = null): void
    {
        if ($session === null) {
            $objectManager = Bootstrap::getObjectManager();
            $objectManager->removeSharedInstance(Session::class);
            $session = $objectManager->get(Session::class);
        }
        $session->setCustomerId($this->getId());
    }

    /**
     * @param Session|null $session
     *
     * @return void
     */
    public function logout(?Session $session = null): void
    {
        if ($session === null) {
            $objectManager = Bootstrap::getObjectManager();
            $session = $objectManager->get(Session::class);
        }

        $session->logout();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function rollback(): void
    {
        CustomerFixtureRollback::create()->execute($this);
    }
}
