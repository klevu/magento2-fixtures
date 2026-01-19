<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

class TaxRateFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly TaxRateRepositoryInterface $taxRateRepository,
    ) {
    }

    public static function create(): TaxRateFixtureRollback //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(type: Registry::class),
            taxRateRepository: $objectManager->get(type: TaxRateRepositoryInterface::class),
        );
    }

    /**
     * @throws \Exception
     */
    public function execute(TaxRateFixture ...$taxRateFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($taxRateFixtures as $taxRateFixture) {
            try {
                $this->taxRateRepository->deleteById(rateId: $taxRateFixture->getId());
            } catch (NoSuchEntityException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // tax rate has already been removed
            }
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
