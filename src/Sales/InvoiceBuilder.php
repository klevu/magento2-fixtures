<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemCreationInterface;
use Magento\Sales\Api\Data\InvoiceItemCreationInterfaceFactory;
use Magento\Sales\Api\InvoiceOrderInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Builder to be used by fixtures
 */
class InvoiceBuilder
{
    /**
     * @var int[]
     */
    private array $orderItems;

    public function __construct(
        private readonly InvoiceItemCreationInterfaceFactory $itemFactory,
        private readonly InvoiceOrderInterface $invoiceOrder,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly Order $order,
    ) {
        $this->orderItems = [];
    }

    public static function forOrder(
        Order $order,
    ): InvoiceBuilder {
        $objectManager = Bootstrap::getObjectManager();

        return new static(
            itemFactory: $objectManager->create(type: InvoiceItemCreationInterfaceFactory::class),
            invoiceOrder: $objectManager->create(type: InvoiceOrderInterface::class),
            invoiceRepository: $objectManager->create(type: InvoiceRepositoryInterface::class),
            order: $order,
        );
    }

    public function withItem(int $orderItemId, int $qty): InvoiceBuilder
    {
        $builder = clone $this;
        $builder->orderItems[$orderItemId] = $qty;

        return $builder;
    }

    public function build(): InvoiceInterface
    {
        $invoiceItems = $this->buildInvoiceItems();
        $invoiceId = $this->invoiceOrder->execute(
            orderId: $this->order->getEntityId(),
            items: $invoiceItems,
        );

        return $this->invoiceRepository->get(id: $invoiceId);
    }

    /**
     * @return InvoiceItemCreationInterface[]
     */
    private function buildInvoiceItems(): array
    {
        $invoiceItems = [];
        foreach ($this->orderItems as $orderItemId => $qty) {
            /** @var InvoiceItemCreationInterface $invoiceItem */
            $invoiceItem = $this->itemFactory->create();
            $invoiceItem->setOrderItemId($orderItemId);
            $invoiceItem->setQty(qty: $qty);
            $invoiceItems[] = $invoiceItem;
        }

        return $invoiceItems;
    }
}
