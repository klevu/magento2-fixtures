<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Model\Entity\Attribute\Option as AttributeOption;
use Magento\Eav\Model\Entity\Attribute\Source\TableFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Create a source-model option for an attribute.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OptionBuilder
{

    public function __construct(
        private readonly AttributeOptionManagementInterface $optionManagement,
        private AttributeOption $option,
        private readonly AttributeOptionLabelInterface $optionLabel,
        private readonly string $attributeCode,
    ) {
    }

    public function __clone()
    {
        $this->option = clone $this->option;
    }

    /**
     * Create an option.
     *
     * @throws InputException
     * @throws StateException
     */
    public static function anOptionFor(string $attributeCode): OptionBuilder
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var AttributeOptionManagementInterface $optionManagement */
        $optionManagement = $objectManager->create(type: AttributeOptionManagementInterface::class);
        $items = $optionManagement->getItems(entityType: Product::ENTITY, attributeCode: $attributeCode);

        /** @var AttributeOptionLabelInterface $optionLabel */
        $optionLabel = $objectManager->create(type: AttributeOptionLabelInterface::class);
        $label = uniqid(prefix: 'Name ', more_entropy: true);
        $optionLabel->setStoreId(storeId: Store::DEFAULT_STORE_ID);
        $optionLabel->setLabel(label: $label);

        /** @var AttributeOption $option */
        $option = $objectManager->create(type: AttributeOption::class);
        $option->setLabel(label: $label);
        $option->setStoreLabels(storeLabels: [$optionLabel]);
        $option->setSortOrder(sortOrder: count($items) + 1);
        $option->setIsDefault(isDefault: false);

        return new static(
            optionManagement: $optionManagement,
            option: $option,
            optionLabel: $optionLabel,
            attributeCode: $attributeCode,
        );
    }

    public function withLabel(string $label): OptionBuilder
    {
        $builder = clone $this;
        $builder->optionLabel->setLabel(label: $label);
        $builder->option->setStoreLabels(storeLabels: [$builder->optionLabel]);
        $builder->option->setLabel(label: $label);

        return $builder;
    }

    public function withSortOrder(int $sortOrder): OptionBuilder
    {
        $builder = clone $this;
        $builder->option->setSortOrder(sortOrder: $sortOrder);

        return $builder;
    }

    public function withIsDefault(bool $isDefault): OptionBuilder
    {
        $builder = clone $this;
        $builder->option->setIsDefault(isDefault: $isDefault);

        return $builder;
    }

    public function withStoreId(int $storeId): OptionBuilder
    {
        $builder = clone $this;
        $builder->optionLabel->setStoreId(storeId: $storeId);

        return $builder;
    }

    /**
     * Build the option and apply it to the attribute.
     *
     * @throws InputException
     * @throws StateException
     * @throws NoSuchEntityException
     */
    public function build(): AttributeOption
    {
        $builder = clone $this;

        // add the option
        $this->optionManagement->add(
            entityType: Product::ENTITY,
            attributeCode: $builder->attributeCode,
            option: $builder->option,
        );

        $optionId = $this->getOptionId();
        $builder->option->setId($optionId);

        return $builder->option;
    }

    /**
     * @throws NoSuchEntityException
     */
    private function getOptionId(): int
    {
        $objectManager = Bootstrap::getObjectManager();
        // the add option above does not return the option, so we need to retrieve it
        $attributeRepository = $objectManager->get(type: ProductAttributeRepositoryInterface::class);
        $attribute = $attributeRepository->get(attributeCode: $this->attributeCode);
        $attributeValues[$attribute->getAttributeId()] = [];

        // We have to generate a new sourceModel instance each time through to prevent it from
        // referencing its _options cache. No other way to get it to pick up newly-added values.
        $tableFactory = $objectManager->get(type: TableFactory::class);
        $sourceModel = $tableFactory->create();
        $sourceModel->setAttribute($attribute);
        foreach ($sourceModel->getAllOptions() as $option) {
            $attributeValues[$attribute->getAttributeId()][$option['label']] = $option['value'];
        }
        if (isset($attributeValues[$attribute->getAttributeId()][$this->optionLabel->getLabel()])) {
            return (int)$attributeValues[$attribute->getAttributeId()][$this->optionLabel->getLabel()];
        }

        throw new \RuntimeException(message: 'Error building option');
    }
}
