<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\InvoiceInterface;

class InvoiceFixturePool
{
    /**
     * @var InvoiceFixture[]
     */
    private array $invoiceFixtures = [];

    public function add(InvoiceInterface $invoice, string $key = null): void
    {
        if ($key === null) {
            $this->invoiceFixtures[] = new InvoiceFixture(invoice: $invoice);
        } else {
            $this->invoiceFixtures[$key] = new InvoiceFixture(invoice: $invoice);
        }
    }

    /**
     * Returns invoice fixture by key, or last added if key not specified
     */
    public function get(string|int|null $key = null): InvoiceFixture
    {
        if ($key === null) {
            $key = \array_key_last(array: $this->invoiceFixtures);
        }
        if ($key === null || !array_key_exists(key: $key, array: $this->invoiceFixtures)) {
            throw new \OutOfBoundsException(message: 'No matching invoice found in fixture pool');
        }

        return $this->invoiceFixtures[$key];
    }
}
