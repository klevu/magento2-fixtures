<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;

class ShipmentFixture
{
    public function __construct(
        private readonly ShipmentInterface $shipment,
    ) {
    }

    public function getShipment(): ShipmentInterface
    {
        return $this->shipment;
    }

    public function getId(): int
    {
        return (int)$this->shipment->getEntityId();
    }

    /**
     * @return ShipmentTrackInterface[]
     */
    public function getTracks(): array
    {
        return $this->shipment->getTracks();
    }

    public function getShippingLabel(): string
    {
        return (string)$this->shipment->getShippingLabel();
    }
}
