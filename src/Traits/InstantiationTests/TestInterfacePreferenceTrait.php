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
trait TestInterfacePreferenceTrait
{
    /**
     * @group objectInstantiation
     */
    public function testInterfacePreferenceResolvesToExpectedImplementation(): void
    {
        try {
            $testObjectFromInterface = $this->instantiateTestObjectFromInterface(
                arguments: $this->constructorArgumentDefaults,
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

        $expectedFqcns = [
            $this->implementationFqcn,
        ];
        if ($this->expectPlugins) {
            $expectedFqcns[] = $this->implementationFqcn . '\Interceptor';
        }

        $this->assertContains(
            needle: $testObjectFromInterface::class,
            haystack: $expectedFqcns,
            message: implode(separator: ', ', array: $expectedFqcns),
        );
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return object
     * @throws \LogicException
     */
    private function instantiateTestObjectFromInterface(
        ?array $arguments = null,
    ): object {
        if (!$this->interfaceFqcn) {
            throw new \LogicException(
                message: 'Cannot instantiate test object: no interfaceFqcn defined',
            );
        }
        if (!$this->implementationFqcn) {
            throw new \LogicException(
                message: 'Cannot run test: no implementationFqcn defined',
            );
        }
        if (!(($this->objectManager ?? null) instanceof ObjectManagerInterface)) {
            throw new \LogicException(
                message: 'Cannot instantiate test object: objectManager property not defined',
            );
        }

        return (null === $arguments)
            ? $this->objectManager->get(type: $this->interfaceFqcn)
            : $this->objectManager->create(type: $this->interfaceFqcn, arguments: $arguments);
    }
}
