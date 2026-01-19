<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Traits;

trait GeneratorTrait
{
    /**
     * Helper method to convert an array to a generator.
     * Example usage:
     *  We have a method we wish to mock that returns a Generator
     *  $mockClass->method('execute')->willReturn($this->generate(['mockResponse1', 'mockResponse2']));
     *
     * @param mixed[]|\Iterator $yieldValues
     *
     * @return \Generator
     */
    private function generate(array|\Iterator $yieldValues): \Generator
    {
        foreach ($yieldValues as $key => $value) {
            yield $key => $value;
        }
    }
}
