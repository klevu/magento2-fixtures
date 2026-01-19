<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;

class OrderFixturePool
{
    /**
     * @var OrderFixture[]
     */
    private array $orderFixtures = [];

    public function add(Order $order, string $key = null): void
    {
        if ($key === null) {
            $this->orderFixtures[] = new OrderFixture(order: $order);
        } else {
            $this->orderFixtures[$key] = new OrderFixture(order: $order);
        }
    }

    /**
     * Returns order fixture by key, or last added if key not specified
     */
    public function get(string|int|null $key = null): OrderFixture
    {
        if ($key === null) {
            $key = \array_key_last(array: $this->orderFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->orderFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching order found in fixture pool');
        }

        return $this->orderFixtures[$key];
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function rollback(): void
    {
        OrderFixtureRollback::create()->execute(
            ...array_values(array: $this->orderFixtures),
        );
        $this->orderFixtures = [];
    }
}
