<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Tax\Api\Data\TaxRuleInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\IndexFailedException;
use TddWizard\Fixtures\Traits\IsTransactionExceptionTrait;

class TaxRuleBuilder
{
    use IsTransactionExceptionTrait;

    public function __construct(
        private readonly TaxRuleInterface $taxRule,
        private readonly TaxRuleRepositoryInterface $taxRuleRepository,
        private readonly TaxCalculation $taxCalculation,
        private readonly TaxHelper $taxHelper,
    ) {
    }

    public static function addTaxRule(): TaxRuleBuilder
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            taxRule: $objectManager->create(type: TaxRuleInterface::class),
            taxRuleRepository: $objectManager->create(type: TaxRuleRepositoryInterface::class),
            taxCalculation: $objectManager->create(type: TaxCalculation::class),
            taxHelper: $objectManager->create(type: TaxHelper::class),
        );
    }

    public function withCode(string $code): TaxRuleBuilder
    {
        $builder = clone $this;
        $builder->taxRule->setCode(code: $code);

        return $builder;
    }

    /**
     * @param int[] $taxRateIds
     */
    public function withTaxRateIds(array $taxRateIds): TaxRuleBuilder
    {
        $builder = clone $this;
        $builder->taxRule->setTaxRateIds(taxRateIds: $taxRateIds);

        return $builder;
    }

    /**
     * @param int[] $customerTaxClassIds
     */
    public function withCustomerTaxClassIds(array $customerTaxClassIds): TaxRuleBuilder
    {
        $builder = clone $this;
        $builder->taxRule->setCustomerTaxClassIds(
            customerTaxClassIds: array_map(
                callback: static fn (mixed $customerTaxClassId): int => (int)$customerTaxClassId,
                array: $customerTaxClassIds,
            ),
        );

        return $builder;
    }

    /**
     * @param int[] $productTaxClassIds
     */
    public function withProductTaxClassIds(array $productTaxClassIds): TaxRuleBuilder
    {
        $builder = clone $this;
        $builder->taxRule->setProductTaxClassIds(
            productTaxClassIds: array_map(
                callback: static fn (mixed $productTaxClassId): int => (int)$productTaxClassId,
                array: $productTaxClassIds,
            ),
        );

        return $builder;
    }

    public function withPriority(int $priority): TaxRuleBuilder
    {
        $builder = clone $this;
        $builder->taxRule->setPriority(priority: $priority);

        return $builder;
    }

    public function withCalculateSubtotal(bool $calculateSubtotal): TaxRuleBuilder
    {
        $builder = clone $this;
        $builder->taxRule->setCalculateSubtotal(calculateSubtotal: $calculateSubtotal);

        return $builder;
    }

    /**
     * @throws \Exception
     */
    public function build(): TaxRuleInterface
    {
        try {
            $builder = $this->createTaxRule();

            return $this->taxRuleRepository->save(rule: $builder->taxRule);
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

    private function createTaxRule(?int $storeId = null): TaxRuleBuilder
    {
        $builder = clone $this;

        if (!$builder->taxRule->getCode()) {
            $builder->taxRule->setCode(code: 'tdd_tax_rule_code');
        }
        if (!$builder->taxRule->getCustomerTaxClassIds()) {
            $builder->taxRule->setCustomerTaxClassIds(
                customerTaxClassIds: [$this->getDefaultCustomerTaxClassId(storeId: $storeId)],
            );
        }
        if (!$builder->taxRule->getProductTaxClassIds()) {
            $builder->taxRule->setProductTaxClassIds(
                productTaxClassIds: [$this->getDefaultProductTaxClassId()],
            );
        }
        if (!$builder->taxRule->getPriority()) {
            $builder->taxRule->setPriority(priority: 0);
        }
        if (null === $builder->taxRule->getCalculateSubtotal()) {
            $builder->taxRule->setCalculateSubtotal(calculateSubtotal: false);
        }

        return $builder;
    }

    private function getDefaultCustomerTaxClassId(?int $storeId = null): int
    {
        return (int)$this->taxCalculation->getDefaultCustomerTaxClass(store: $storeId);
    }

    private function getDefaultProductTaxClassId(): int
    {
        return (int)$this->taxHelper->getDefaultProductTaxClass();
    }
}
