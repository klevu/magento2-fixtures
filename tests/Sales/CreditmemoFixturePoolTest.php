<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Sales;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class CreditmemoFixturePoolTest extends TestCase
{
    private CreditmemoFixturePool $creditmemoFixtures;

    protected function setUp(): void
    {
        $this->creditmemoFixtures = new CreditmemoFixturePool();
    }

    /**
     * @throws \Exception
     */
    public function testLastCreditmemoFixtureReturnedByDefault(): void
    {
        $firstCreditmemo = $this->createCreditmemo();
        $lastCreditmemo = $this->createCreditmemo();
        $this->creditmemoFixtures->add($firstCreditmemo);
        $this->creditmemoFixtures->add($lastCreditmemo);
        $creditmemoFixture = $this->creditmemoFixtures->get();
        $this->assertEquals($lastCreditmemo->getEntityId(), $creditmemoFixture->getId());
    }

    public function testExceptionThrownWhenAccessingEmptyCreditmemoPool(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->creditmemoFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testCreditmemoFixtureReturnedByKey(): void
    {
        $firstCreditmemo = $this->createCreditmemo();
        $lastCreditmemo = $this->createCreditmemo();
        $this->creditmemoFixtures->add($firstCreditmemo, 'first');
        $this->creditmemoFixtures->add($lastCreditmemo, 'last');
        $creditmemoFixture = $this->creditmemoFixtures->get('first');
        $this->assertEquals($firstCreditmemo->getEntityId(), $creditmemoFixture->getId());
    }

    /**
     * @throws \Exception
     */
    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $creditmemo = $this->createCreditmemo();
        $this->creditmemoFixtures->add($creditmemo, 'foo');
        $this->expectException(\OutOfBoundsException::class);
        $this->creditmemoFixtures->get('bar');
    }

    /**
     * @throws \Exception
     */
    private function createCreditmemo(): CreditmemoInterface
    {
        static $nextId = 1;
        /** @var CreditmemoInterface $creditmemo */
        $creditmemo = Bootstrap::getObjectManager()->create(CreditmemoInterface::class);
        $creditmemo->setEntityId($nextId++);
        return $creditmemo;
    }
}
