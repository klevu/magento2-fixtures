<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\Quote\Api\Data\CartInterface;

class CartFixturePool
{
    /**
     * @var CartFixture[]
     */
    private array $cartFixtures = [];

    public function add(CartInterface $cart, ?string $key = null): void
    {
        if ($key === null) {
            $this->cartFixtures[] = new CartFixture(cart: $cart);
        } else {
            $this->cartFixtures[$key] = new CartFixture(cart: $cart);
        }
    }

    public function get(string|int|null $key = null): CartFixture
    {
        if ($key === null) {
            $key = array_key_last(array: $this->cartFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->cartFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching cart found in fixture pool');
        }

        return $this->cartFixtures[$key];
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        CartFixtureRollback::create()->execute(
            ...array_values(array: $this->cartFixtures),
        );
        $this->cartFixtures = [];
    }
}
