<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @internal Use ProductFixture::rollback() or ProductFixturePool::rollback() instead
 */
class ProductFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    public static function create(): ProductFixtureRollback
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(type: Registry::class),
            productRepository: $objectManager->get(type: ProductRepositoryInterface::class),
        );
    }

    public function execute(ProductFixture ...$productFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($productFixtures as $productFixture) {
            try {
                $this->productRepository->deleteById(sku: $productFixture->getSku());
            } catch (\Exception) {
                // this is fine, products has already been removed
            }
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
