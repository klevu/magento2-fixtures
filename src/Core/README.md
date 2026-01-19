# Core Fixtures

## Config

With the config fixture you can set a configuration value globally, i.e. it will ensure that it is not only set in the
default scope but also in all store scopes:

```
ConfigFixture::setGlobal('general/store_information/name', 'Ye Olde Wizard Shop');
```

It uses `MutableScopeConfigInterface`, so the configuration is not persisted in the database.  
Use `@magentoAppIsolation enabled` in your test to make sure that changes are reverted in subsequent tests.

You can also set configuration values explicitly for stores with `ConfigFixture::setForStore()`
