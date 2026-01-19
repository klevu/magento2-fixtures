<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Rule;

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Api\Data\ConditionInterface;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\CatalogRule\Model\Data\ConditionFactory;
use Magento\CatalogRule\Model\Rule\Condition\Combine as CombineCondition;
use Magento\CatalogRule\Model\Rule\Condition\Product as ProductCondition;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\SalesRule\Model\Rule;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\IndexFailedException;
use TddWizard\Fixtures\Traits\IsTransactionExceptionTrait;

class CatalogRuleBuilder
{
    use IsTransactionExceptionTrait;

    public function __construct(
        private RuleInterface $rule,
        private readonly CatalogRuleRepositoryInterface $catalogRuleRepository,
        private readonly ConditionFactory $conditionFactory,
    ) {
    }

    public function __clone(): void
    {
        $this->rule = clone $this->rule;
    }

    public static function aCatalogRule(): CatalogRuleBuilder
    {
        $objectManager = Bootstrap::getObjectManager();

        $rule = $objectManager->create(type: RuleInterface::class);
        $rule->setName(name: 'TDD Catalog Rule');
        $rule->setIsActive(isActive: true);
        $rule->setStopRulesProcessing(isStopProcessing: true);
        $rule->setSimpleAction(action: Rule::BY_PERCENT_ACTION);
        $rule->setSortOrder(sortOrder: 1);
        $rule->setDiscountAmount(amount: 10.00);

        return new static(
            rule: $rule,
            catalogRuleRepository: $objectManager->create(CatalogRuleRepositoryInterface::class),
            conditionFactory: $objectManager->create(ConditionFactory::class),
        );
    }

    public function withName(string $name): CatalogRuleBuilder
    {
        $builder = clone $this;
        $builder->rule->setName(name: $name);

        return $builder;
    }

    public function withIsActive(bool $isActive): CatalogRuleBuilder
    {
        $builder = clone $this;
        $builder->rule->setIsActive(isActive: $isActive);

        return $builder;
    }

    public function withStopRulesProcessing(bool $stopRulesProcessing): CatalogRuleBuilder
    {
        $builder = clone $this;
        $builder->rule->setStopRulesProcessing(isStopProcessing: $stopRulesProcessing);

        return $builder;
    }

    /**
     * @param int[] $websiteIds
     */
    public function withWebsiteIds(array $websiteIds): CatalogRuleBuilder
    {
        $builder = clone $this;
        $builder->rule->setWebsiteIds(
            implode(
                separator: ',',
                array: array_map(callback: 'intval', array: $websiteIds),
            ),
        );

        return $builder;
    }

    /**
     * @param int[] $customerGroupIds
     */
    public function withCustomerGroupIds(array $customerGroupIds): CatalogRuleBuilder
    {
        $builder = clone $this;
        $builder->rule->setCustomerGroupIds(
            implode(
                separator: ',',
                array: array_map(callback: 'intval', array: $customerGroupIds),
            ),
        );

        return $builder;
    }

    public function withFromDate(string $fromDate): CatalogRuleBuilder
    {
        $builder = clone $this;
        $builder->rule->setFromDate($fromDate);

        return $builder;
    }

    public function withToDate(string $toDate): CatalogRuleBuilder
    {
        $builder = clone $this;
        $builder->rule->setToDate($toDate);

        return $builder;
    }

    public function withDiscountAmount(float $discountAmount): CatalogRuleBuilder
    {
        $builder = clone $this;
        $builder->rule->setDiscountAmount(amount: $discountAmount);

        return $builder;
    }

    public function withSortOrder(int $sortOrder): CatalogRuleBuilder
    {
        $builder = clone $this;
        $builder->rule->setSortOrder(sortOrder: $sortOrder);

        return $builder;
    }

    /**
     * @param string $simpleAction
     *  \Magento\SalesRule\Model\Rule::BY_FIXED_ACTION
     *  or \Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION
     */
    public function withSimpleAction(string $simpleAction): CatalogRuleBuilder
    {
        $builder = clone $this;
        $builder->rule->setSimpleAction(action: $simpleAction);

        return $builder;
    }

    /**
     * @see \Magento\CatalogRule\Model\Rule\Condition\ConditionsToSearchCriteriaMapper::mapRuleOperatorToSQLCondition
     *
     * @param array<int, array<string, string>> $conditions
     *  data format
     *  [
     *    [
     *      'attribute' => 'klevu_test_attribute',
     *      'operator' => '==',
     *      'value' => 'test_attribute_value'
     *    ]
     *  ]
     */
    public function withConditions(array $conditions, string $type = 'all'): CatalogRuleBuilder
    {
        $builder = clone $this;
        $ruleConditions = [];
        foreach ($conditions as $condition) {
            /** @var ConditionInterface $ruleCondition */
            $ruleCondition = $builder->conditionFactory->create();
            $ruleCondition->setType(type: ProductCondition::class);
            $ruleCondition->setAttribute(attribute: $condition['attribute']);
            $ruleCondition->setOperator(operator: $condition['operator'] ?? '==');
            $ruleCondition->setValue(value: $condition['value']);
            $ruleConditions[] = $ruleCondition;
        }
        /** @var ConditionInterface $combinedCondition */
        $combinedCondition = $builder->conditionFactory->create();
        $combinedCondition->setType(type: CombineCondition::class);
        $combinedCondition->setAttribute(attribute: $type);
        $combinedCondition->setValue(value: '1');
        $combinedCondition->setConditions(conditions: $ruleConditions);

        $builder->rule->setRuleCondition(condition: $combinedCondition);

        return $builder;
    }

    /**
     * @throws \Exception
     */
    public function build(): RuleInterface
    {
        try {
            $rule = $this->createRule();
        } catch (\Exception $exception) {
            if (
                self::isTransactionException(exception: $exception)
                || self::isTransactionException(exception: $exception->getPrevious())
            ) {
                throw IndexFailedException::becauseInitiallyTriggeredInTransaction(previous: $exception);
            }
            throw $exception;
        }

        return $rule;
    }

    /**
     * @throws CouldNotSaveException
     */
    private function createRule(): RuleInterface
    {
        $builder = clone $this;

        return $builder->catalogRuleRepository->save(rule: $builder->rule);
    }

}
