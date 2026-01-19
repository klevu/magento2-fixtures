<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Catalog\Api\Data\ProductInterface;

class ProductFixturePool
{
    /**
     * @var ProductFixture[]
     */
    private array $productFixtures = [];

    public function add(ProductInterface $product, string $key = null): void
    {
        if ($key === null) {
            $this->productFixtures[] = new ProductFixture(product: $product);
        } else {
            $this->productFixtures[$key] = new ProductFixture(product: $product);
        }
    }

    /**
     * Returns product fixture by key, or last added if key not specified
     */
    public function get(int|string|null $key = null): ProductFixture
    {
        if ($key === null) {
            $key = \array_key_last(array: $this->productFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->productFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching product found in fixture pool');
        }

        return $this->productFixtures[$key];
    }

    public function rollback(): void
    {
        ProductFixtureRollback::create()->execute(
            ...array_values(array: $this->productFixtures),
        );
        $this->productFixtures = [];
    }
}
