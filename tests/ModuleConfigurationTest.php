<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ModuleConfigurationTest extends TestCase
{
    private const MODULE_NAME = 'TddWizard_Fixtures';

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    public function testModuleIsRegistered(): void
    {
        $registrar = $this->objectManager->create(ComponentRegistrar::class);
        $modulePaths = $registrar->getPaths(ComponentRegistrar::MODULE);

        $this->assertArrayHasKey(self::MODULE_NAME, $modulePaths);
    }

    public function testModuleIsEnabledInTheTestEnvironment(): void
    {
        $moduleList = $this->objectManager->create(ModuleList::class);

        $this->assertTrue($moduleList->has(self::MODULE_NAME));
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}
