<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

class TaxClassFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly TaxClassRepositoryInterface $taxClassRepository,
    ) {
    }

    public static function create(): TaxClassFixtureRollback //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->create(type: Registry::class),
            taxClassRepository: $objectManager->create(type: TaxClassRepositoryInterface::class),
        );
    }

    /**
     * @throws CouldNotDeleteException
     */
    public function execute(TaxClassFixture ...$taxClassFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($taxClassFixtures as $taxClassFixture) {
            try {
                $this->taxClassRepository->deleteById(taxClassId: $taxClassFixture->getId());
            } catch (NoSuchEntityException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // tax rate has already been removed
            }
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
