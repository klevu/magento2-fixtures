<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Attribute;

use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use TddWizard\Fixtures\Exception\FixturePoolMissingException;

trait AttributeTrait
{
    private ?AttributeFixturePool $attributeFixturePool = null;

    /**
     * @param array<string, mixed> $attributeData
     *
     * @throws FixturePoolMissingException
     * @throws \Exception
     */
    public function createAttribute(array $attributeData = []): void
    {
        if (null === $this->attributeFixturePool) {
            throw new FixturePoolMissingException(
                message: 'attributeFixturePool has not been created in your test setUp method.',
            );
        }
        if (null === ($attributeData['attribute_type'] ?? null)) {
            $attributeData['attribute_type'] = 'text';
        }
        if (!($attributeData['code'] ?? null)) {
            $attributeData['code'] = 'tdd_attribute';
        }

        $attributeBuilder = ($attributeData['entity_type'] ?? null) === CategoryAttributeInterface::ENTITY_TYPE_CODE
            ? AttributeBuilder::aCategoryAttribute(
                attributeCode: $attributeData['code'],
                attributeType: $attributeData['attribute_type'],
                attributeData: $attributeData,
            )
            : AttributeBuilder::aProductAttribute(
                attributeCode: $attributeData['code'],
                attributeType: $attributeData['attribute_type'],
                attributeData: $attributeData,
            );

        if (($attributeData['entity_type'] ?? null) !== CategoryAttributeInterface::ENTITY_TYPE_CODE) {
            if ($attributeData['attribute_set'] ?? null) {
                $attributeBuilder = $attributeBuilder->withAttributeSet(
                    attributeSet: $attributeData['attribute_set'],
                );
            }
            if ($attributeData['attribute_group'] ?? null) {
                $attributeBuilder = $attributeBuilder->withAttributeGroup(
                    attributeGroup: $attributeData['attribute_group'],
                );
            }
        }

        if (!($attributeData['label'] ?? null)) {
            $attributeData['label'] = ucwords(
                string: str_replace(search: '_', replace: ' ', subject: $attributeData['code']),
            );
        }
        $attributeBuilder = $attributeBuilder->withLabel(label: $attributeData['label']);

        if ($attributeData['store_labels'] ?? null) {
            $attributeBuilder = $attributeBuilder->withLabels(labels: $attributeData['store_labels']);
        }

        if ($attributeData['data'] ?? null) {
            $attributeBuilder = $attributeBuilder->withAttributeData(attributeData: $attributeData['data']);
        }

        if (
            !($attributeData['options'] ?? null)
            && (
                in_array(needle: ($attributeData['attribute_type'] ?? null), haystack: ['configurable', 'select', 'multiselect'], strict: true)
                || in_array(needle: ($attributeData['data']['frontend_input'] ?? null), haystack: ['select', 'multiselect'], strict: true)
            )
        ) {
            $attributeData['options'] = [
                '1' => 'Option 1',
                '2' => 'Option 2',
                '3' => 'Option 3',
                '4' => 'Option 4',
                '5' => 'Option 5',
            ];
        }
        if ($attributeData['options'] ?? null) {
            $attributeBuilder = $attributeBuilder->withOptions(attributeOptionValues: $attributeData['options']);
        }

        $attributeBuilder = $attributeBuilder->withEntityType(
            entityType: $attributeData['entity_type'] ?? ProductAttributeInterface::ENTITY_TYPE_CODE,
        );

        $this->attributeFixturePool->add(
            attribute: $attributeBuilder->build(),
            key: $attributeData['key'] ?? 'tdd_attribute',
        );
    }
}
