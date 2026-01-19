<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Theme;

use Magento\Framework\View\DesignInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoAppArea frontend
 * @magentoComponentsDir ../../../../vendor/hardman-commerce/magento2-fixtures/tests/Theme/_files/design
 */
class ThemeFixtureTest extends TestCase
{
    public function testSetCurrentFrontendTheme(): void
    {
        ThemeFixture::setCurrentTheme('Magento/blank');
        /** @var DesignInterface $design */
        $design = Bootstrap::getObjectManager()->get(DesignInterface::class);
        $this->assertEquals('Magento/blank', $design->getDesignTheme()->getCode());
    }

    public function testCanUseTestThemeAfterRegistering(): void
    {
        ThemeFixture::registerTestThemes();
        ThemeFixture::setCurrentTheme('Custom/default');
        /** @var DesignInterface $design */
        $design = Bootstrap::getObjectManager()->get(DesignInterface::class);
        $this->assertEquals('Custom/default', $design->getDesignTheme()->getCode());
        $this->assertGreaterThan(0, $design->getDesignTheme()->getId());
        $this->assertEquals('Magento/blank', $design->getDesignTheme()->getParentTheme()->getCode());
    }
}
