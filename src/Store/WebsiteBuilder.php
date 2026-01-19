<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ResourceModel\Website as WebsiteResourceModel;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\IndexFailedException;
use TddWizard\Fixtures\Traits\IsTransactionExceptionTrait;

class WebsiteBuilder
{
    use IsTransactionExceptionTrait;

    public const DEFAULT_CODE = 'tdd_website';

    public function __construct(
        private readonly WebsiteInterface & AbstractModel $website,
    ) {
    }

    public static function addWebsite(): WebsiteBuilder //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            $objectManager->create(type: WebsiteInterface::class),
        );
    }

    public function withCode(string $code): WebsiteBuilder
    {
        $builder = clone $this;
        $builder->website->setCode(code: $code);

        return $builder;
    }

    public function withName(string $name): WebsiteBuilder
    {
        $builder = clone $this;
        $builder->website->setName(name: $name);

        return $builder;
    }

    public function withDefaultGroupId(int $groupId): WebsiteBuilder
    {
        $builder = clone $this;
        $builder->website->setDefaultGroupId(defaultGroupId: $groupId);

        return $builder;
    }

    /**
     * @throws \Exception
     */
    public function build(): WebsiteInterface
    {
        try {
            return $this->saveWebsite(
                builder: $this->createWebsite(),
            );
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

    public function buildWithoutSave(): WebsiteInterface
    {
        $builder = $this->createWebsite();

        return $builder->website;
    }

    private function createWebsite(): WebsiteBuilder
    {
        $builder = clone $this;

        if (!$builder->website->getCode()) {
            $builder->website->setCode(code: static::DEFAULT_CODE);
        }
        if (!$builder->website->getName()) {
            $builder->website->setName(
                name: ucwords(
                    string: str_replace(search: ['_', '-'], replace: ' ', subject: $builder->website->getCode()),
                ),
            );
        }

        return $builder;
    }

    /**
     * @throws AlreadyExistsException
     */
    private function saveWebsite(WebsiteBuilder $builder): WebsiteInterface
    {
        // website repository has no save methods so revert to resourceModel
        /** @var WebsiteResourceModel $websiteResourceModel */
        $websiteResourceModel = $this->website->getResource();
        $websiteResourceModel->save(object: $builder->website);

        return $builder->website;
    }
}
