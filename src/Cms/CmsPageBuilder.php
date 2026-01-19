<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Cms;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\IndexFailedException;
use TddWizard\Fixtures\Traits\IsTransactionExceptionTrait;

class CmsPageBuilder
{
    use IsTransactionExceptionTrait;

    public function __construct(
        private readonly PageInterface & AbstractModel $page,
        private readonly PageRepositoryInterface $pageRepository,
    ) {
    }

    public static function addPage(): CmsPageBuilder
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            page: $objectManager->create(type: PageInterface::class),
            pageRepository: $objectManager->create(type: PageRepositoryInterface::class),
        );
    }

    public function withIdentifier(string $identifier): CmsPageBuilder
    {
        $builder = clone $this;
        $builder->page->setIdentifier(identifier: $identifier);

        return $builder;
    }

    public function withTitle(string $title): CmsPageBuilder
    {
        $builder = clone $this;
        $builder->page->setTitle(title: $title);

        return $builder;
    }

    public function withIsActive(bool $isActive): CmsPageBuilder
    {
        $builder = clone $this;
        $builder->page->setIsActive(isActive: $isActive);

        return $builder;
    }

    public function withStoreId(int $storeId): CmsPageBuilder
    {
        $builder = clone $this;
        $builder->page->setData(key: 'store_id', value: $storeId);

        return $builder;
    }

    /**
     * @param int[] $stores
     */
    public function withStores(array $stores): CmsPageBuilder
    {
        $builder = clone $this;
        $builder->page->setData(key: 'stores', value: $stores);

        return $builder;
    }

    /**
     * Use to set any data not covered by specific methods in this class
     * e.g.
     * $data = [
     *   'meta_title' => 'Custom Meta Title',
     *   'meta_keywords' => 'Keyword List',
     *   'sort_order' => 3,
     * ]
     *
     * @param array<string, mixed> $data
     */
    public function withData(array $data): CmsPageBuilder
    {
        $builder = clone $this;
        foreach ($data as $key => $value) {
            $builder->page->setData(key: $key, value: $value);
        }

        return $builder;
    }

    /**
     * @throws LocalizedException
     */
    public function build(): PageInterface & AbstractModel
    {
        try {
            $builder = $this->createPage();

            return $this->pageRepository->save(page: $builder->page);
        } catch (\Exception $exception) {
            if (
                self::isTransactionException(exception: $exception)
                || self::isTransactionException(exception: $exception->getPrevious())
            ) {
                throw IndexFailedException::becauseInitiallyTriggeredInTransaction(previous: $exception);
            }
            throw $exception;
        }
    }

    private function createPage(): CmsPageBuilder
    {
        $builder = clone $this;

        if (!$builder->page->getIdentifier()) {
            $builder->page->setIdentifier(identifier: 'tdd-page');
        }
        if (!$builder->page->getTitle()) {
            $builder->page->setTitle(
                title: ucwords(
                    string: str_replace(search: '-', replace: ' ', subject: $builder->page->getIdentifier()),
                ),
            );
        }
        if (!$builder->page->getContentHeading()) {
            $builder->page->setContentHeading(
                contentHeading: 'Heading - ' . $builder->page->getTitle(),
            );
        }
        if (!$builder->page->getContent()) {
            $builder->page->setContent(
                content: 'Content - ' . $builder->page->getTitle(),
            );
        }
        if (!$builder->page->getMetaDescription()) {
            $builder->page->setMetaDescription(
                metaDescription: 'Meta Description - ' . $builder->page->getTitle(),
            );
        }
        if (!$builder->page->getMetaTitle()) {
            $builder->page->setMetaTitle(
                metaTitle: 'Meta Title - ' . $builder->page->getTitle(),
            );
        }
        if (!$builder->page->getPageLayout()) {
            $builder->page->setPageLayout(pageLayout: '1column');
        }
        if (null === $builder->page->isActive()) {
            $builder->page->setIsActive(isActive: true);
        }
        if (null === $builder->page->getData(key: 'store_id')) {
            $builder->page->setData(
                key: 'store_id',
                value: Store::DEFAULT_STORE_ID,
            );
        }
        if (null === $builder->page->getData(key: 'stores')) {
            $builder->page->setData(
                key: 'stores',
                value: [(int)$builder->page->getData(key: 'store_id')],
            );
        }

        return $builder;
    }
}
