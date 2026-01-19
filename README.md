# TddWizard Fixture library

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)

---

Forked from [Magento 2 Fixtures by Fabian Schmengler](https://github.com/tddwizard/magento2-fixtures).  
Updated with code from [Klevu Test Fixtures](https://github.com/klevu/module-m2-test-fixtures).

## What is it?

An alternative to the procedural script-based fixtures in Magento 2 integration tests.

It aims to be:

- extensible
- expressive
- easy to use

## Installation

Install it into your Magento 2 project with composer:

    composer require --dev hardman-commerce/magento2-fixtures

## Requirements

- Magento 2.4.4+
- PHP 8.1+

---

## Documentation:

### [Catalog/Attribute](./src/Catalog/Attribute/README.md)

Fixtures and Traits to build catalog attributes, including store scope settings.  
Useful when an attribute is required to create configurable products.

### [Catalog/Category](./src/Catalog/Category/README.md)

Fixtures and Traits to build categories, including store scope settings.

### [Catalog/Product](./src/Catalog/Product/README.md)

Fixtures and Traits to build products, including store scope settings.  
Extended TDDWizard product fixtures to add

* Tier Prices
* Images

The following product types are covered

* Simple
* Virtual
* Downloadable
* Grouped
* Configurable

Todo:

* Bundle
* Gift Card

### [Catalog/Rule](./src/Catalog/Rule/README.md)

Fixtures and Traits to build catalog price rules.

### [Checkout](./src/Checkout/README.md)

Fixtures and Traits to build Cart and perform a customer checkout.

Add the following product types to the cart:

* Simple
* Configurable
* Grouped

Todo:

* Bundle
* Gift Card

### [CMS](./src/Cms/README.md)

Fixtures and Traits to build:

* Pages

Todo:

* Blocks
* Widgets

### [Core](./src/Core/README.md)

Fixtures to create config settings.

### [Customer](./src/Customer/README.md)

Fixtures and Traits to build:

* Customer Addresses
* Customers
* Customer Groups

### [Sales](./src/Sales/README.md)

Fixtures to build:

* Credit memos
* Invoices
* Orders
* Shipments

Todo:

* Create traits

### [Store](./src/Store/README.md)

Fixtures and Traits to build:

* Stores
* Store Groups
* Websites

### [Tax](./src/Tax/README.md)

Fixtures and Traits to build

* Tax Classes
* Tax Rates
* Tax Rules

### Fixture pools

To manage multiple fixtures, **fixture pools** have been introduced for all entities:

Usage demonstrated with the `ProductFixturePool`:

```
protected function setUp()
{
    $this->productFixtures = new ProductFixturePool;
}

protected function tearDown()
{
    $this->productFixtures->rollback();
}

public function testSomethingWithMultipleProducts()
{
    $this->productFixtures->add(ProductBuilder::aSimpleProduct()->build());
    $this->productFixtures->add(ProductBuilder::aSimpleProduct()->build(), 'foo');
    $this->productFixtures->add(ProductBuilder::aSimpleProduct()->build());

    $this->productFixtures->get();      // returns ProductFixture object for last added product
    $this->productFixtures->get('foo'); // returns ProductFixture object for product added with specific key 'foo'
    $this->productFixtures->get(0);     // returns ProductFixture object for first product added without specific key (numeric array index)
}

```

---

## Credits

- [Fabian Schmengler][link-author-fs]
- [Richard Hardman][link-author-rm]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.txt) for more information.

[ico-version]: https://img.shields.io/packagist/v/hardman-commerce/magento2-fixtures.svg?style=flat-square

[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/hardman-commerce/magento2-fixtures

[link-author-fs]: https://github.com/schmengler

[link-author-rm]: https://github.com/hardman-commerce/

[link-contributors]: ../../contributors

