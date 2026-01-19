<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Roll back one or more options.
 *
 * @internal Use OptionFixture::rollback() instead.
 */
class OptionFixtureRollback
{
    public function __construct(
        private readonly Registry $registry,
        private readonly AttributeOptionManagementInterface $optionManagement,
    ) {
    }

    public static function create(): OptionFixtureRollback
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            registry: $objectManager->get(type: Registry::class),
            optionManagement: $objectManager->get(type: AttributeOptionManagementInterface::class),
        );
    }

    /**
     * Remove the given option(s).
     *
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function execute(OptionFixture ...$optionFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($optionFixtures as $optionFixture) {
            $this->optionManagement->delete(
                entityType: Product::ENTITY,
                attributeCode: $optionFixture->getAttributeCode(),
                optionId: $optionFixture->getOption()->getId(),
            );
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
