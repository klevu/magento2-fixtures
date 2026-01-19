<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\Framework\Registry;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

class CartFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly CartRepositoryInterface $cartRepository,
    ) {
    }

    public static function create(): CartFixtureRollback //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(type: Registry::class),
            cartRepository: $objectManager->get(type: CartRepositoryInterface::class),
        );
    }

    public function execute(CartFixture ...$cartFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($cartFixtures as $cartFixture) {
            $this->cartRepository->delete(quote: $cartFixture->getCart());
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
