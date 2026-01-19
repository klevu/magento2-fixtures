<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @internal Use CategoryFixture::rollback() or CategoryFixturePool::rollback() instead
 */
class CategoryFixtureRollback
{

    public function __construct(
        private readonly Registry $registry,
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {
    }

    public static function create(): CategoryFixtureRollback
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(type: Registry::class),
            categoryRepository: $objectManager->get(type: CategoryRepositoryInterface::class),
        );
    }

    /**
     * @throws LocalizedException
     */
    public function execute(CategoryFixture ...$categoryFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($categoryFixtures as $categoryFixture) {
            try {
                $this->categoryRepository->deleteByIdentifier(categoryId: $categoryFixture->getId());
            } catch (NoSuchEntityException) {
                // this is fine, category has already been removed
            }
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
