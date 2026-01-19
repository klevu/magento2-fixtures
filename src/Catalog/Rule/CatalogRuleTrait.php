<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Rule;

use Magento\Customer\Model\Group;
use Magento\SalesRule\Model\Rule;
use TddWizard\Fixtures\Exception\FixturePoolMissingException;

trait CatalogRuleTrait
{
    private ?CatalogRuleFixturePool $ruleFixturePool = null;

    /**
     * @see \Magento\CatalogRule\Model\Rule\Condition\ConditionsToSearchCriteriaMapper::mapRuleOperatorToSQLCondition
     *
     * @param array<string, mixed> $ruleData = [
     *     'conditions' => [
     *         [
     *             'attribute' => 'tdd_attribute',
     *             'operator' => '==',
     *             'value' => 'test_attribute_value'
     *         ],
     *         [
     *              'attribute' => 'sku',
     *              'operator' => '{}', // like
     *              'value' => 'SKU_ABC_'
     *          ],
     *     ],
     *     'condition_type' => 'all',
     * ];
     *
     * @throws \Exception
     */
    public function createCatalogRule(array $ruleData = []): void
    {
        if (null === $this->ruleFixturePool) {
            throw new FixturePoolMissingException(
                message: 'ruleFixturePool has not been created in your test setUp method.',
            );
        }
        $ruleBuilder = CatalogRuleBuilder::aCatalogRule();

        $ruleBuilder = $ruleBuilder->withName(
            name: $ruleData['name'] ?? 'TDD Catalog Rule',
        );

        $ruleBuilder = $ruleBuilder->withIsActive(
            isActive: $ruleData['is_active'] ?? true,
        );

        $ruleBuilder = $ruleBuilder->withStopRulesProcessing(
            stopRulesProcessing: $ruleData['stop_rules'] ?? true,
        );

        $ruleBuilder = $ruleBuilder->withWebsiteIds(
            websiteIds: $ruleData['website_ids'] ?? [1],
        );

        $ruleBuilder = $ruleBuilder->withCustomerGroupIds(
            customerGroupIds: $ruleData['customer_group_ids'] ?? [Group::NOT_LOGGED_IN_ID],
        );

        $ruleBuilder = $ruleBuilder->withFromDate(
            fromDate: $ruleData['from_date'] ?? date(format: 'Y-m-d H:i:s', timestamp: time() - (3600 * 24)),
        );

        $ruleBuilder = $ruleBuilder->withToDate(
            toDate: $ruleData['to_date'] ?? date(format: 'Y-m-d H:i:s', timestamp: time() + (3600 * 24)),
        );

        $ruleBuilder = $ruleBuilder->withDiscountAmount(
            discountAmount: $ruleData['discount_amount'] ?? 10.00,
        );

        $ruleBuilder = $ruleBuilder->withSimpleAction(
            simpleAction: ($ruleData['is_percent'] ?? true)
                ? Rule::BY_PERCENT_ACTION
                : Rule::BY_FIXED_ACTION,
        );

        $ruleBuilder = $ruleBuilder->withSortOrder(
            sortOrder: $ruleData['sort_order'] ?? 1,
        );

        if ($ruleData['conditions'] ?? null) {
            $ruleBuilder = $ruleBuilder->withConditions(
                conditions: $ruleData['conditions'],
                type: $ruleData['condition_type'] ?? 'all',
            );
        }

        $this->ruleFixturePool->add(
            rule: $ruleBuilder->build(),
            key: $ruleData['key'] ?? 'tdd_catalog_rule',
        );
    }
}
