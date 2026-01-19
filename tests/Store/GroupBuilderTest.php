<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GroupBuilderTest extends TestCase
{
    private ObjectManagerInterface $objectManager;
    private GroupRepositoryInterface $groupRepository;
    private CategoryRepositoryInterface $categoryRepository;
    /**
     * @var GroupFixture[]
     */
    private array $groups = [];
    /**
     * @var CategoryInterface[]
     */
    private array $categories = [];

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->groupRepository = $this->objectManager->create(GroupRepositoryInterface::class);
        $this->categoryRepository = $this->objectManager->create(type: CategoryRepositoryInterface::class);
        $this->groups = [];
        $this->categories = [];
    }

    /**
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        $this->deleteGroups();
        $this->deleteCategories();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     */
    public function testDefaultGroup(): void
    {
        $groupFixture = new GroupFixture(
            group: GroupBuilder::addGroup()->build(),
        );
        $this->groups[] = $groupFixture;

        $group = $this->groupRepository->get(id: (int)$groupFixture->getId());

        $this->assertSame(expected: GroupBuilder::DEFAULT_CODE, actual: $group->getCode());
        $this->assertSame(expected: $groupFixture->getName(), actual: $group->getName());
        $this->assertEquals(expected: $groupFixture->getWebsiteId(), actual: $group->getWebsiteId());
        $this->assertEquals(expected: $groupFixture->getRootCategoryId(), actual: $group->getRootCategoryId());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     */
    public function testGroupWithSpecificAttributes(): void
    {
        $category = $this->createRootCategory();

        $groupBuilder = GroupBuilder::addGroup();
        $groupBuilder->withCode(code: 'tdd_test_store_group_abc');
        $groupBuilder->withName(name: 'ABC TDD Test Store Group');
        $groupBuilder->withRootCategoryId(categoryId: (int)$category->getId());

        $groupFixture = new GroupFixture(
            group: $groupBuilder->build(),
        );
        $this->groups[] = $groupFixture;

        $group = $this->groupRepository->get(id: (int)$groupFixture->getId());

        $this->assertSame(expected: $groupFixture->getCode(), actual: $group->getCode());
        $this->assertSame(expected: $groupFixture->getName(), actual: $group->getName());
        $this->assertEquals(expected: $groupFixture->getWebsiteId(), actual: $group->getWebsiteId());
        $this->assertEquals(expected: $category->getId(), actual: $group->getRootCategoryId());
    }

    /**
     * @throws CouldNotSaveException
     */
    private function createRootCategory(): CategoryInterface
    {
        $category = $this->objectManager->create(type: CategoryInterface::class);
        $category->setName('Root Category');
        $category->setIsActive(true);
        $category->setPath('1');
        $category->setParentId(1);
        $this->categoryRepository->save(category: $category);

        return $category;
    }

    /**
     * @throws \Exception
     */
    private function deleteGroups(): void
    {
        if (!empty($this->groups)) {
            foreach ($this->groups as $group) {
                GroupFixtureRollback::create()->execute($group);
            }
        }
    }

    private function deleteCategories(): void
    {
        if (!empty($this->categories)) {
            foreach ($this->categories as $category) {
                try {
                    $this->categoryRepository->delete(category: $category);
                } catch (\Exception) {
                    // Category already removed
                }
            }
        }
    }
}
