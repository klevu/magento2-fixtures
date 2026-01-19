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
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class WebsiteFixturePoolTest extends TestCase
{
    private ObjectManagerInterface $objectManager;
    private WebsiteFixturePool $websiteFixturePool;
    private GroupRepositoryInterface $groupRepository;
    private WebsiteRepositoryInterface $websiteRepository;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->websiteFixturePool = new WebsiteFixturePool();
        $this->groupRepository = $this->objectManager->get(GroupRepositoryInterface::class);
        $this->websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
    }

    public function testLastWebsiteFixtureReturnedByDefault(): void
    {
        $firstWebsite = $this->createWebsite();
        $lastWebsite = $this->createWebsite();

        $this->websiteFixturePool->add(website: $firstWebsite);
        $this->websiteFixturePool->add(website: $lastWebsite);
        $groupFixture = $this->websiteFixturePool->get();
        $this->assertSame(expected: $lastWebsite->getCode(), actual: $groupFixture->getCode());
    }

    public function testWebsiteFixtureReturnedByKey(): void
    {
        $firstWebsite = $this->createWebsite();
        $lastWebsite = $this->createWebsite();

        $this->websiteFixturePool->add(website: $firstWebsite, key: 'first');
        $this->websiteFixturePool->add(website: $lastWebsite, key: 'last');
        $groupFixture = $this->websiteFixturePool->get(key: 'first');
        $this->assertSame(expected: $firstWebsite->getCode(), actual: $groupFixture->getCode());
    }

    public function testWebsiteFixtureReturnedByNumericalKey(): void
    {
        $firstWebsite = $this->createWebsite();
        $lastWebsite = $this->createWebsite();

        $this->websiteFixturePool->add(website: $firstWebsite);
        $this->websiteFixturePool->add(website: $lastWebsite);
        $groupFixture = $this->websiteFixturePool->get(key: 0);
        $this->assertSame(expected: $firstWebsite->getCode(), actual: $groupFixture->getCode());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $website = $this->createWebsite();
        $this->websiteFixturePool->add(website: $website, key: 'foo');
        $this->websiteFixturePool->get(key: 'bar');
    }

    public function testRollbackRemovesWebsitesFromPool(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $this->websiteFixturePool->add(website: $this->createWebsite());
        $this->websiteFixturePool->rollback();
        $this->websiteFixturePool->get();
    }

    public function testRollbackWorksWithKeys(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $this->websiteFixturePool->add(website: $this->createWebsite(), key: 'first');
        $this->websiteFixturePool->rollback();
        $this->websiteFixturePool->get();
    }

    /**
     * @magentoDbIsolation disabled
     */
    public function testRollbackDeletesWebsiteFromDb(): void
    {
        $website = $this->createWebsiteInDb();
        $this->websiteFixturePool->add(website: $website);
        $websiteInDb = $this->websiteRepository->getById(id: (int)$website->getId());
        $this->assertSame(expected: $website->getCode(), actual: $websiteInDb->getCode());

        $this->websiteFixturePool->rollback();
        $this->websiteRepository->clean();
        $this->expectException(NoSuchEntityException::class);
        $this->websiteRepository->getById(id: (int)$website->getId());
    }

    private function createWebsite(): WebsiteInterface & AbstractModel
    {
        $lastGroupId = array_key_last(array: $this->getGroupsById());
        $lastWebsiteId = array_key_last(array: $this->getWebsitesById());

        $website = $this->objectManager->create(WebsiteInterface::class);
        $website->setCode('test_website_' . ($lastWebsiteId + 1));
        $website->setName('Test Website');
        $website->setGroupId($lastGroupId);

        return $website;
    }

    private function createWebsiteInDb(): WebsiteInterface & AbstractModel
    {
        $website = $this->createWebsite();
        // website repository does not have save method so reverting to resourceModel
        $websiteResourceModel = $website->getResource();
        $websiteResourceModel->save($website);

        return $website;
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

    /**
     * @return array<int, WebsiteInterface>
     */
    private function getWebsitesById(): array
    {
        $websites = $this->websiteRepository->getList();

        return array_combine(
            keys: array_map(
                callback: static fn (WebsiteInterface $website): int => (int)$website->getId(),
                array: $websites,
            ),
            values: $websites,
        );
    }
}
