<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\Quote\Api\Data\CartInterface;

class CartFixture
{
    public function __construct(
        private readonly CartInterface $cart,
    ) {
    }

    public function getCart(): CartInterface
    {
        return $this->cart;
    }

    public function getCartId(): int
    {
        return (int)$this->cart->getId();
    }

    public function rollback(): void
    {
        CartFixtureRollback::create()->execute($this);
    }
}
