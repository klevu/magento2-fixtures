<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class WebsiteBuilderTest extends TestCase
{
    private ObjectManagerInterface $objectManager;
    private WebsiteRepositoryInterface $websiteRepository;
    private GroupRepositoryInterface $groupRepository;
    /**
     * @var WebsiteFixture[]
     */
    private array $websites = [];

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->websiteRepository = $this->objectManager->create(WebsiteRepositoryInterface::class);
        $this->groupRepository = $this->objectManager->get(GroupRepositoryInterface::class);
        $this->websites = [];
    }

    /**
     * @throws \Exception
     */
    protected function teardown(): void
    {
        $this->deleteWebsites();
    }

    public function testDefaultWebsite(): void
    {
        $websiteFixture = new WebsiteFixture(
            website: WebsiteBuilder::addWebsite()->build(),
        );
        $this->websites[] = $websiteFixture;

        $website = $this->websiteRepository->getById(id: (int)$websiteFixture->getId());

        $this->assertSame(expected: $websiteFixture->getCode(), actual: $website->getCode());
        $this->assertSame(expected: $websiteFixture->getName(), actual: $website->getName());
        $this->assertEquals(expected: $websiteFixture->getDefaultGroupId(), actual: $website->getDefaultGroupId());
    }

    public function testWebsiteWithSpecificAttributes(): void
    {
        $lastGroupId = array_key_last(array: $this->getGroupsById());

        $websiteBuilder = WebsiteBuilder::addWebsite();
        $websiteBuilder->withCode(code: 'test_website');
        $websiteBuilder->withName(name: 'Test Website');
        $websiteBuilder->withDefaultGroupId(groupId: $lastGroupId);

        $websiteFixture = new WebsiteFixture(
            website: $websiteBuilder->build(),
        );
        $this->websites[] = $websiteFixture;

        $website = $this->websiteRepository->getById(id: (int)$websiteFixture->getId());

        $this->assertSame(expected: $websiteFixture->getCode(), actual: $website->getCode());
        $this->assertSame(expected: $websiteFixture->getName(), actual: $website->getName());
        $this->assertEquals(expected: $websiteFixture->getDefaultGroupId(), actual: $website->getDefaultGroupId());
    }

    /**
     * @throws \Exception
     */
    private function deleteWebsites(): void
    {
        if (!empty($this->websites)) {
            foreach ($this->websites as $website) {
                WebsiteFixtureRollback::create()->execute($website);
            }
        }
    }

    /**
     * @return array<int, GroupInterface>
     */
    private function getGroupsById(): array
    {
        $groups = array_filter(
            array: $this->groupRepository->getList(),
            callback: static fn (GroupInterface $group): bool => 0 !== (int)$group->getRootCategoryId(),
        );

        return array_combine(
            keys: array_map(
                callback: static fn (GroupInterface $group): int => (int)$group->getId(),
                array: $groups,
            ),
            values: $groups,
        );
    }
}
