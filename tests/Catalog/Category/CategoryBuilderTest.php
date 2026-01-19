<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\Product\ProductBuilder;
use TddWizard\Fixtures\Catalog\Product\ProductFixture;
use TddWizard\Fixtures\Catalog\Product\ProductFixtureRollback;
use TddWizard\Fixtures\Store\StoreFixturePool;
use TddWizard\Fixtures\Store\StoreTrait;

/**
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class CategoryBuilderTest extends TestCase
{
    use StoreTrait;

    private CategoryRepositoryInterface $categoryRepository;
    /**
     * @var CategoryFixture[]
     */
    private array $categories = [];
    /**
     * @var ProductFixture[]
     */
    private array $products = [];

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->categoryRepository = $objectManager->create(type: CategoryRepositoryInterface::class);
        $this->categories = [];
        $this->products = [];

        $this->storeFixturePool = $objectManager->create(type: StoreFixturePool::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (!empty($this->categories)) {
            foreach ($this->categories as $product) {
                CategoryFixtureRollback::create()->execute($product);
            }
        }
        if (!empty($this->products)) {
            foreach ($this->products as $product) {
                ProductFixtureRollback::create()->execute($product);
            }
        }
        $this->storeFixturePool->rollback();
    }

    public function testDefaultRootCategory(): void
    {
        $categoryFixture = new CategoryFixture(
            CategoryBuilder::rootCategory()->build(),
        );
        $this->categories[] = $categoryFixture;

        /** @var Category $category */
        $category = $this->categoryRepository->get($categoryFixture->getId());

        // store ids are mixed type, normalize first for strict type checking
        $storeIds = array_map('strval', $category->getStoreIds());

        $this->assertEquals('Root Category', $category->getName(), 'Category name does not match expected value.');
        $this->assertContains('0', $storeIds, 'Admin store ID is not assigned.');
        $this->assertContains('1', $storeIds, 'Default store ID is not assigned.');
        $this->assertEquals(
            '1/' . $categoryFixture->getId(),
            $category->getPath(),
            'Category path does not match expected value.',
        );
    }

    public function testDefaultTopLevelCategory(): void
    {
        $categoryFixture = new CategoryFixture(
            CategoryBuilder::topLevelCategory()->build(),
        );
        $this->categories[] = $categoryFixture;

        /** @var Category $category */
        $category = $this->categoryRepository->get($categoryFixture->getId());

        // store ids are mixed type, normalize first for strict type checking
        $storeIds = array_map('strval', $category->getStoreIds());

        $this->assertEquals('Top Level Category', $category->getName(), 'Category name does not match expected value.');
        $this->assertContains('0', $storeIds, 'Admin store ID is not assigned.');
        $this->assertContains('1', $storeIds, 'Default store ID is not assigned.');
        $this->assertEquals(
            '1/2/' . $categoryFixture->getId(),
            $category->getPath(),
            'Category path does not match expected value.',
        );
    }

    public function testDefaultChildCategory(): void
    {
        $parentCategoryFixture = new CategoryFixture(
            CategoryBuilder::topLevelCategory()->build(),
        );
        $this->categories[] = $parentCategoryFixture;
        $childCategoryFixture = new CategoryFixture(
            CategoryBuilder::childCategoryOf($parentCategoryFixture->getCategory())->build(),
        );

        /** @var Category $category */
        $category = $this->categoryRepository->get($childCategoryFixture->getId());

        // store ids are mixed type, normalize first for strict type checking
        $storeIds = array_map('strval', $category->getStoreIds());

        $this->assertEquals('Child Category', $category->getName(), 'Category name does not match expected value.');
        $this->assertContains('0', $storeIds, 'Admin store ID is not assigned.');
        $this->assertContains('1', $storeIds, 'Default store ID is not assigned.');
        $this->assertEquals(
            '1/2/' . $parentCategoryFixture->getId() . '/' . $childCategoryFixture->getId(),
            $category->getPath(),
            'Category path does not match expected value.',
        );
    }

    public function testCategoryWithSpecificAttributes(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturePool->get('tdd_store');

        $categoryFixture = new CategoryFixture(
            CategoryBuilder::topLevelCategory()
                ->withName('Custom Name')
                ->withDescription('Custom Description')
                ->withIsActive(false)
                ->withUrlKey('my-url-key')
                ->withDisplayMode(displayMode: Category::DM_MIXED)
                ->withCustomAttributes(values: [
                    'meta_title' => 'Custom Meta Title',
                ])
                ->withStoreId(storeId: (int)$storeFixture->getId())
                ->withImage(fileName: 'image2.png')
                ->build(),
        );
        $this->categories[] = $categoryFixture;

        /** @var Category $category */
        $category = $this->categoryRepository->get(
            categoryId: $categoryFixture->getId(),
            storeId: (int)$storeFixture->getId(),
        );
        $this->assertEquals('0', $category->getIsActive(), 'Category should be inactive');
        $this->assertEquals('Custom Name', $category->getName(), 'Category name');
        $this->assertEquals('my-url-key', $category->getUrlKey(), 'Category URL key');
        $this->assertEquals(
            'Custom Description',
            $category->getCustomAttribute('description')->getValue(),
            'Category description',
        );
        $this->assertEquals(expected: Category::DM_MIXED, actual: $category->getDisplayMode());
        $this->assertEquals(expected: $storeFixture->getId(), actual: $category->getStoreId());
        $customAttribute = $category->getCustomAttribute(attributeCode: 'meta_title');
        $this->assertSame(expected: 'meta_title', actual: $customAttribute->getAttributeCode());
        $this->assertSame(expected: 'Custom Meta Title', actual: $customAttribute->getValue());
        $this->assertStringMatchesFormat(format: '%A/media/catalog/category/image2%A.png', string: $category->getImageUrl());
    }

    public function testCategoryWithProducts(): void
    {
        $product1 = new ProductFixture(ProductBuilder::aSimpleProduct()->build());
        $product2 = new ProductFixture(ProductBuilder::aSimpleProduct()->build());
        $categoryFixture = new CategoryFixture(
            CategoryBuilder::topLevelCategory()->withProducts([$product1->getSku(), $product2->getSku()])->build(),
        );
        $this->products[] = $product1;
        $this->products[] = $product2;
        $this->categories[] = $categoryFixture;

        /** @var Category $category */
        $category = $this->categoryRepository->get($categoryFixture->getId());

        $this->assertEquals(
            [$product1->getId() => 0, $product2->getId() => 1],
            $category->getProductsPosition(),
            'Product positions',
        );
    }

    public function testMultipleCategories(): void
    {
        $this->categories[0] = new CategoryFixture(
            CategoryBuilder::topLevelCategory()->build(),
        );
        $this->categories[1] = new CategoryFixture(
            CategoryBuilder::topLevelCategory()->build(),
        );

        /** @var Category $category1 */
        $category1 = $this->categoryRepository->get($this->categories[0]->getId());
        /** @var Category $category2 */
        $category2 = $this->categoryRepository->get($this->categories[1]->getId());
        $this->assertNotEquals(
            $category1->getUrlKey(),
            $category2->getUrlKey(),
            'Categories should be created with different URL keys',
        );
    }

    public function testCategoryWithStoreSpecificAttributes(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturePool->get('tdd_store');

        $categoryFixture = new CategoryFixture(
            CategoryBuilder::topLevelCategory()
                ->withName(name: 'Custom Name')
                ->withName(name: 'Custom Name Other Store', storeId: (int)$storeFixture->getId())
                ->withDescription(description: 'Custom Description')
                ->withDescription(description: 'Custom Description Other Store', storeId: (int)$storeFixture->getId())
                ->withIsActive(isActive: false)
                ->withIsActive(isActive: true, storeId: (int)$storeFixture->getId())
                ->withUrlKey(urlKey: 'my-url-key')
                ->withUrlKey(urlKey: 'my-url-key-other-store', storeId: (int)$storeFixture->getId())
                ->withDisplayMode(displayMode: Category::DM_PRODUCT)
                ->withCustomAttributes(values: [
                    'meta_title' => 'Custom Meta Title',
                ])
                ->withCustomAttributes(
                    values: [
                        'meta_title' => 'Custom Meta Title Other Store',
                    ],
                    storeId: $storeFixture->getId(),
                )
                ->build(),
        );
        $this->categories[] = $categoryFixture;

        /** @var Category $categoryDefaultStore */
        $categoryDefaultStore = $this->categoryRepository->get(categoryId: $categoryFixture->getId());
        $this->assertEquals('0', $categoryDefaultStore->getIsActive(), 'Category should be inactive');
        $this->assertEquals('Custom Name', $categoryDefaultStore->getName(), 'Category name');
        $this->assertEquals('my-url-key', $categoryDefaultStore->getUrlKey(), 'Category URL key');
        $this->assertEquals(
            'Custom Description',
            $categoryDefaultStore->getCustomAttribute('description')->getValue(),
            'Category description',
        );
        $this->assertEquals(expected: Category::DM_PRODUCT, actual: $categoryDefaultStore->getDisplayMode());
        $this->assertEquals(expected: Store::DISTRO_STORE_ID, actual: $categoryDefaultStore->getStoreId());
        $customAttribute = $categoryDefaultStore->getCustomAttribute(attributeCode: 'meta_title');
        $this->assertSame(expected: 'meta_title', actual: $customAttribute->getAttributeCode());
        $this->assertSame(expected: 'Custom Meta Title', actual: $customAttribute->getValue());

        /** @var Category $categoryNewStore */
        $categoryNewStore = $this->categoryRepository->get(
            categoryId: $categoryFixture->getId(),
            storeId: (int)$storeFixture->getId(),
        );
        $this->assertEquals('1', $categoryNewStore->getIsActive(), 'Category should be active in this store');
        $this->assertEquals('Custom Name Other Store', $categoryNewStore->getName(), 'Category name');
        $this->assertEquals('my-url-key-other-store', $categoryNewStore->getUrlKey(), 'Category URL key');
        $this->assertEquals(
            'Custom Description Other Store',
            $categoryNewStore->getCustomAttribute('description')->getValue(),
            'Category description',
        );
        $this->assertEquals(expected: Category::DM_PRODUCT, actual: $categoryNewStore->getDisplayMode());
        $this->assertEquals(expected: $storeFixture->getId(), actual: $categoryNewStore->getStoreId());
        $customAttribute = $categoryNewStore->getCustomAttribute(attributeCode: 'meta_title');
        $this->assertSame(expected: 'meta_title', actual: $customAttribute->getAttributeCode());
        $this->assertSame(expected: 'Custom Meta Title Other Store', actual: $customAttribute->getValue());
    }
}
