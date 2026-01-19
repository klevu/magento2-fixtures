<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Cms;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Store\StoreFixturePool;
use TddWizard\Fixtures\Store\StoreTrait;

class CmsPageBuilderTest extends TestCase
{
    use StoreTrait;

    private PageRepositoryInterface $cmsPageRepository;
    /**
     * @var CmsPageFixture[]
     */
    private array $cmsPages;

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->cmsPageRepository = $objectManager->create(type: PageRepositoryInterface::class);
        $this->storeFixturePool = $objectManager->get(StoreFixturePool::class);
        $this->cmsPages = [];
    }

    /**
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteCmsPages();
        $this->storeFixturePool->rollback();
    }

    public function testCmsPage_DefaultValues(): void
    {
        $cmsPageFixture = new CmsPageFixture(
            page: CmsPageBuilder::addPage()->build(),
        );
        $this->cmsPages[] = $cmsPageFixture;
        $cmsPage = $this->cmsPageRepository->getById(pageId: $cmsPageFixture->getId());

        $this->assertSame(expected: 'tdd-page', actual: $cmsPage->getIdentifier());
        $this->assertSame(expected: 'Tdd Page', actual: $cmsPage->getTitle());
        $this->assertSame(expected: 'Heading - Tdd Page', actual: $cmsPage->getContentHeading());
        $this->assertSame(expected: 'Content - Tdd Page', actual: $cmsPage->getContent());
        $this->assertSame(expected: 'Meta Description - Tdd Page', actual: $cmsPage->getMetaDescription());
        $this->assertSame(expected: 'Meta Title - Tdd Page', actual: $cmsPage->getMetaTitle());
        $this->assertSame(expected: '1column', actual: $cmsPage->getPageLayout());
        $this->assertTrue(condition: $cmsPage->isActive());
        $stores = $cmsPage->getData(key: 'store_id');
        $this->assertIsArray(actual: $stores);
        $this->assertCount(expectedCount: 1, haystack: $stores);
        $this->assertContains(needle: (string)Store::DEFAULT_STORE_ID, haystack: $stores);
    }

    public function testCmsPage_CustomValues(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturePool->get(key: 'tdd_store');

        $cmsPageBuilder = CmsPageBuilder::addPage();
        $cmsPageBuilder->withIdentifier(identifier: 'tdd-custom-identifier');
        $cmsPageBuilder->withTitle(title: 'TDD Custom Title');
        $cmsPageBuilder->withIsActive(isActive: false);
        $cmsPageBuilder->withStoreId(storeId: $storeFixture->getId());
        $cmsPageBuilder->withData(data: [
            PageInterface::META_TITLE => 'Custom Meta Title',
            PageInterface::META_KEYWORDS => 'Keyword List',
            PageInterface::SORT_ORDER => 3,
            PageInterface::PAGE_LAYOUT => '2columns-right',
        ]);

        $cmsPageFixture = new CmsPageFixture(
            page: $cmsPageBuilder->build(),
        );
        $this->cmsPages[] = $cmsPageFixture;
        $cmsPage = $this->cmsPageRepository->getById(pageId: $cmsPageFixture->getId());

        $this->assertSame(expected: 'tdd-custom-identifier', actual: $cmsPage->getIdentifier());
        $this->assertSame(expected: 'TDD Custom Title', actual: $cmsPage->getTitle());
        $this->assertSame(expected: 'Heading - TDD Custom Title', actual: $cmsPage->getContentHeading());
        $this->assertSame(expected: 'Content - TDD Custom Title', actual: $cmsPage->getContent());
        $this->assertSame(expected: 'Meta Description - TDD Custom Title', actual: $cmsPage->getMetaDescription());
        $this->assertSame(expected: 'Custom Meta Title', actual: $cmsPage->getMetaTitle());
        $this->assertSame(expected: 'Keyword List', actual: $cmsPage->getMetaKeywords());
        $this->assertSame(expected: '2columns-right', actual: $cmsPage->getPageLayout());
        $this->assertEquals(expected: 3, actual: $cmsPage->getSortOrder());
        $this->assertFalse(condition: $cmsPage->isActive());
        $stores = $cmsPage->getData(key: 'store_id');
        $this->assertIsArray(actual: $stores);
        $this->assertCount(expectedCount: 1, haystack: $stores);
        $this->assertContains(needle: (string)$storeFixture->getId(), haystack: $stores);
    }

    /**
     * @throws LocalizedException
     */
    private function deleteCmsPages(): void
    {
        foreach ($this->cmsPages as $cmsPage) {
            try {
                $this->cmsPageRepository->delete(page: $cmsPage->getPage());
            } catch (NoSuchEntityException) {
                // CMS page already removed
            }
        }
    }
}
