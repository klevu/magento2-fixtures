<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Traits\InstantiationTests;

use Magento\Framework\ObjectManagerInterface;

/**
 * @property ObjectManagerInterface $objectManager
 */
trait TestObjectInstantiationTrait
{
    private ?string $implementationFqcn = null;
    private ?string $interfaceFqcn = null;
    private ?string $implementationForVirtualType = null;
    private ?bool $expectPlugins = false;
    private ?array $constructorArgumentDefaults = null;

    /**
     * @group objectInstantiation
     */
    public function testFqcnResolvesToExpectedImplementation(): object
    {
        try {
            $testObject = $this->instantiateTestObject(
                arguments: $this->constructorArgumentDefaults,
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

        $expectedFqcns = $this->getExpectedFqcns();
        $this->assertContains(
            needle: $testObject::class,
            haystack: $expectedFqcns,
            message: implode(separator: ', ', array: $expectedFqcns),
        );

        return $testObject;
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @throws \LogicException
     */
    private function instantiateTestObject(
        ?array $arguments = null,
    ): object {
        if (!$this->implementationFqcn) {
            throw new \LogicException(
                message: 'Cannot instantiate test object: no implementationFqcn defined',
            );
        }
        if (!(($this->objectManager ?? null) instanceof ObjectManagerInterface)) {
            throw new \LogicException(
                message: 'Cannot instantiate test object: objectManager property not defined',
            );
        }
        if (null === $arguments) {
            $arguments = $this->constructorArgumentDefaults;
        }

        return (null === $arguments)
            ? $this->objectManager->get(type: $this->implementationFqcn)
            : $this->objectManager->create(type: $this->implementationFqcn, arguments: $arguments);
    }

    /**
     * @return string[]
     */
    private function getExpectedFqcns(): array
    {
        $expectedFqcns = [
            $this->implementationFqcn,
        ];
        if ($this->implementationForVirtualType) {
            $expectedFqcns[] = $this->implementationForVirtualType;
        }
        if ($this->expectPlugins) {
            $expectedFqcns[] = $this->implementationFqcn . '\Interceptor';
        }

        return $expectedFqcns;
    }
}
