<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Attribute;

use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Api\Data\EavAttributeInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product\Attribute\OptionManagement;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as AttributeResourceModel;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterfaceFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Weee\Model\Attribute\Backend\Weee\Tax as BackendWeeTax;
use TddWizard\Fixtures\Exception\IndexFailedException;
use TddWizard\Fixtures\Traits\IsTransactionExceptionTrait;

class AttributeBuilder
{
    use IsTransactionExceptionTrait;

    private const ENTITY_TYPE = 'entity_type';

    public function __construct(
        private readonly EavConfig $eavConfig,
        private readonly EavSetup $eavSetup,
        private readonly AttributeRepositoryInterface $attributeRepository,
        private readonly AttributeOptionInterfaceFactory $attributeOptionFactory,
        private Attribute $attribute,
        private readonly string $attributeCode = '',
        private readonly string $attributeType = '',
        private readonly string|int|null $attributeSetId = null,
        private readonly string|int|null $attributeGroupId = null,
    ) {
    }

    public function __clone(): void
    {
        $this->attribute = clone $this->attribute;
    }

    /**
     * @param array<string, mixed> $attributeData
     */
    public static function aProductAttribute(
        string $attributeCode,
        string $attributeType = 'text',
        array $attributeData = [],
        ?string $attributeSetId = null,
        ?string $attributeGroupId = null,
    ): AttributeBuilder {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Attribute $attribute */
        $attribute = $objectManager->create(type: ProductAttributeInterface::class);
        $attribute->setEntityType(type: ProductAttributeInterface::ENTITY_TYPE_CODE);

        $defaultAttributeData = [
            AttributeInterface::IS_UNIQUE => 0,
            AttributeInterface::IS_REQUIRED => 0,
            EavAttributeInterface::IS_SEARCHABLE => 0,
            EavAttributeInterface::IS_VISIBLE_IN_ADVANCED_SEARCH => 0,
            EavAttributeInterface::IS_COMPARABLE => 0,
            EavAttributeInterface::IS_FILTERABLE => 0,
            EavAttributeInterface::IS_FILTERABLE_IN_SEARCH => 0,
            EavAttributeInterface::IS_USED_FOR_PROMO_RULES => 0,
            EavAttributeInterface::IS_HTML_ALLOWED_ON_FRONT => 0,
            EavAttributeInterface::IS_VISIBLE_ON_FRONT => 1,
            EavAttributeInterface::USED_IN_PRODUCT_LISTING => 0,
            EavAttributeInterface::USED_FOR_SORT_BY => 0,
            EavAttributeInterface::IS_VISIBLE => 1,
            EavAttributeInterface::IS_USED_IN_GRID => 0,
            EavAttributeInterface::IS_VISIBLE_IN_GRID => 0,
            EavAttributeInterface::IS_FILTERABLE_IN_GRID => 0,
            EavAttributeInterface::IS_WYSIWYG_ENABLED => 0,
            AttributeResourceModel::KEY_IS_GLOBAL => 0,
        ];
        $attributeData = array_intersect_key($attributeData, $defaultAttributeData);

        $attribute->addData(
            array_merge(
                $defaultAttributeData,
                $attributeData,
            ),
        );

        return new static(
            eavConfig: $objectManager->create(type: EavConfig::class),
            eavSetup: $objectManager->create(type: EavSetup::class),
            attributeRepository: $objectManager->create(type: AttributeRepositoryInterface::class),
            attributeOptionFactory: $objectManager->create(type: AttributeOptionInterfaceFactory::class),
            attribute: $attribute,
            attributeCode: $attributeCode,
            attributeType: $attributeType,
            attributeSetId: $attributeSetId,
            attributeGroupId: $attributeGroupId,
        );
    }

