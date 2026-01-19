<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Traits\InstantiationTests;

use Magento\Framework\ObjectManagerInterface;

/**
 * @property ObjectManagerInterface $objectManager
 * @property bool $expectPlugins
 * @property string $implementationFqcn
 * @property string $interfaceFqcn
 * @property array $constructorArgumentDefaults
 */
trait TestImplementsInterfaceTrait
{
    /**
     * @group objectInstantiation
     */
    public function testImplementsExpectedInterface(): void
    {
        // @see TestInterfacePreferenceTrait
        if (method_exists(object_or_class: $this, method: 'instantiateTestObjectFromInterface')) {
            try {
                $this->assertInstanceOf(
                    expected: $this->interfaceFqcn,
                    actual: $this->instantiateTestObjectFromInterface(arguments: $this->constructorArgumentDefaults),
                );
            } catch (\Exception $exception) {
                $this->fail(
                    message: sprintf(
                        'Cannot instantiate test object from interface "%s": %s',
                        $this->interfaceFqcn,
                        $exception->getMessage(),
                    ),
                );
            }
        }

        try {
            $this->assertInstanceOf(
                expected: $this->interfaceFqcn,
                actual: $this->instantiateTestObject(arguments: $this->constructorArgumentDefaults),
            );
        } catch (\Exception $exception) {
            $this->fail(
                message: sprintf(
                    'Cannot instantiate test object from FQCN "%s": %s',
                    $this->implementationFqcn,
                    $exception->getMessage(),
                ),
            );
        }
    }
}
