<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Core;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;

class ConfigFixture
{
    /**
     * Sets configuration in default scope AND all stores, no matter what was configured previously
     */
    public static function setGlobal(string $path, mixed $value): void
    {
        self::scopeConfig()->setValue(
            path: $path,
            value: $value,
            scopeType: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        );
        foreach (self::storeRepository()->getList() as $store) {
            self::scopeConfig()->setValue(
                path: $path,
                value: $value,
                scopeType: ScopeInterface::SCOPE_STORE,
                scopeCode: $store->getCode(),
            );
        }
    }

    /**
     * Sets configuration in store scope
     *
     * @param string $path
     * @param mixed $value
     * @param string|null $storeCode store code or NULL for current store
     */
    public static function setForStore(string $path, mixed $value, ?string $storeCode = null): void
    {
        self::scopeConfig()->setValue(
            path: $path,
            value: $value,
            scopeType: ScopeInterface::SCOPE_STORE,
            scopeCode: $storeCode,
        );
    }

    private static function scopeConfig(): MutableScopeConfigInterface
    {
        return Bootstrap::getObjectManager()->get(type: MutableScopeConfigInterface::class);
    }

    private static function storeRepository(): StoreRepositoryInterface
    {
        return Bootstrap::getObjectManager()->get(type: StoreRepositoryInterface::class);
    }
}
