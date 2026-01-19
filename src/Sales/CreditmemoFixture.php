<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\CreditmemoInterface;

class CreditmemoFixture
{
    public function __construct(
        private readonly CreditmemoInterface $creditmemo,
    ) {
    }

    public function getCreditmemo(): CreditmemoInterface
    {
        return $this->creditmemo;
    }

    public function getId(): int
    {
        return (int)$this->creditmemo->getEntityId();
    }
}
