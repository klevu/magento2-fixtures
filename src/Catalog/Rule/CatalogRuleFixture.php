<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Rule;

use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\Framework\Exception\CouldNotDeleteException;

class CatalogRuleFixture
{
    public function __construct(
        private readonly RuleInterface $rule,
    ) {
    }

    /**
     * @return RuleInterface
     */
    public function getRule(): RuleInterface
    {
        return $this->rule;
    }

    /**
     * @return int
     */
    public function getRuleId(): int
    {
        return (int)$this->rule->getRuleId();
    }

    /**
     * @throws CouldNotDeleteException
     */
    public function rollback(): void
    {
        CatalogRuleFixtureRollback::create()->execute(ruleFixtures: $this);
    }
}
