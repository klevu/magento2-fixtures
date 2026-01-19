<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Tax\Api\Data\TaxRateInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\IndexFailedException;
use TddWizard\Fixtures\Traits\IsTransactionExceptionTrait;

class TaxRateBuilder
{
    use IsTransactionExceptionTrait;

    public function __construct(
        private readonly TaxRateInterface $taxRate,
        private readonly TaxRateRepositoryInterface $taxRateRepository,
    ) {
    }

    public static function addTaxRate(): TaxRateBuilder
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            taxRate: $objectManager->create(type: TaxRateInterface::class),
            taxRateRepository: $objectManager->create(type: TaxRateRepositoryInterface::class),
        );
    }

    public function withCode(string $code): TaxRateBuilder
    {
        $builder = clone $this;
        $builder->taxRate->setCode(code: $code);

        return $builder;
    }

    public function withRate(float $rate): TaxRateBuilder
    {
        $builder = clone $this;
        $builder->taxRate->setRate(rate: $rate);

        return $builder;
    }

    public function withCountryId(string $countryId): TaxRateBuilder
    {
        $builder = clone $this;
        $builder->taxRate->setTaxCountryId(taxCountryId: $countryId);

        return $builder;
    }

    public function withRegionId(string $taxRegionId): TaxRateBuilder
    {
        $builder = clone $this;
        $builder->taxRate->setTaxRegionId(taxRegionId: $taxRegionId);

        return $builder;
    }

    public function withZipIsRange(int $zipIsRange): TaxRateBuilder
    {
        $builder = clone $this;
        $builder->taxRate->setZipIsRange(zipIsRange: $zipIsRange);

        return $builder;
    }

    public function withZipFrom(string $zipFrom): TaxRateBuilder
    {
        $builder = clone $this;
        $builder->taxRate->setZipFrom(zipFrom: $zipFrom);

        return $builder;
    }

    public function withZipTo(string $zipTo): TaxRateBuilder
    {
        $builder = clone $this;
        $builder->taxRate->setZipTo(zipTo: $zipTo);

        return $builder;
    }

    public function withTaxPostCode(string $taxPostCode): TaxRateBuilder
    {
        $builder = clone $this;
        $builder->taxRate->setTaxPostcode(taxPostCode: $taxPostCode);

        return $builder;
    }

    /**
     * @throws \Exception
     */
    public function build(): TaxRateInterface
    {
        try {
            $builder = $this->createTaxRate();

            return $this->taxRateRepository->save(taxRate: $builder->taxRate);
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

    private function createTaxRate(): TaxRateBuilder
    {
        $builder = clone $this;

        if (!$builder->taxRate->getCode()) {
            $builder->taxRate->setCode(code: 'tdd_tax_code');
        }
        if (!$builder->taxRate->getRate()) {
            $builder->taxRate->setRate(rate: 20.00);
        }
        if (!$builder->taxRate->getTaxCountryId()) {
            $builder->taxRate->setTaxCountryId(taxCountryId: 'GB');
        }
        if (!$builder->taxRate->getTaxRegionId()) {
            $builder->taxRate->setTaxRegionId(taxRegionId: 0);
        }
        if ($builder->taxRate->getZipFrom() || $builder->taxRate->getZipTo()) {
            $builder->taxRate->setZipIsRange(zipIsRange: 1);
        }
        if (null === $builder->taxRate->getZipIsRange()) {
            $builder->taxRate->setZipIsRange(zipIsRange: 0);
        }
        if ($builder->taxRate->getZipIsRange()) {
            if (!$builder->taxRate->getZipFrom()) {
                $builder->taxRate->setZipFrom(zipFrom: 0);
            }
            if (!$builder->taxRate->getZipTo()) {
                $builder->taxRate->setZipTo(zipTo: 999999999);
            }
        } elseif (!$builder->taxRate->getTaxPostcode()) {
            $builder->taxRate->setTaxPostcode(taxPostCode: '*');
        }

        return $builder;
    }
}
