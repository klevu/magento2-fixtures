<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\InvoiceInterface;

class InvoiceFixture
{
    public function __construct(
        private readonly InvoiceInterface $invoice,
    ) {
    }

    public function getInvoice(): InvoiceInterface
    {
        return $this->invoice;
    }

    public function getId(): int
    {
        return (int)$this->invoice->getEntityId();
    }
}
