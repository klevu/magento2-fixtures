<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\CreditmemoInterface;

class CreditmemoFixturePool
{
    /**
     * @var array<int|string, CreditmemoFixture>
     */
    private array $creditmemoFixtures = [];

    public function add(CreditmemoInterface $creditmemo, string $key = null): void
    {
        if ($key === null) {
            $this->creditmemoFixtures[] = new CreditmemoFixture(creditmemo: $creditmemo);
        } else {
            $this->creditmemoFixtures[$key] = new CreditmemoFixture(creditmemo: $creditmemo);
        }
    }

    /**
     * Returns creditmemo fixture by key, or last added if key not specified
     */
    public function get(string|int|null $key = null): CreditmemoFixture
    {
        if ($key === null) {
            $key = \array_key_last(array: $this->creditmemoFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->creditmemoFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching creditmemo found in fixture pool');
        }

        return $this->creditmemoFixtures[$key];
    }
}
