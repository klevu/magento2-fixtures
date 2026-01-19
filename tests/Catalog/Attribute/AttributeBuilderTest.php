<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Attribute;

use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\FrontendLabel;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Framework\Exception\StateException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Weee\Model\Attribute\Backend\Weee\Tax as BackendWeeTax;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Store\StoreFixturePool;
use TddWizard\Fixtures\Store\StoreTrait;

class AttributeBuilderTest extends TestCase
{
    use StoreTrait;

    private AttributeRepositoryInterface $attributeRepository;
    /**
     * @var AttributeFixture[]
     */
    private array $attributes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->storeFixturePool = $objectManager->get(type: StoreFixturePool::class);
        $this->attributeRepository = $objectManager->get(type: AttributeRepositoryInterface::class);
        $this->attributes = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteAttributes();
        $this->storeFixturePool->rollback();
    }

    public function testProductTextAttribute_DefaultValues(): void
    {
        $attributeFixture = new AttributeFixture(
            attribute: AttributeBuilder::aProductAttribute(
                attributeCode: 'tdd_attribute_code',
            )->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'varchar', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'text', actual: $attribute->getFrontendInput());
        $this->assertEquals(expected: 0, actual: $attribute->getIsUnique());
        $this->assertEquals(expected: 0, actual: $attribute->getIsRequired());
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_searchable'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_visible_in_advanced_search'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_comparable'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_filterable'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_filterable_in_search'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_used_for_promo_rules'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_html_allowed_on_front'));
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'is_visible_on_front'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'used_in_product_listing'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'used_for_sort_by'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_global'));
    }

    public function testProductTextAttribute_CustomValues(): void
    {
        $attributeBuilder = AttributeBuilder::aProductAttribute(
            attributeCode: 'tdd_attribute_code',
            attributeData: [
                'is_unique' => 1,
                'is_required' => 1,
                'is_searchable' => 1,
                'is_visible_in_advanced_search' => 1,
                'is_comparable' => 1,
                'is_used_for_promo_rules' => 1,
                'is_html_allowed_on_front' => 1,
                'is_visible_on_front' => 0,
                'used_in_product_listing' => 1,
                'used_for_sort_by' => 1,
                'is_global' => 1,
            ],
        );

        $attributeFixture = new AttributeFixture(
            attribute: $attributeBuilder->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'varchar', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'text', actual: $attribute->getFrontendInput());
        $this->assertEquals(expected: 1, actual: $attribute->getIsUnique());
        $this->assertEquals(expected: 1, actual: $attribute->getIsRequired());
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'is_searchable'));
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'is_visible_in_advanced_search'));
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'is_comparable'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_filterable'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_filterable_in_search'));
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'is_used_for_promo_rules'));
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'is_html_allowed_on_front'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_visible_on_front'));
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'used_in_product_listing'));
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'used_for_sort_by'));
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'is_global'));
    }

    public function testProductTextareaAttribute_DefaultValues(): void
    {
        $attributeFixture = new AttributeFixture(
            attribute: AttributeBuilder::aProductAttribute(
                attributeCode: 'tdd_attribute_code_textarea',
                attributeType: 'textarea',
            )->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'text', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'textarea', actual: $attribute->getFrontendInput());
    }

    public function testProductDateAttribute_DefaultValues(): void
    {
        $attributeFixture = new AttributeFixture(
            attribute: AttributeBuilder::aProductAttribute(
                attributeCode: 'tdd_attribute_code_date',
                attributeType: 'date',
            )->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'datetime', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'date', actual: $attribute->getFrontendInput());
    }

    public function testProductEnumAttribute_DefaultValues(): void
    {
        $attributeBuilder = AttributeBuilder::aProductAttribute(
            attributeCode: 'tdd_attribute_code_enum',
            attributeType: 'enum',
        );
        $attributeBuilder = $attributeBuilder->withOptions(attributeOptionValues: [
            '1' => 'Option 1',
            '2' => 'Option 2',
            '3' => 'Option 3',
        ]);

        $attributeFixture = new AttributeFixture(
            attribute: $attributeBuilder->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'int', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'select', actual: $attribute->getFrontendInput());
    }

    public function testProductSelectAttribute_DefaultValues(): void
    {
        $attributeBuilder = AttributeBuilder::aProductAttribute(
            attributeCode: 'tdd_attribute_code_select',
            attributeType: 'select',
        );
        $attributeBuilder = $attributeBuilder->withOptions(attributeOptionValues: [
            '1' => 'Option 1',
            '2' => 'Option 2',
            '3' => 'Option 3',
        ]);

        $attributeFixture = new AttributeFixture(
            attribute: $attributeBuilder->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'varchar', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'select', actual: $attribute->getFrontendInput());
    }

    public function testProductMultiselectAttribute_DefaultValues(): void
    {
        $attributeBuilder = AttributeBuilder::aProductAttribute(
            attributeCode: 'tdd_attribute_code_multiselect',
            attributeType: 'multiselect',
        );
        $attributeBuilder = $attributeBuilder->withOptions(attributeOptionValues: [
            '1' => 'Option 1',
            '2' => 'Option 2',
            '3' => 'Option 3',
        ]);
        $attributeFixture = new AttributeFixture(
            attribute: $attributeBuilder->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'text', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'multiselect', actual: $attribute->getFrontendInput());
    }

    public function testProductYesNoAttribute_DefaultValues(): void
    {
        $attributeFixture = new AttributeFixture(
            attribute: AttributeBuilder::aProductAttribute(
                attributeCode: 'tdd_attribute_code_yes_no',
                attributeType: 'yes_no',
            )->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'int', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'boolean', actual: $attribute->getFrontendInput());
        $this->assertSame(expected: Boolean::class, actual: $attribute->getSourceModel());
    }

    public function testProductPriceAttribute_DefaultValues(): void
    {
        $attributeFixture = new AttributeFixture(
            attribute: AttributeBuilder::aProductAttribute(
                attributeCode: 'tdd_attribute_code_price',
                attributeType: 'price',
            )->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'decimal', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'price', actual: $attribute->getFrontendInput());
    }

    public function testProductIMageAttribute_DefaultValues(): void
    {
        $attributeFixture = new AttributeFixture(
            attribute: AttributeBuilder::aProductAttribute(
                attributeCode: 'tdd_attribute_code_image',
                attributeType: 'image',
            )->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'varchar', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'media_image', actual: $attribute->getFrontendInput());
    }

    public function testProductWeeAttribute_DefaultValues(): void
    {
        $attributeFixture = new AttributeFixture(
            attribute: AttributeBuilder::aProductAttribute(
                attributeCode: 'tdd_attribute_code_wee',
                attributeType: 'weee',
            )->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'static', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'weee', actual: $attribute->getFrontendInput());
        $this->assertSame(expected: BackendWeeTax::class, actual: $attribute->getBackendModel());
    }

    public function testProductAttribute_withLabelsPerStore(): void
    {
        $this->createStore([
            'key' => 'tdd_store_1',
        ]);
        $storeFixture1 = $this->storeFixturePool->get(key: 'tdd_store_1');
        $this->createStore([
            'key' => 'tdd_store_2',
            'code' => 'tdd_store_2',
        ]);
        $storeFixture2 = $this->storeFixturePool->get(key: 'tdd_store_2');

        $attributeBuilder = AttributeBuilder::aProductAttribute(
            attributeCode: 'tdd_attribute_code_text',
            attributeType: 'text',
        );
        $attributeBuilder = $attributeBuilder->withLabel('Global Label');
        $attributeBuilder = $attributeBuilder->withLabels(labels: [
            $storeFixture1->getId() => 'Label Store 1',
            $storeFixture2->getId() => 'Label Store 2',
        ]);
        $attributeFixture = new AttributeFixture(
            attribute: $attributeBuilder->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );
        $this->assertSame(expected: 'Global Label', actual: $attribute->getDefaultFrontendLabel());
        $labels = $attribute->getFrontendLabels();
        $labelStore1Array = array_filter(
            array: $labels,
            callback: static fn (FrontendLabel $label): bool => $label->getStoreId() === (int)$storeFixture1->getId(),
        );
        $labelStore1 = array_shift($labelStore1Array);
        $this->assertSame(
            expected: 'Label Store 1',
            actual: $labelStore1->getLabel(),
        );
        $labelStore2Array = array_filter(
            array: $labels,
            callback: static fn (FrontendLabel $label): bool => $label->getStoreId() === (int)$storeFixture2->getId(),
        );
        $labelStore2 = array_shift($labelStore2Array);
        $this->assertSame(
            expected: 'Label Store 2',
            actual: $labelStore2->getLabel(),
        );
    }

    public function testProductConfigurableAttribute(): void
    {
        $attributeBuilder = AttributeBuilder::aProductAttribute(
            attributeCode: 'tdd_attribute_code_configurable',
            attributeType: 'configurable',
        );
        $attributeBuilder = $attributeBuilder->withOptions(attributeOptionValues: [
            '1' => 'Option 1',
            '2' => 'Option 2',
            '3' => 'Option 3',
        ]);
        $attributeFixture = new AttributeFixture(
            attribute: $attributeBuilder->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'int', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'select', actual: $attribute->getFrontendInput());
        $this->assertEquals(expected: 0, actual: $attribute->getIsUnique());
        $this->assertEquals(expected: 0, actual: $attribute->getIsRequired());
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_searchable'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_visible_in_advanced_search'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_comparable'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_filterable'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_filterable_in_search'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_used_for_promo_rules'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_html_allowed_on_front'));
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'is_visible_on_front'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'used_in_product_listing'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'used_for_sort_by'));
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'is_global'));
    }

    public function testCategoryTextAttribute_DefaultValues(): void
    {
        $attributeFixture = new AttributeFixture(
            attribute: AttributeBuilder::aCategoryAttribute(
                attributeCode: 'tdd_attribute_code',
            )->build(),
        );
        $this->attributes[] = $attributeFixture;

        $attribute = $this->attributeRepository->get(
            entityTypeCode: CategoryAttributeInterface::ENTITY_TYPE_CODE,
            attributeCode: $attributeFixture->getAttributeCode(),
        );

        $this->assertSame(expected: 'varchar', actual: $attribute->getBackendType());
        $this->assertSame(expected: 'text', actual: $attribute->getFrontendInput());
        $this->assertEquals(expected: 0, actual: $attribute->getIsUnique());
        $this->assertEquals(expected: 0, actual: $attribute->getIsRequired());
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_searchable'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_visible_in_advanced_search'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_comparable'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_filterable'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_filterable_in_search'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_used_for_promo_rules'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_html_allowed_on_front'));
        $this->assertEquals(expected: 1, actual: $attribute->getData(key: 'is_visible_on_front'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'used_in_product_listing'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'used_for_sort_by'));
        $this->assertEquals(expected: 0, actual: $attribute->getData(key: 'is_global'));
    }

    private function deleteAttributes(): void
    {
        foreach ($this->attributes as $attribute) {
            try {
                $this->attributeRepository->delete(attribute: $attribute->getAttribute());
            } catch (StateException) {
                // attribute already removed
            }
        }
    }
}
