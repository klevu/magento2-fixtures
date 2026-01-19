<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;

class CategoryFixture
{
    public function __construct(
        private readonly CategoryInterface $category,
    ) {
    }

    public function getCategory(): CategoryInterface
    {
        return $this->category;
    }

    public function getId(): int
    {
        return (int)$this->category->getId();
    }

    public function getUrlKey(): string
    {
        /** @var Category $category */
        $category = $this->category;

        return (string)$category->getUrlKey();
    }

    public function rollback(): void
    {
        CategoryFixtureRollback::create()->execute(categoryFixtures: $this);
    }
}
