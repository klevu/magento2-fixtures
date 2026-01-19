<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @internal Use OrderFixture::rollback() or OrderFixturePool::rollback() instead
 */
class OrderFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly OrderRepository $orderRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    public static function create(): OrderFixtureRollback
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(type: Registry::class),
            orderRepository: $objectManager->get(type: OrderRepositoryInterface::class),
            customerRepository: $objectManager->get(type: CustomerRepositoryInterface::class),
            productRepository: $objectManager->get(type: ProductRepositoryInterface::class),
        );
    }

    /**
     * Roll back orders with associated customers and products.
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(OrderFixture ...$orderFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($orderFixtures as $orderFixture) {
            $orderItems = $this->orderRepository->get(id: $orderFixture->getId())->getItems();

            $this->orderRepository->deleteById(id: $orderFixture->getId());
            try {
                $this->customerRepository->deleteById(customerId: $orderFixture->getCustomerId());
            } catch (\Exception) {
                // customer already deleted or guest
            }
            array_walk(
                array: $orderItems,
                callback: function (OrderItemInterface $orderItem) {
                    try {
                        $this->productRepository->deleteById(sku: $orderItem->getSku());
                    } catch (NoSuchEntityException) {
                        // ignore if already deleted
                    }
                },
            );
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
