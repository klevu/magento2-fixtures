<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Downloadable\Model\Product\Type as DownloadableType;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

trait ProductTrait
{
    private ?ProductFixturePool $productFixturePool = null;

    /**
     * Example usage setting store level data
     * $this->createProduct(
     *   productData: [
     *     'sku' => 'PRODUCT_SKU_001',
     *     'name' => 'My Product 001',
     *     'status' => Status::STATUS_ENABLED,
     *     'stores' => [
     *        $store1->getId() => [
     *          'name' => 'My Product 001 Store 1',
     *        ],
     *        $store2->getId() => [
     *          'status' => Status::STATUS_DISABLED,
     *        ],
     *      ],
     *      'tier_prices' => [
     *           ['price' => 20.00, 'qty' => 1, 'customer_group' => 1],
     *           ['price' => 18.00, 'qty' => 5, 'customer_group' => 1],
     *           ['price' => 15.00, 'qty' => 1, 'customer_group' => 2],
     *           ['price' => 13.00, 'qty' => 5, 'customer_group' => 2],
     *       ],
     *      'images' => [
     *          'image' => [
     *              'fileName' => 'image.png',
     *              'path' => 'path/to/images',
     *           ],
     *          'small_image' => [
     *               'fileName' => 'small_image.jpg',
     *               'path' => 'path/to/images',
     *               'mimeType' => 'image/jpg',
     *           ],
     *          'thumbnail' => 'image1.png',
     *      ],
     *   ],
     * );
     *
     * @param array<string, mixed> $productData
     *
     * @throws \Exception
     */
    public function createProduct(array $productData = [], ?int $storeId = null): void // phpcs:ignore SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh, Generic.Files.LineLength.TooLong
    {
        $productBuilder = $this->getProductBuilder(typeId: $productData['type_id'] ?? null);

        if (!empty($productData['sku'])) {
            $productBuilder = $productBuilder->withSku(sku: $productData['sku']);
        }
        if (!empty($productData['name'])) {
            $productBuilder = $productBuilder->withName(name: $productData['name'], storeId: $storeId);
        }
        if (isset($productData['status'])) {
            // we pass store here, but remember that this is a website scope setting
            $productBuilder = $productBuilder->withStatus(status: $productData['status'], storeId: $storeId);
        }
        if (!empty($productData['visibility'])) {
            $productBuilder = $productBuilder->withVisibility(visibility: $productData['visibility'], storeId: $storeId);
        }
        if (isset($productData['is_in_stock'])) {
            $productBuilder = $productBuilder->withIsInStock(inStock: $productData['is_in_stock']);
        }
        if (isset($productData['manage_stock'])) {
            $productBuilder = $productBuilder->withManageStock(manageStock: $productData['manage_stock']);
        }
        if (isset($productData['qty'])) {
            $productBuilder = $productBuilder->withStockQty(qty: $productData['qty']);
        }
        if (isset($productData['is_qty_decimal'])) {
            $productBuilder = $productBuilder->withIsQtyDecimal(isQtyDecimal: $productData['is_qty_decimal']);
        }
        if (isset($productData['backorders'])) {
            $productBuilder = $productBuilder->withBackorders(backorders: $productData['backorders']);
        }
        if (isset($productData['price'])) {
            $productBuilder = $productBuilder->withPrice(price: $productData['price']);
        }
        if (isset($productData['tier_prices'])) {
            /**
             * $productData['tier_prices'][['price' => 10.00, 'qty' => 1, 'customer_group' => 2]]
             */
            $productBuilder = $productBuilder->withTierPrices(tierPrices: $productData['tier_prices']);
        }
        if (isset($productData['tax_class_id'])) {
            $productBuilder = $productBuilder->withTaxClassId(taxClassId: $productData['tax_class_id']);
        }
        if (!empty($productData['website_ids'])) {
            $productBuilder = $productBuilder->withWebsiteIds(websiteIds: $productData['website_ids']);
        }
        if (!empty($productData['category_ids'])) {
            $productBuilder = $productBuilder->withCategoryIds(categoryIds: $productData['category_ids']);
        }
        if (!empty($productData['custom_attributes']) || !empty($productData['data'])) {
            $attributeValues = array_merge(
                $productData['custom_attributes'] ?? [],
                $productData['data'] ?? [],
            );
            $productBuilder = $productBuilder->withCustomAttributes(
                attributeValues: $attributeValues,
                storeId: $storeId,
            );
        }
        if (!empty($productData['images'])) {
            /**
             * $productData['images']
             * key is the Magento image type, e.g. image, small_image, thumbnail, will default to image if not supplied
             * value can be a string containing the fileName or an array containing the fileName, path and/or mimeType
             *  e.g ['image' => 'image1.png'] // will look for an image of that name in src/_files/images
             *   or ['image' => ['fileName' =>'your_image.jpg', 'path' => 'path/to/images', 'mimeType' => 'image/jpg']]
             */
            foreach ($productData['images'] as $type => $image) {
                if (is_numeric($type)) {
                    $type = null;
                }
                if (is_string($image)) {
                    $path = null;
                    $mimeType = null;
                    $fileName = $image;
                } elseif (is_array($image)) {
                    $fileName = $image['fileName'] ?? null;
                    $path = $image['path'] ?? null;
                    $mimeType = $image['mimeType'] ?? null;
                }
                $productBuilder = $productBuilder->withImage(
                    fileName: $fileName,
                    imageType: $type,
                    mimeType: $mimeType,
                    imagePath: $path,
                );
            }
        }
        if (($productData['type_id'] ?? null) === Grouped::TYPE_CODE) {
            // grouped product
            if (!empty($productData['linked_products'])) {
                $productBuilder = $this->processLinkedProduct(
                    productBuilder: $productBuilder,
                    linkedProducts: $productData['linked_products'],
                );
            }
        }
        if (($productData['type_id'] ?? null) === Configurable::TYPE_CODE) {
            // configurable product
            if (!empty($productData['configurable_attributes'])) {
                $productBuilder = $this->processConfigurableAttributes(
                    productBuilder: $productBuilder,
                    configurableAttributes: $productData['configurable_attributes'],
                );
            }
            if (!empty($productData['variants'])) {
                $productBuilder = $this->processVariants(
                    productBuilder: $productBuilder,
                    variantProducts: $productData['variants'],
                );
            }
        }
        if (($productData['type_id'] ?? null) === DownloadableType::TYPE_DOWNLOADABLE) {
            if (!empty($productData['download_links'])) {
                $productBuilder = $productBuilder->withDownloadLinks(links: $productData['download_links']);
            }
        }
        if (!empty($productData['stores'])) {
            $productBuilder = $this->setStoreLevelData(
                storesData: $productData['stores'],
                productBuilder: $productBuilder,
            );
        }

        $this->productFixturePool->add(
            product: $productBuilder->build(),
            key: $productData['key'] ?? 'tdd_product',
        );
    }

