<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GroupFixturePoolTest extends TestCase
{
    private ObjectManagerInterface $objectManager;
    private GroupFixturePool $groupFixturePool;

    private GroupRepositoryInterface $groupRepository;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->groupFixturePool = new GroupFixturePool();
        $this->groupRepository = $this->objectManager->get(GroupRepositoryInterface::class);
    }

    public function testLastGroupFixtureReturnedByDefault(): void
    {
        $firstGroup = $this->createGroup();
        $lastGroup = $this->createGroup();

        $this->groupFixturePool->add(group: $firstGroup);
        $this->groupFixturePool->add(group: $lastGroup);
        $groupFixture = $this->groupFixturePool->get();
        $this->assertSame(expected: $lastGroup->getCode(), actual: $groupFixture->getCode());
    }

    public function testGroupFixtureReturnedByKey(): void
    {
        $firstGroup = $this->createGroup();
        $lastGroup = $this->createGroup();

        $this->groupFixturePool->add(group: $firstGroup, key: 'first');
        $this->groupFixturePool->add(group: $lastGroup, key: 'last');
        $groupFixture = $this->groupFixturePool->get(key: 'first');
        $this->assertSame(expected: $firstGroup->getCode(), actual: $groupFixture->getCode());
    }

    public function testGroupFixtureReturnedByNumericalKey(): void
    {
        $firstGroup = $this->createGroup();
        $lastGroup = $this->createGroup();

        $this->groupFixturePool->add(group: $firstGroup);
        $this->groupFixturePool->add(group: $lastGroup);
        $groupFixture = $this->groupFixturePool->get(key: 0);
        $this->assertSame(expected: $firstGroup->getCode(), actual: $groupFixture->getCode());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $group = $this->createGroup();
        $this->groupFixturePool->add(group: $group, key: 'foo');
        $this->groupFixturePool->get(key: 'bar');
    }

    public function testRollbackRemovesGroupsFromPool(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $this->groupFixturePool->add(group: $this->createGroup());
        $this->groupFixturePool->rollback();
        $this->groupFixturePool->get();
    }

    public function testRollbackWorksWithKeys(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $this->groupFixturePool->add(group: $this->createGroup(), key: 'first');
        $this->groupFixturePool->rollback();
        $this->groupFixturePool->get();
    }

    /**
     * @magentoDbIsolation disabled
     */
    public function testRollbackDeletesGroupsFromDb(): void
    {
        $group = $this->createGroupInDb();
        $this->groupFixturePool->add(group: $group);
        $groupInDb = $this->groupRepository->get(id: (int)$group->getId());
        $this->assertSame(expected: $group->getCode(), actual: $groupInDb->getCode());

        $this->groupFixturePool->rollback();
        $this->groupRepository->clean();
        $this->expectException(NoSuchEntityException::class);
        $this->groupRepository->get(id: (int)$group->getId());
    }

    private function createGroup(): GroupInterface & AbstractModel
    {
        $lastGroupId = array_key_last(array: $this->getGroupsById());

        $group = $this->objectManager->create(GroupInterface::class);
        $group->setCode('tdd_group_' . ($lastGroupId + 1));
        $group->setName('Tdd Group');

        return $group;
    }

    private function createGroupInDb(): GroupInterface & AbstractModel
    {
        $group = $this->createGroup();
        // group repository does not have save method so reverting to resourceModel
        $groupResourceModel = $group->getResource();
        $groupResourceModel->save($group);

        return $group;
    }

    /**
     * @return array<int, GroupInterface>
     */
    private function getGroupsById(): array
    {
        $groups = $this->groupRepository->getList();

        return array_combine(
            keys: array_map(
                callback: static fn (GroupInterface $group): int => (int)$group->getId(),
                array: $groups,
            ),
            values: $groups,
        );
    }
}
