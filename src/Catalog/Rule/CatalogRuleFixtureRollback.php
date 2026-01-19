<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

//phpcs:disable Magento2.Annotation.MethodArguments.ArgumentMissing

namespace TddWizard\Fixtures\Catalog\Rule;

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

class CatalogRuleFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly CatalogRuleRepositoryInterface $catalogRuleRepository,
    ) {
    }

    public static function create(): CatalogRuleFixtureRollback //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(Registry::class),
            catalogRuleRepository: $objectManager->get(CatalogRuleRepositoryInterface::class),
        );
    }

    /**
     * Rollback attributes.
     *
     * @throws CouldNotDeleteException
     * @throws \Exception
     */
    public function execute(CatalogRuleFixture ...$ruleFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($ruleFixtures as $ruleFixture) {
            $this->catalogRuleRepository->deleteById(
                ruleId: $ruleFixture->getRuleId(),
            );
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
