<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\Data\GroupExcludedWebsiteInterface;
use Magento\Customer\Api\Data\GroupExcludedWebsiteInterfaceFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupExcludedWebsiteRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\Exception\StateException;
use Magento\Tax\Model\TaxClass\Source\Customer as CustomerTaxClassSource;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\IndexFailedException;
use TddWizard\Fixtures\Traits\IsTransactionExceptionTrait;

class CustomerGroupBuilder
{
    use IsTransactionExceptionTrait;

    /**
     * @var array<int, array<string, string|int|null>>|null
     */
    private ?array $taxClasses = null;
    /**
     * @var int[]
     */
    private array $excludedWebsiteIds = [];

    public function __construct(
        private GroupInterface $customerGroup,
        private readonly GroupRepositoryInterface $customerGroupRepository,
        private readonly CustomerTaxClassSource $customerTaxClassSource,
        private readonly GroupExcludedWebsiteRepositoryInterface $groupExcludedWebsiteRepository,
        private readonly GroupExcludedWebsiteInterfaceFactory $groupExcludedWebsiteFactory,
    ) {
    }

    public function __clone(): void
    {
        $this->customerGroup = clone $this->customerGroup;
    }

    public static function addCustomerGroup(): CustomerGroupBuilder //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            customerGroup: $objectManager->create(type: GroupInterface::class),
            customerGroupRepository: $objectManager->create(type: GroupRepositoryInterface::class),
            customerTaxClassSource: $objectManager->create(type: CustomerTaxClassSource::class),
            groupExcludedWebsiteRepository: $objectManager->create(type: GroupExcludedWebsiteRepositoryInterface::class),
            groupExcludedWebsiteFactory: $objectManager->create(type: GroupExcludedWebsiteInterfaceFactory::class),
        );
    }

    public function withCode(string $code): CustomerGroupBuilder
    {
        $builder = clone $this;
        $builder->customerGroup->setCode(code: $code);

        return $builder;
    }

    public function withTaxClassId(int $taxClassId): CustomerGroupBuilder
    {
        $builder = clone $this;
        $builder->customerGroup->setTaxClassId(taxClassId: $taxClassId);

        return $builder;
    }

    /**
     * @param int[] $excludedIds
     */
    public function withExcludedWebsiteIds(array $excludedIds): CustomerGroupBuilder
    {
        $builder = clone $this;
        $builder->excludedWebsiteIds = array_map(
            callback: 'intval',
            array: $excludedIds,
        );

        return $builder;
    }

    /**
     * @throws \Exception
     */
    public function build(): GroupInterface
    {
        try {
            $builder = $this->createCustomerGroup();
            $customerGroup = $this->saveCustomerGroup(builder: $builder);
            $this->excludeWebsites(group: $customerGroup);

            return $customerGroup;
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

    /**
     * @throws StateException
     */
    public function buildWithoutSave(): GroupInterface
    {
        $builder = $this->createCustomerGroup();

        return $builder->customerGroup;
    }

    /**
     * @throws StateException
     */
    private function createCustomerGroup(): CustomerGroupBuilder
    {
        $builder = clone $this;
        if (!$builder->customerGroup->getCode()) {
            $builder->customerGroup->setCode(code: 'TDD Customer Group');
        }
        if (!$builder->customerGroup->getTaxClassId()) {
            $builder->customerGroup->setTaxClassId(taxClassId: $this->getDefaultTaxClassId());
        }
        $builder->customerGroup->setTaxClassName(
            taxClassName: $this->getTaxClassName(
                taxClassId: $builder->customerGroup->getTaxClassId(),
            ),
        );

        return $builder;
    }

    /**
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     */
    private function saveCustomerGroup(CustomerGroupBuilder $builder): GroupInterface
    {
        return $this->customerGroupRepository->save(group: $builder->customerGroup);
    }

    /**
     * @throws LocalizedException
     */
    private function excludeWebsites(GroupInterface $group): void
    {
        $builder = clone $this;
        foreach ($builder->excludedWebsiteIds as $websiteId) {
            /** @var GroupExcludedWebsiteInterface $groupExcludedWebsite */
            $groupExcludedWebsite = $this->groupExcludedWebsiteFactory->create();
            $groupExcludedWebsite->setGroupId(id: (int)$group->getId());
            $groupExcludedWebsite->setExcludedWebsiteId(websiteId: $websiteId);
            $this->groupExcludedWebsiteRepository->save(groupExcludedWebsite: $groupExcludedWebsite);
        }
    }

    /**
     * Get the "Retail Customer" tax class id if it exists, else return the tax class with the lowest ID
     *
     * @throws StateException
     */
    private function getDefaultTaxClassId(): int
    {
        $taxClasses = $this->getAllCustomerTaxClasses();
        $retailCustomerTaxClasses = array_filter(
            array: $taxClasses,
            callback: static fn (array $taxClass): bool => $taxClass['label'] === 'Retail Customer',
        );
        if ($retailCustomerTaxClasses) {
            $retailCustomerTaxClass = array_shift(array: $retailCustomerTaxClasses);
            if ($retailCustomerTaxClass['value'] ?? null) {
                return (int)$retailCustomerTaxClass['value'];
            }
        }
        $taxClassIds = array_map(
            callback: static fn (array $taxClass): int => (int)$taxClass['value'],
            array: $taxClasses,
        );

        return array_shift(array: $taxClassIds);
    }

    /**
     * @throws StateException
     */
    private function getTaxClassName(int $taxClassId): string
    {
        $taxClasses = $this->getAllCustomerTaxClasses();

        $taxClassNames = array_filter(
            array: $taxClasses,
            callback: static fn (array $taxClass): bool => (int)$taxClass['value'] === $taxClassId,
        );

        return $taxClassNames['label'] ?? '';
    }

    /**
     * @return array<int, array<string, string|int|null>>
     * @throws StateException
     */
    private function getAllCustomerTaxClasses(): array
    {
        if (null === $this->taxClasses) {
            $this->taxClasses = $this->customerTaxClassSource->getAllOptions();
        }

        return $this->taxClasses;
    }
}
