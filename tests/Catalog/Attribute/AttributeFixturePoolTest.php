<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Attribute;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class AttributeFixturePoolTest extends TestCase
{
    private ObjectManagerInterface $objectManager;
    private AttributeFixturePool $attributeFixturePool;
    private AttributeRepositoryInterface $attributeRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->attributeFixturePool = new AttributeFixturePool();
        $this->attributeRepository = $this->objectManager->get(type: AttributeRepositoryInterface::class);
    }

    public function testLastAttributeReturnedByDefault(): void
    {
        $firstAttribute = $this->createAttribute();
        $lastAttribute = $this->createAttribute();
        $this->attributeFixturePool->add(attribute: $firstAttribute);
        $this->attributeFixturePool->add(attribute: $lastAttribute);
        $attributeFixture = $this->attributeFixturePool->get();
        $this->assertSame(expected: $lastAttribute->getAttributeCode(), actual: $attributeFixture->getAttributeCode());
    }

    public function testExceptionThrownWhenAccessingEmptyProductPool(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->attributeFixturePool->get();
    }

    public function testAttributeFixtureReturnedByKey(): void
    {
        $firstAttribute = $this->createAttribute();
        $lastAttribute = $this->createAttribute();
        $this->attributeFixturePool->add(attribute: $firstAttribute, key: 'first');
        $this->attributeFixturePool->add(attribute: $lastAttribute, key: 'last');
        $attributeFixture = $this->attributeFixturePool->get('first');
        $this->assertSame(expected: $firstAttribute->getAttributeCode(), actual: $attributeFixture->getAttributeCode());
    }

    public function testAttributeFixtureReturnedByNumericKey(): void
    {
        $firstAttribute = $this->createAttribute();
        $lastAttribute = $this->createAttribute();
        $this->attributeFixturePool->add(attribute: $firstAttribute);
        $this->attributeFixturePool->add(attribute: $lastAttribute);
        $attributeFixture = $this->attributeFixturePool->get(key: 0);
        $this->assertSame(expected: $firstAttribute->getAttributeCode(), actual: $attributeFixture->getAttributeCode());
    }

    /**
     * @throws \Exception
     */
    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $attribute = $this->createAttribute();
        $this->attributeFixturePool->add(attribute: $attribute, key: 'foo');
        $this->expectException(\OutOfBoundsException::class);
        $this->attributeFixturePool->get(key: 'bar');
    }

    /**
     * @throws \Exception
     */
    public function testRollbackRemovesProductsFromPool(): void
    {
        $attribute = $this->createAttributeInDb();
        $this->attributeFixturePool->add(attribute: $attribute);
        $this->attributeFixturePool->rollback();
        $this->expectException(\OutOfBoundsException::class);
        $this->attributeFixturePool->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackWorksWithKeys(): void
    {
        $attribute = $this->createAttributeInDb();
        $this->attributeFixturePool->add(attribute: $attribute, key: 'key');
        $this->attributeFixturePool->rollback();
        $this->expectException(\OutOfBoundsException::class);
        $this->attributeFixturePool->get();
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testRollbackDeletesProductsFromDb(): void
    {
        $attribute = $this->createAttributeInDb();
        $this->attributeFixturePool->add(attribute: $attribute);
        $this->attributeFixturePool->rollback();
        $this->expectException(NoSuchEntityException::class);
        $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attribute->getAttributeCode(),
        );
    }

    private function createAttribute(): AttributeInterface
    {
        static $nextId = 1;
        $attribute = $this->objectManager->create(AttributeInterface::class);
        $attribute->setAttributeCode('tdd_attribute_code_' . $nextId);
        $attribute->setAttributeId($nextId);

        return $attribute;
    }

    private function createAttributeInDb(): AttributeInterface
    {
        return AttributeBuilder::aProductAttribute('tdd_attribute_code')->build();
    }
}
