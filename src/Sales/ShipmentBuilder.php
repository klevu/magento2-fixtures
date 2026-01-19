<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterface;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterfaceFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Builder to be used by fixtures
 */
class ShipmentBuilder
{
    /**
     * @var int[]
     */
    private array $orderItems;
    /**
     * @var string[]
     */
    private array $trackingNumbers;

    final public function __construct(
        private readonly ShipmentItemCreationInterfaceFactory $itemFactory,
        private readonly ShipmentTrackCreationInterfaceFactory $trackFactory,
        private readonly ShipOrderInterface $shipOrder,
        private readonly ShipmentRepositoryInterface $shipmentRepository,
        private readonly Order $order,
    ) {
        $this->orderItems = [];
        $this->trackingNumbers = [];
    }

    public static function forOrder(
        Order $order,
    ): ShipmentBuilder {
        $objectManager = Bootstrap::getObjectManager();

        return new static(
            itemFactory: $objectManager->create(type: ShipmentItemCreationInterfaceFactory::class),
            trackFactory: $objectManager->create(type: ShipmentTrackCreationInterfaceFactory::class),
            shipOrder: $objectManager->create(type: ShipOrderInterface::class),
            shipmentRepository: $objectManager->create(type: ShipmentRepositoryInterface::class),
            order: $order,
        );
    }

    public function withItem(int $orderItemId, int $qty): ShipmentBuilder
    {
        $builder = clone $this;
        $builder->orderItems[$orderItemId] = $qty;

        return $builder;
    }

    public function withTrackingNumbers(string ...$trackingNumbers): ShipmentBuilder
    {
        $builder = clone $this;
        $builder->trackingNumbers = $trackingNumbers;

        return $builder;
    }

    public function build(): ShipmentInterface
    {
        $shipmentItems = $this->buildShipmentItems();
        $tracks = $this->buildTracks();

        $shipmentId = $this->shipOrder->execute(
            orderId: $this->order->getEntityId(),
            items: $shipmentItems,
            tracks: $tracks,
        );

        $shipment = $this->shipmentRepository->get(id: $shipmentId);
        if (!empty($this->trackingNumbers)) {
            $shipment->setShippingLabel('%PDF-1.4');
            $this->shipmentRepository->save(entity: $shipment);
        }

        return $shipment;
    }

    /**
     * @return ShipmentTrackCreationInterface[]
     */
    private function buildTracks(): array
    {
        return array_map(
            callback: function (string $trackingNumber): ShipmentTrackCreationInterface {
                $carrierCode = strtok(string: (string)$this->order->getShippingMethod(), token: '_');
                $track = $this->trackFactory->create();
                $track->setCarrierCode($carrierCode);
                $track->setTitle(title: $carrierCode);
                $track->setTrackNumber(trackNumber: $trackingNumber);

                return $track;
            },
            array: $this->trackingNumbers,
        );
    }

    /**
     * @return ShipmentItemCreationInterface[]
     */
    private function buildShipmentItems(): array
    {
        $shipmentItems = [];

        foreach ($this->orderItems as $orderItemId => $qty) {
            $shipmentItem = $this->itemFactory->create();
            $shipmentItem->setOrderItemId($orderItemId);
            $shipmentItem->setQty(qty: $qty);
            $shipmentItems[] = $shipmentItem;
        }

        return $shipmentItems;
    }
}
