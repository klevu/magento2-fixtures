<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Model\ClassModel as TaxClass;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\IndexFailedException;
use TddWizard\Fixtures\Traits\IsTransactionExceptionTrait;

class TaxClassBuilder
{
    use IsTransactionExceptionTrait;

    public function __construct(
        private readonly TaxClassInterface $taxClass,
        private readonly TaxClassRepositoryInterface $taxClassRepository,
    ) {
    }

    public static function addTaxClass(): TaxClassBuilder
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            taxClass: $objectManager->create(type: TaxClassInterface::class),
            taxClassRepository: $objectManager->create(type: TaxClassRepositoryInterface::class),
        );
    }

    public function withClassName(string $className): TaxClassBuilder
    {
        $builder = clone $this;
        $builder->taxClass->setClassName(className: $className);

        return $builder;
    }

    public function withClassType(string $classType): TaxClassBuilder
    {
        $builder = clone $this;
        $builder->taxClass->setClassType(classType: $classType);

        return $builder;
    }

    /**
     * @throws \Exception
     */
    public function build(): TaxClassInterface
    {
        try {
            $builder = $this->createTaxClass();
            $taxClassId = $this->taxClassRepository->save(taxClass: $builder->taxClass);

            return $this->taxClassRepository->get(taxClassId: $taxClassId);
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

    private function createTaxClass(): TaxClassBuilder
    {
        $builder = clone $this;

        if (!$builder->taxClass->getClassName()) {
            $builder->taxClass->setClassName(className: 'TDD Product Tax Class');
        }
        if (!$builder->taxClass->getClassType()) {
            $builder->taxClass->setClassType(classType: TaxClass::TAX_CLASS_TYPE_PRODUCT);
        }

        return $builder;
    }
}
