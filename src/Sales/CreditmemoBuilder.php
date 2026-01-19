<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemCreationInterface;
use Magento\Sales\Api\Data\CreditmemoItemCreationInterfaceFactory;
use Magento\Sales\Api\RefundOrderInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Builder to be used by fixtures
 */
class CreditmemoBuilder
{
    /**
     * @var float[]
     */
    private array $orderItems;

    public function __construct(
        private readonly CreditmemoItemCreationInterfaceFactory $itemFactory,
        private readonly RefundOrderInterface $refundOrder,
        private readonly CreditmemoRepositoryInterface $creditmemoRepository,
        private readonly Order $order,
    ) {
        $this->orderItems = [];
    }

    public static function forOrder(
        Order $order,
    ): CreditmemoBuilder {
        $objectManager = Bootstrap::getObjectManager();

        return new static(
            itemFactory: $objectManager->create(type: CreditmemoItemCreationInterfaceFactory::class),
            refundOrder: $objectManager->create(type: RefundOrderInterface::class),
            creditmemoRepository: $objectManager->create(type: CreditmemoRepositoryInterface::class),
            order: $order,
        );
    }

    public function withItem(int $orderItemId, int $qty): CreditmemoBuilder
    {
        $builder = clone $this;

        $builder->orderItems[$orderItemId] = $qty;

        return $builder;
    }

    public function build(): CreditmemoInterface
    {
        // order must be invoiced before a refund can be created.
        if ($this->order->canInvoice()) {
            InvoiceBuilder::forOrder(order: $this->order)->build();
        }

        // refund items must be explicitly set
        if (empty($this->orderItems)) {
            foreach ($this->order->getItems() as $item) {
                $this->orderItems[$item->getItemId()] = (float)$item->getQtyOrdered();
            }
        }
        $creditmemoItems = $this->buildCreditmemoItems();
        $creditmemoId = $this->refundOrder->execute(orderId: $this->order->getEntityId(), items: $creditmemoItems);

        return $this->creditmemoRepository->get($creditmemoId);
    }

    /**
     * @return CreditmemoItemCreationInterface[]
     */
    private function buildCreditmemoItems(): array
    {
        $creditmemoItems = [];
        foreach ($this->orderItems as $orderItemId => $qty) {
            /** @var CreditmemoItemCreationInterface $creditmemoItem */
            $creditmemoItem = $this->itemFactory->create();
            $creditmemoItem->setOrderItemId($orderItemId);
            $creditmemoItem->setQty(qty: $qty);
            $creditmemoItems[] = $creditmemoItem;
        }

        return $creditmemoItems;
    }
}
