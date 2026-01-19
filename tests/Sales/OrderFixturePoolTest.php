<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class OrderFixturePoolTest extends TestCase
{
    private OrderFixturePool $orderFixtures;

    protected function setUp(): void
    {
        $this->orderFixtures = new OrderFixturePool();
    }

    /**
     * @throws \Exception
     */
    public function testLastOrderFixtureReturnedByDefault(): void
    {
        $firstOrder = $this->createOrder();
        $lastOrder = $this->createOrder();
        $this->orderFixtures->add($firstOrder);
        $this->orderFixtures->add($lastOrder);
        $orderFixture = $this->orderFixtures->get();
        $this->assertEquals($lastOrder->getId(), $orderFixture->getId());
    }

    public function testExceptionThrownWhenAccessingEmptyOrderPool(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->orderFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testOrderFixtureReturnedByKey(): void
    {
        $firstOrder = $this->createOrder();
        $lastOrder = $this->createOrder();
        $this->orderFixtures->add($firstOrder, 'first');
        $this->orderFixtures->add($lastOrder, 'last');
        $orderFixture = $this->orderFixtures->get('first');
        $this->assertEquals($firstOrder->getId(), $orderFixture->getId());
    }

    /**
     * @throws \Exception
     */
    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $order = $this->createOrder();
        $this->orderFixtures->add($order, 'foo');
        $this->expectException(\OutOfBoundsException::class);
        $this->orderFixtures->get('bar');
    }

    /**
     * @throws \Exception
     */
    private function createOrder(): Order
    {
        static $nextId = 1;
        /** @var Order $order */
        $order = Bootstrap::getObjectManager()->create(Order::class);
        $order->setId($nextId++);
        return $order;
    }
}
