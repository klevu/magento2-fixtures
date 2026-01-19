<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class InvoiceFixturePoolTest extends TestCase
{
    private InvoiceFixturePool $invoiceFixtures;


    protected function setUp(): void
    {
        $this->invoiceFixtures = new InvoiceFixturePool();
    }

    /**
     * @throws \Exception
     */
    public function testLastInvoiceFixtureReturnedByDefault(): void
    {
        $firstInvoice = $this->createInvoice();
        $lastInvoice = $this->createInvoice();
        $this->invoiceFixtures->add($firstInvoice);
        $this->invoiceFixtures->add($lastInvoice);
        $invoiceFixture = $this->invoiceFixtures->get();
        $this->assertEquals($lastInvoice->getEntityId(), $invoiceFixture->getId());
    }

    public function testExceptionThrownWhenAccessingEmptyInvoicePool(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->invoiceFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testInvoiceFixtureReturnedByKey(): void
    {
        $firstInvoice = $this->createInvoice();
        $lastInvoice = $this->createInvoice();
        $this->invoiceFixtures->add($firstInvoice, 'first');
        $this->invoiceFixtures->add($lastInvoice, 'last');
        $invoiceFixture = $this->invoiceFixtures->get('first');
        $this->assertEquals($firstInvoice->getEntityId(), $invoiceFixture->getId());
    }

    /**
     * @throws \Exception
     */
    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $invoice = $this->createInvoice();
        $this->invoiceFixtures->add($invoice, 'foo');
        $this->expectException(\OutOfBoundsException::class);
        $this->invoiceFixtures->get('bar');
    }

    /**
     * @throws \Exception
     */
    private function createInvoice(): InvoiceInterface
    {
        static $nextId = 1;
        /** @var InvoiceInterface $invoice */
        $invoice = Bootstrap::getObjectManager()->create(InvoiceInterface::class);
        $invoice->setEntityId($nextId++);
        return $invoice;
    }
}
