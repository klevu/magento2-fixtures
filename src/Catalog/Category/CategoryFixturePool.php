<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\LocalizedException;

class CategoryFixturePool
{
    /**
     * @var array<int|string, CategoryFixture>
     */
    private array $categoryFixtures = [];

    public function add(CategoryInterface $category, string $key = null): void
    {
        if ($key === null) {
            $this->categoryFixtures[] = new CategoryFixture(category: $category);
        } else {
            $this->categoryFixtures[$key] = new CategoryFixture(category: $category);
        }
    }

    /**
     * Returns category fixture by key, or last added if key not specified
     */
    public function get(int|string|null $key = null): CategoryFixture
    {
        if ($key === null) {
            $key = \array_key_last(array: $this->categoryFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->categoryFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching category found in fixture pool');
        }

        return $this->categoryFixtures[$key];
    }

    /**
     * @throws LocalizedException
     */
    public function rollback(): void
    {
        CategoryFixtureRollback::create()->execute(
            ...array_values(array: $this->categoryFixtures),
        );
        $this->categoryFixtures = [];
    }
}
