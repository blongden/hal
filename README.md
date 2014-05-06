Nocarrier\Hal
=============

[![Build Status](https://secure.travis-ci.org/blongden/hal.png)](http://travis-ci.org/blongden/hal)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/blongden/hal/badges/quality-score.png?s=7979c06ed24c45ae5f6d762ad43dec9375306b11)](https://scrutinizer-ci.com/g/blongden/hal/)

This is a library for creating documents in the [application/hal+json and application/hal+xml][1] hypermedia formats

It requires PHP 5.3 or later.

```php
<?php
require_once 'vendor/autoload.php';

use Nocarrier\Hal;

$hal = new Hal('/orders');
$hal->addLink('next', '/orders?page=2');
$hal->addLink('search', '/orders?id={order_id}');

$resource = new Hal(
    '/orders/123',
    array(
        'total' => 30.00,
        'currency' => 'USD',
    )
);

$resource->addLink('customer', '/customer/bob', array('title' => 'Bob Jones <bob@jones.com>'));
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
        "nocarrier/hal": "0.9.*"
    }
}
```

Alternatively, clone the project and install into your project manually.

## License

Nocarrier\Hal is licensed under the MIT license.

[1]: http://tools.ietf.org/html/draft-kelly-json-hal-05