    /**
     * @param array<string, mixed> $attributeData
     */
    public static function aCategoryAttribute(
        string $attributeCode,
        string $attributeType = 'text',
        array $attributeData = [],
    ): AttributeBuilder {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Attribute $attribute */
        $attribute = $objectManager->create(CategoryAttributeInterface::class);
        $attribute->setEntityType(type: CategoryAttributeInterface::ENTITY_TYPE_CODE);

        $defaultAttributeData = [
            AttributeInterface::IS_UNIQUE => 0,
            AttributeInterface::IS_REQUIRED => 0,
            EavAttributeInterface::IS_SEARCHABLE => 0,
            EavAttributeInterface::IS_VISIBLE_IN_ADVANCED_SEARCH => 0,
            EavAttributeInterface::IS_COMPARABLE => 0,
            EavAttributeInterface::IS_FILTERABLE => 0,
            EavAttributeInterface::IS_FILTERABLE_IN_SEARCH => 0,
            EavAttributeInterface::IS_USED_FOR_PROMO_RULES => 0,
            EavAttributeInterface::IS_HTML_ALLOWED_ON_FRONT => 0,
            EavAttributeInterface::IS_VISIBLE_ON_FRONT => 1,
            EavAttributeInterface::USED_IN_PRODUCT_LISTING => 0,
            EavAttributeInterface::USED_FOR_SORT_BY => 0,
            EavAttributeInterface::IS_VISIBLE => 1,
            EavAttributeInterface::IS_USED_IN_GRID => 0,
            EavAttributeInterface::IS_VISIBLE_IN_GRID => 0,
            EavAttributeInterface::IS_FILTERABLE_IN_GRID => 0,
            EavAttributeInterface::IS_WYSIWYG_ENABLED => 0,
            AttributeResourceModel::KEY_IS_GLOBAL => 0,
        ];
        $attributeData = array_intersect_key($attributeData, $defaultAttributeData);

        $attribute->addData(
            array_merge(
                $defaultAttributeData,
                $attributeData,
            ),
        );

        return new static(
            eavConfig: $objectManager->create(type: EavConfig::class),
            eavSetup: $objectManager->create(type: EavSetup::class),
            attributeRepository: $objectManager->create(type: AttributeRepositoryInterface::class),
            attributeOptionFactory: $objectManager->create(type: AttributeOptionInterfaceFactory::class),
            attribute: $attribute,
            attributeCode: $attributeCode,
            attributeType: $attributeType,
            attributeSetId: null,
            attributeGroupId: null,
        );
    }

    public function withLabel(string $label): AttributeBuilder
    {
        $builder = clone $this;
        $builder->attribute->setDefaultFrontendLabel(defaultFrontendLabel: $label);

        return $builder;
    }

    /**
     * @param array<int, string> $labels
     */
    public function withLabels(array $labels): AttributeBuilder
    {
        $builder = clone $this;

        $objectManager = Bootstrap::getObjectManager();
        $labelFactory = $objectManager->get(type: AttributeFrontendLabelInterfaceFactory::class);
        $labelsToSave = [];
        foreach ($labels as $storeId => $label) {
            /** @var AttributeFrontendLabelInterface $labelToSave */
            $labelToSave = $labelFactory->create();
            $labelToSave->setStoreId(storeId: (int)$storeId);
            $labelToSave->setLabel(label: (string)$label);
            $labelsToSave[] = $labelToSave;
        }
        if ($labelsToSave) {
            $builder->attribute->setFrontendLabels(frontendLabels: $labelsToSave);
        }

        return $builder;
    }

    /**
     * @param array<string, mixed> $attributeData
     */
    public function withAttributeData(array $attributeData): AttributeBuilder
    {
        $builder = clone $this;
        $data = $builder->attribute->getData();
        $builder->attribute->addData(
            array_merge($data, $attributeData),
        );

        return $builder;
    }

    public function withAttributeSet(string|int $attributeSet): AttributeBuilder
    {
        $builder = clone $this;
        $builder->attribute->attributeSetId = $attributeSet;

        return $builder;
    }

    public function withAttributeGroup(string|int $attributeGroup): AttributeBuilder
    {
        $builder = clone $this;
        $builder->attribute->attributeGroup = $attributeGroup;

        return $builder;
    }

    /**
     * @param array<string, string> $attributeOptionValues
     */
    public function withOptions(array $attributeOptionValues): AttributeBuilder
    {
        $builder = clone $this;

        $options = [];
        foreach ($attributeOptionValues as $value => $label) {
            /** @var AttributeOptionInterface $option */
            $option = $this->attributeOptionFactory->create();
            $option->setValue(value: (string)$value);
            $option->setLabel(label: (string)$label);
            $options[] = $option;
        }
        $builder->attribute = $builder->attribute->setOptions(options: $options);

        return $builder;
    }

    public function withEntityType(string $entityType): AttributeBuilder
    {
        $builder = clone $this;
        $builder->attribute = $builder->attribute->setData(key: self::ENTITY_TYPE, value: $entityType);

        return $builder;
    }

    /**
     * @throws IndexFailedException
     * @throws \Exception
     */
    public function build(): AttributeInterface
    {
        try {
            $attribute = $this->saveAttribute(
                builder: $this->createAttribute(),
            );
            $attribute->setOrigData(data: $attribute->getData());
            if ($attribute instanceof ProductAttributeInterface) {
                $this->eavSetup->addAttributeToGroup(
                    entityType: ProductAttributeInterface::ENTITY_TYPE_CODE,
                    setId: $builder->attribute->attributeSetId ?? 'Default',
                    groupId: $builder->attribute->attributeGroupId ?? 'General',
                    attributeId: $attribute->getId(),
                );
                if ($attribute->getOptions() && ($attribute->getSourceModel() !== Boolean::class)) {
                    $objectManager = Bootstrap::getObjectManager();
                    $optionManagement = $objectManager->create(type: OptionManagement::class);
                    foreach ($attribute->getOptions() as $option) {
                        $optionManagement->add(
                            attributeCode: $attribute->getAttributeCode(),
                            option: $option,
                        );
                    }
                }
            }

            return $attribute;
        } catch (\Exception $exception) {
            if (
                self::isTransactionException(exception: $exception)
                || self::isTransactionException(exception: $exception->getPrevious())
            ) {
                throw IndexFailedException::becauseInitiallyTriggeredInTransaction(previous: $exception);
            }
            throw $exception;
        }
    }

