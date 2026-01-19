<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\ShipmentInterface;

class ShipmentFixturePool
{
    /**
     * @var ShipmentFixture[]
     */
    private array $shipmentFixtures = [];

    public function add(ShipmentInterface $shipment, string $key = null): void
    {
        if ($key === null) {
            $this->shipmentFixtures[] = new ShipmentFixture(shipment: $shipment);
        } else {
            $this->shipmentFixtures[$key] = new ShipmentFixture(shipment: $shipment);
        }
    }

    /**
     * Returns shipment fixture by key, or last added if key not specified
     */
    public function get(string|int|null $key = null): ShipmentFixture
    {
        if ($key === null) {
            $key = \array_key_last(array: $this->shipmentFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->shipmentFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching shipment found in fixture pool');
        }

        return $this->shipmentFixtures[$key];
    }
}