    private function getProductBuilder(?string $typeId = null): ProductBuilder
    {
        // @TODO add bundle products & gift cards
        return match ($typeId ?? null) {
            DownloadableType::TYPE_DOWNLOADABLE => ProductBuilder::aDownloadableProduct(),
            Type::TYPE_VIRTUAL => ProductBuilder::aVirtualProduct(),
            Grouped::TYPE_CODE => ProductBuilder::aGroupedProduct(),
            Configurable::TYPE_CODE => ProductBuilder::aConfigurableProduct(),
            default => ProductBuilder::aSimpleProduct(),
        };
    }

    /**
     * @param ProductInterface[] $linkedProducts
     **/
    private function processLinkedProduct(
        ProductBuilder $productBuilder,
        array $linkedProducts,
    ): ProductBuilder {
        foreach ($linkedProducts as $linkedProduct) {
            /** @var ProductBuilder $productBuilder */
            $productBuilder = $productBuilder->withLinkedProduct(
                linkedProduct: $linkedProduct,
            );
        }

        return $productBuilder;
    }

    /**
     * @param AttributeInterface[] $configurableAttributes
     */
    private function processConfigurableAttributes(
        ProductBuilder $productBuilder,
        array $configurableAttributes,
    ): ProductBuilder {
        foreach ($configurableAttributes as $attribute) {
            /** @var ProductBuilder $productBuilder */
            $productBuilder = $productBuilder->withConfigurableAttribute(
                attribute: $attribute,
            );
        }

        return $productBuilder;
    }

    /**
     * @param ProductInterface[] $variantProducts
     */
    private function processVariants(
        ProductBuilder $productBuilder,
        array $variantProducts,
    ): ProductBuilder {
        foreach ($variantProducts as $variantProduct) {
            /** @var ProductBuilder $productBuilder */
            $productBuilder = $productBuilder->withVariant(variantProduct: $variantProduct);
        }

        return $productBuilder;
    }

    /**
     * @param mixed[] $storesData
     */
    private function setStoreLevelData(array $storesData, ProductBuilder $productBuilder): ProductBuilder
    {
        foreach ($storesData as $storeIdKey => $productStoreData) {
            if (!empty($productStoreData['name'])) {
                $productBuilder = $productBuilder->withName(
                    name: $productStoreData['name'],
                    storeId: $storeIdKey,
                );
            }
            if (isset($productStoreData['status'])) {
                // we pass store here, but remember that this is a website scope setting
                $productBuilder = $productBuilder->withStatus(
                    status: $productStoreData['status'],
                    storeId: $storeIdKey,
                );
            }
            if (!empty($productStoreData['visibility'])) {
                $productBuilder = $productBuilder->withVisibility(
                    visibility: $productStoreData['visibility'],
                    storeId: $storeIdKey,
                );
            }
            if (!empty($productStoreData['custom_attributes'])) {
                $productBuilder = $productBuilder->withCustomAttributes(
                    attributeValues: $productStoreData['custom_attributes'],
                    storeId: $storeIdKey,
                );
            }
        }

        return $productBuilder;
    }
}
