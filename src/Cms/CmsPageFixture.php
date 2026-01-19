<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Cms;

use Magento\Cms\Api\Data\PageInterface;

class CmsPageFixture
{
    public function __construct(
        private readonly PageInterface $page,
    ) {
    }

    public function getPage(): PageInterface
    {
        return $this->page;
    }

    public function getId(): int
    {
        return (int)$this->page->getId();
    }

    public function getIdentifier(): string
    {
        return $this->page->getIdentifier();
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        CmsPageFixtureRollback::create()->execute(pageFixtures: $this);
    }
}
