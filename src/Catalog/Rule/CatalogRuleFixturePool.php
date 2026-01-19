<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Rule;

use Magento\CatalogRule\Api\Data\RuleInterface;

class CatalogRuleFixturePool
{
    /**
     * @var CatalogRuleFixture[]
     */
    private array $ruleFixtures = [];

    public function add(RuleInterface $rule, ?string $key = null): void
    {
        if ($key === null) {
            $this->ruleFixtures[] = new CatalogRuleFixture(rule: $rule);
        } else {
            $this->ruleFixtures[$key] = new CatalogRuleFixture(rule: $rule);
        }
    }

    /**
     * Returns store fixture by key, or last added if key not specified
     */
    public function get(string|int|null $key = null): CatalogRuleFixture
    {
        if ($key === null) {
            $key = array_key_last(array: $this->ruleFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->ruleFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching rule found in fixture pool');
        }

        return $this->ruleFixtures[$key];
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        CatalogRuleFixtureRollback::create()->execute(...array_values(array: $this->ruleFixtures));
        $this->ruleFixtures = [];
    }
}
