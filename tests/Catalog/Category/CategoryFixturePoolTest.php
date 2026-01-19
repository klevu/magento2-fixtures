<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class CategoryFixturePoolTest extends TestCase
{
    private CategoryFixturePool $categoryFixtures;
    private CategoryRepositoryInterface $categoryRepository;
    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->categoryFixtures = new CategoryFixturePool();
        $this->categoryRepository = $this->objectManager->create(CategoryRepositoryInterface::class);
    }

    public function testLastCategoryFixtureReturnedByDefault(): void
    {
        $firstCategory = $this->createCategory();
        $lastCategory = $this->createCategory();
        $this->categoryFixtures->add($firstCategory);
        $this->categoryFixtures->add($lastCategory);
        $categoryFixture = $this->categoryFixtures->get();
        $this->assertEquals($lastCategory->getId(), $categoryFixture->getId());
    }

    public function testExceptionThrownWhenAccessingEmptyCategoryPool(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->categoryFixtures->get();
    }

    public function testCategoryFixtureReturnedByKey(): void
    {
        $firstCategory = $this->createCategory();
        $lastCategory = $this->createCategory();
        $this->categoryFixtures->add($firstCategory, 'first');
        $this->categoryFixtures->add($lastCategory, 'last');
        $categoryFixture = $this->categoryFixtures->get('first');
        $this->assertEquals($firstCategory->getId(), $categoryFixture->getId());
    }

    public function testCategoryFixtureReturnedByNumericKey(): void
    {
        $firstCategory = $this->createCategory();
        $lastCategory = $this->createCategory();
        $this->categoryFixtures->add($firstCategory);
        $this->categoryFixtures->add($lastCategory);
        $categoryFixture = $this->categoryFixtures->get(0);
        $this->assertEquals($firstCategory->getId(), $categoryFixture->getId());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $category = $this->createCategory();
        $this->categoryFixtures->add($category, 'foo');
        $this->expectException(\OutOfBoundsException::class);
        $this->categoryFixtures->get('bar');
    }

    /**
     * @throws \Exception
     */
    public function testRollbackRemovesCategorysFromPool(): void
    {
        $category = $this->createCategoryInDb();
        $this->categoryFixtures->add($category);
        $this->categoryFixtures->rollback();
        $this->expectException(\OutOfBoundsException::class);
        $this->categoryFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackWorksWithKeys(): void
    {
        $category = $this->createCategoryInDb();
        $this->categoryFixtures->add($category, 'key');
        $this->categoryFixtures->rollback();
        $this->expectException(\OutOfBoundsException::class);
        $this->categoryFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackDeletesCategorysFromDb(): void
    {
        $category = $this->createCategoryInDb();
        $this->categoryFixtures->add($category);
        $this->categoryFixtures->rollback();
        $this->expectException(NoSuchEntityException::class);
        $this->categoryRepository->get($category->getId());
    }

    /**
     * Creates dummy category object
     */
    private function createCategory(): Category
    {
        static $nextId = 1;
        /** @var Category $category */
        $category = $this->objectManager->create(Category::class);
        $category->setId($nextId++);

        return $category;
    }

    /**
     * Creates category using builder
     *
     * @throws \Exception
     */
    private function createCategoryInDb(): Category
    {
        return CategoryBuilder::topLevelCategory()->build();
    }
}
