<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @internal Use CustomerFixture::rollback() or CustomerFixturePool::rollback() instead
 */
class CustomerFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
    }

    public static function create(): CustomerFixtureRollback
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(type: Registry::class),
            customerRepository: $objectManager->get(type: CustomerRepositoryInterface::class),
        );
    }

    /**
     * @throws LocalizedException
     */
    public function execute(CustomerFixture ...$customerFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($customerFixtures as $customerFixture) {
            $this->customerRepository->deleteById(customerId: $customerFixture->getId());
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