    /**
     * @throws LocalizedException
     * @throws StateException
     * @throws NoSuchEntityException
     * @throws IndexFailedException
     * @throws AlreadyExistsException
     */
    private function createAttribute(): AttributeBuilder
    {
        $builder = clone $this;

        $entityTypeId = $this->eavSetup->getEntityTypeId(
            entityTypeId: $builder->attribute->getData(key: self::ENTITY_TYPE),
        );
        $builder->attribute->setEntityTypeId(id: $entityTypeId);
        $builder->attribute->setAttributeCode(data: $builder->attributeCode);
        if (null === $builder->attribute->getIsUserDefined()) {
            $builder->attribute->setIsUserDefined(isUserDefined: 1);
        }
        switch ($builder->attributeType) {
            case ('text'):
                $builder->attribute->setFrontendInput(frontendInput: 'text');
                $builder->attribute->setBackendType(data: 'varchar');
                break;
            case ('textarea'):
                $builder->attribute->setFrontendInput(frontendInput: 'textarea');
                $builder->attribute->setBackendType(data: 'text');
                break;
            case ('date'):
                $builder->attribute->setFrontendInput(frontendInput: 'date');
                $builder->attribute->setBackendType(data: 'datetime');
                break;
            case ('configurable'):
                $builder->attribute->setData(key: AttributeResourceModel::KEY_IS_GLOBAL, value: 1);
                $builder->attribute->setFrontendInput(frontendInput: 'select');
                $builder->attribute->setBackendType(data: 'int');
                break;
            case ('enum'):
                $builder->attribute->setFrontendInput(frontendInput: 'select');
                $builder->attribute->setBackendType(data: 'int');
                break;
            case ('select'):
                $builder->attribute->setFrontendInput(frontendInput: 'select');
                $builder->attribute->setBackendType(data: 'varchar');
                break;
            case ('multiselect'):
            case ('multi-select'):
            case ('multi_select'):
            case ('multiSelect'):
                $builder->attribute->setFrontendInput(frontendInput: 'multiselect');
                $builder->attribute->setBackendType(data: 'text');
                break;
            case ('boolean'):
            case ('bool'):
                $builder->attribute->setFrontendInput(frontendInput: 'boolean');
                $builder->attribute->setBackendType(data: 'int');
                break;
            case ('yes_no'):
            case ('yes-no'):
            case ('yesNo'):
            case ('yesno'):
                $builder->attribute->setFrontendInput(frontendInput: 'boolean');
                $builder->attribute->setBackendType(data: 'int');
                $builder->attribute->setSourceModel(sourceModel: Boolean::class);
                break;
            case ('price'):
                $builder->attribute->setFrontendInput(frontendInput: 'price');
                $builder->attribute->setBackendType(data: 'decimal');
                break;
            case ('image'):
                $builder->attribute->setFrontendInput(frontendInput: 'media_image');
                $builder->attribute->setBackendType(data: 'varchar');
                break;
            case ('weee'):
                $builder->attribute->setFrontendInput(frontendInput: 'weee');
                $builder->attribute->setBackendType(data: 'static');
                $builder->attribute->setBackendModel(data: BackendWeeTax::class);
                break;
            default:
                // no type provided, values can be set via 'withAttributeData'
                // where the required combination is not listed above
                break;
        }

        return $builder;
    }

    /**
     * @param AttributeBuilder $builder
     *
     * @return AttributeInterface
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    private function saveAttribute(AttributeBuilder $builder): AttributeInterface
    {
        $attribute = $this->eavConfig->getAttribute(
            entityType: $builder->attribute->getData(key: self::ENTITY_TYPE),
            code: $builder->attribute->getAttributeCode(),
        );
        if ($attribute?->getId()) {
            throw new AlreadyExistsException(
                phrase: __('Attribute with code %1 already exists', $builder->attribute->getAttributeCode()),
            );
        }
        try {
            $attributeCode = $builder->attributeRepository->save(attribute: $builder->attribute);

            return $builder->attributeRepository->get(
                entityTypeCode: ProductAttributeInterface::ENTITY_TYPE_CODE,
                attributeCode: $attributeCode,
            );
        } catch (\Exception $exception) {
            if (
                self::isTransactionException(exception: $exception)
                || self::isTransactionException(exception: $exception->getPrevious())
            ) {
                throw IndexFailedException::becauseInitiallyTriggeredInTransaction(previous: $exception);
            }
            throw $exception;
        }
    }
}
