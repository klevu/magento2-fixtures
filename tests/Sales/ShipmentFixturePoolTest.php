<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class ShipmentFixturePoolTest extends TestCase
{
    private ShipmentFixturePool $shipmentFixtures;

    protected function setUp(): void
    {
        $this->shipmentFixtures = new ShipmentFixturePool();
    }

    /**
     * @throws \Exception
     */
    public function testLastShipmentFixtureReturnedByDefault(): void
    {
        $firstShipment = $this->createShipment();
        $lastShipment = $this->createShipment();
        $this->shipmentFixtures->add($firstShipment);
        $this->shipmentFixtures->add($lastShipment);
        $shipmentFixture = $this->shipmentFixtures->get();
        $this->assertEquals($lastShipment->getEntityId(), $shipmentFixture->getId());
    }

    public function testExceptionThrownWhenAccessingEmptyShipmentPool(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->shipmentFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testShipmentFixtureReturnedByKey(): void
    {
        $firstShipment = $this->createShipment();
        $lastShipment = $this->createShipment();
        $this->shipmentFixtures->add($firstShipment, 'first');
        $this->shipmentFixtures->add($lastShipment, 'last');
        $shipmentFixture = $this->shipmentFixtures->get('first');
        $this->assertEquals($firstShipment->getEntityId(), $shipmentFixture->getId());
    }

    /**
     * @throws \Exception
     */
    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $shipment = $this->createShipment();
        $this->shipmentFixtures->add($shipment, 'foo');
        $this->expectException(\OutOfBoundsException::class);
        $this->shipmentFixtures->get('bar');
    }

    /**
     * @throws \Exception
     */
    private function createShipment(): ShipmentInterface
    {
        static $nextId = 1;
        /** @var ShipmentInterface $shipment */
        $shipment = Bootstrap::getObjectManager()->create(ShipmentInterface::class);
        $shipment->setEntityId($nextId++);
        return $shipment;
    }
}
