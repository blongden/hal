Nocarrier\Hal
=============

[![Build Status](https://secure.travis-ci.org/blongden/hal.png)](http://travis-ci.org/blongden/hal)

This is a library for creating documents in the [application/hal+json and application/hal+xml][1] hypermedia formats

```php
```

It requires PHP 5.3 or later.

```php
<?php
$hal = new Nocarrier\Hal('/orders');
$hal->addLink('next', '/orders?page=2');
$hal->addLink('search', '/orders?id={order_id}');

$resource = new Nocarrier\Hal(
    '/orders/123',
    array(
        'total' => 30.00,
        'currency' => 'USD',
    )
);
$resource->addLink('customer', '/customer/bob', 'Bob Jones <bob@jones.com>');
$hal->addResource('order', $resource);
echo $hal->asJson();
echo $hal->asXml();
```

## Installation

The preferred method of installation is via packagist as this provides the PSR-0 autoloader functionality. The
following composer.json will download and install the latest version of the Hal library into your project.

```json
{
    "require": {
        "nocarrier/hal": "*"
    },
    "minimum-stability": "dev"
}
```

Alternatively, clone the project and install into your project manually.

## License

Nocarrier\Hal is licensed under the MIT license.

[1]: http://stateless.co/hal_specification.html
