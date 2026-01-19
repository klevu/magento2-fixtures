<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Cms;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CmsPageFixturePoolTest extends TestCase
{
    private CmsPageFixturePool $cmsPageFixtures;
    private PageRepositoryInterface $cmsPageRepository;
    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->cmsPageFixtures = new CmsPageFixturePool();
        $this->cmsPageRepository = $this->objectManager->create(type: PageRepositoryInterface::class);
    }

    public function testLastCmsPageFixtureReturnedByDefault(): void
    {
        $firstCmsPage = $this->createCmsPage();
        $lastCmsPage = $this->createCmsPage();
        $this->cmsPageFixtures->add(page: $firstCmsPage);
        $this->cmsPageFixtures->add(page: $lastCmsPage);
        $cmsPageFixture = $this->cmsPageFixtures->get();
        $this->assertEquals(expected: $lastCmsPage->getId(), actual: $cmsPageFixture->getId());
    }

    public function testExceptionThrownWhenAccessingEmptyCmsPagePool(): void
    {
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->cmsPageFixtures->get();
    }

    public function testCmsPageFixtureReturnedByKey(): void
    {
        $firstCmsPage = $this->createCmsPage();
        $lastCmsPage = $this->createCmsPage();
        $this->cmsPageFixtures->add(page: $firstCmsPage, key: 'first');
        $this->cmsPageFixtures->add(page: $lastCmsPage, key: 'last');
        $cmsPageFixture = $this->cmsPageFixtures->get(key: 'first');
        $this->assertEquals(expected: $firstCmsPage->getId(), actual: $cmsPageFixture->getId());
    }

    public function testCmsPageFixtureReturnedByNumericKey(): void
    {
        $firstCmsPage = $this->createCmsPage();
        $lastCmsPage = $this->createCmsPage();
        $this->cmsPageFixtures->add(page: $firstCmsPage);
        $this->cmsPageFixtures->add(page: $lastCmsPage);
        $cmsPageFixture = $this->cmsPageFixtures->get(key: 0);
        $this->assertEquals(expected: $firstCmsPage->getId(), actual: $cmsPageFixture->getId());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $cmsPage = $this->createCmsPage();
        $this->cmsPageFixtures->add(page: $cmsPage, key: 'foo');
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->cmsPageFixtures->get(key: 'bar');
    }

    /**
     * @throws \Exception
     */
    public function testRollbackRemovesCmsPagesFromPool(): void
    {
        $cmsPage = $this->createCmsPageInDb();
        $this->cmsPageFixtures->add(page: $cmsPage);
        $this->cmsPageFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->cmsPageFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackWorksWithKeys(): void
    {
        $cmsPage = $this->createCmsPageInDb();
        $this->cmsPageFixtures->add(page: $cmsPage, key: 'key');
        $this->cmsPageFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->cmsPageFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackDeletesCmsPagesFromDb(): void
    {
        $cmsPage = $this->createCmsPageInDb();
        $this->cmsPageFixtures->add(page: $cmsPage);
        $this->cmsPageFixtures->rollback();
        $this->expectException(exception: NoSuchEntityException::class);
        $this->cmsPageRepository->getById(pageId: $cmsPage->getId());
    }

    /**
     * Creates dummy page object
     */
    private function createCmsPage(): PageInterface
    {
        static $nextId = 1;
        /** @var PageInterface $cmsPage */
        $cmsPage = $this->objectManager->create(type: PageInterface::class);
        $cmsPage->setId($nextId++);

        return $cmsPage;
    }

    /**
     * Creates page using builder
     *
     * @throws \Exception
     */
    private function createCmsPageInDb(): PageInterface
    {
        return CmsPageBuilder::addPage()->build();
    }
}
