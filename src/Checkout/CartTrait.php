<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

trait CartTrait
{
    private ?CartFixturePool $cartFixturePool = null;

    /**
     * $cartData = [
     *   'products' => [
     *     'simple' => [
     *        'SKU001' => 1, // 'sku' => 'qty'
     *     ],
     *     'configurable' => [
     *       'SKU002' => [ // sku
     *         'qty' => 1,
     *         'options' => [
     *           'configurable_attribute_code' => 12345, // 'attr_code' => 'optionId'
     *          ],
     *        ],
     *     ],
     *     'grouped' => [
     *       'SKU_003' => [ // sku of grouped product
     *          'qty' => 1,
     *          'options' => [
     *             'SKU_004' => 1, // sku => qty
     *             'SKU_005' => 2,
     *           ],
     *        ],
     *     ],
     *   ],
     * ]
     *
     * @param mixed[] $cartData
     *
     * @throws LocalizedException
     */
    public function createCart(array $cartData = []): void
    {
        $cartBuilder = CartBuilder::forCurrentSession();
        $cartBuilder = $cartBuilder->withAddress(address: $cartData['address'] ?? null);

        if ($cartData['store_id'] ?? null) {
            $cartBuilder = $cartBuilder->withStoreId(storeId: $cartData['store_id']);
        }
        if ($cartData['reserved_order_id'] ?? null) {
            $cartBuilder = $cartBuilder->withReservedOrderId(reservedOrderId: $cartData['reserved_order_id']);
        }
        foreach (($cartData['products'] ?? []) as $type => $cartItemData) {
            $cartBuilder = match ($type) {
                Configurable::TYPE_CODE => $this->buildConfigurableProduct(
                    cartItemData: $cartItemData,
                    cartBuilder: $cartBuilder,
                ),
                Grouped::TYPE_CODE => $this->buildGroupedProduct(
                    cartItemData: $cartItemData,
                    cartBuilder: $cartBuilder,
                ),
                default => $this->buildDefaultProduct(
                    cartItemData: $cartItemData,
                    cartBuilder: $cartBuilder,
                ),
            };
        }

        $this->cartFixturePool->add(
            cart: $cartBuilder->build(),
            key: $cartData['key'] ?? 'tdd_cart',
        );
    }

    /**
     * @param mixed[] $cartItemData
     */
    private function buildConfigurableProduct(array $cartItemData, CartBuilder $cartBuilder): CartBuilder
    {
        foreach ($cartItemData as $sku => $data) {
            $cartBuilder = $cartBuilder->withConfigurableProduct(
                sku: $sku,
                options: $data['options'],
                qty: $data['qty'] ?? null,
            );
        }

        return $cartBuilder;
    }

    /**
     * @param mixed[] $cartItemData
     */
    private function buildGroupedProduct(array $cartItemData, CartBuilder $cartBuilder): CartBuilder
    {
        foreach ($cartItemData as $sku => $data) {
            $cartBuilder = $cartBuilder->withGroupedProduct(
                sku: $sku,
                options: $data['options'],
                qty: $data['qty'] ?? null,
            );
        }

        return $cartBuilder;
    }

    /**
     * @param mixed[] $cartItemData
     */
    private function buildDefaultProduct(array $cartItemData, CartBuilder $cartBuilder): CartBuilder
    {
        foreach ($cartItemData as $sku => $qty) {
            $cartBuilder = $cartBuilder->withSimpleProduct(sku: $sku, qty: $qty);
        }

        return $cartBuilder;
    }
}
