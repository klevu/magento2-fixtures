<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class ProductFixturePoolTest extends TestCase
{
    private ProductFixturePool $productFixtures;
    private ProductRepositoryInterface $productRepository;
    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->productFixtures = new ProductFixturePool();
        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
    }

    /**
     * @throws \Exception
     */
    public function testLastProductFixtureReturnedByDefault(): void
    {
        $firstProduct = $this->createProduct();
        $lastProduct = $this->createProduct();
        $this->productFixtures->add($firstProduct);
        $this->productFixtures->add($lastProduct);
        $productFixture = $this->productFixtures->get();
        $this->assertEquals($lastProduct->getSku(), $productFixture->getSku());
    }

    public function testExceptionThrownWhenAccessingEmptyProductPool(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->productFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testProductFixtureReturnedByKey(): void
    {
        $firstProduct = $this->createProduct();
        $lastProduct = $this->createProduct();
        $this->productFixtures->add($firstProduct, 'first');
        $this->productFixtures->add($lastProduct, 'last');
        $productFixture = $this->productFixtures->get('first');
        $this->assertEquals($firstProduct->getSku(), $productFixture->getSku());
    }

    public function testProductFixtureReturnedByNumericKey(): void
    {
        $firstProduct = $this->createProduct();
        $lastProduct = $this->createProduct();
        $this->productFixtures->add($firstProduct);
        $this->productFixtures->add($lastProduct);
        $productFixture = $this->productFixtures->get(0);
        $this->assertEquals($firstProduct->getSku(), $productFixture->getSku());
    }

    /**
     * @throws \Exception
     */
    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $product = $this->createProduct();
        $this->productFixtures->add($product, 'foo');
        $this->expectException(\OutOfBoundsException::class);
        $this->productFixtures->get('bar');
    }

    /**
     * @throws \Exception
     */
    public function testRollbackRemovesProductsFromPool(): void
    {
        $product = $this->createProductInDb();
        $this->productFixtures->add($product);
        $this->productFixtures->rollback();
        $this->expectException(\OutOfBoundsException::class);
        $this->productFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackWorksWithKeys(): void
    {
        $product = $this->createProductInDb();
        $this->productFixtures->add($product, 'key');
        $this->productFixtures->rollback();
        $this->expectException(\OutOfBoundsException::class);
        $this->productFixtures->get();
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testRollbackDeletesProductsFromDb(): void
    {
        $product = $this->createProductInDb();
        $this->productFixtures->add($product);
        $this->productFixtures->rollback();
        $this->expectException(NoSuchEntityException::class);
        $this->productRepository->get($product->getSku());
    }

    /**
     * Creates a dummy product object
     *
     * @throws \Exception
     */
    private function createProduct(): ProductInterface
    {
        static $nextId = 1;
        /** @var ProductInterface $product */
        $product = $this->objectManager->create(ProductInterface::class);
        $product->setSku('product-' . $nextId);
        $product->setId($nextId++);

        return $product;
    }

    /**
     * Uses builder to create a product
     *
     * @throws \Exception
     */
    private function createProductInDb(): ProductInterface
    {
        return ProductBuilder::aSimpleProduct()->build();
    }
}
