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
following command will download and install the latest version of the Hal library into your project.

```
php composer.phar require nocarrier/hal
```

Alternatively, clone the project and install into your project manually.

## License

Nocarrier\Hal is licensed under the MIT license.

[1]: http://tools.ietf.org/html/draft-kelly-json-hal-05

## Usage

### Creating Hal Resources

A Hal resource can be created with no values set:

```php
$hal = new \Nocarrier\Hal();
```
with a URI for the resource:

```php
$hal = new \Nocarrier\Hal('/orders');
```

and also with an array of data:

```php
$hal = new \Nocarrier\Hal('/orders', ['customerId' => 'CUS1234']);
```

Hal resources can also be created from existing XML or JSON documents:

```php
$hal = \Nocarrier\Hal::fromJson($jsonString);
```

```php
$hal = \Nocarrier\Hal::fromXml($xmlString);
```

```php
$hal = \Nocarrier\Hal::fromXml($simpleXMLElement);
```

The depth of embedded resources parsed with both these methods is controlled by
a second argument, which defaults to 0:

```php
$hal = \Nocarrier\Hal::fromJson($jsonString, 5);
```

### Getting Representations

The Hal resource can be formatted as JSON or XML:

```php
$hal = new \Nocarrier\Hal('/orders', ['customerId' => 'CUS1234']);
$hal->asJson();
```

which with a first argument of `true` for pretty printing:

```php
$hal = new \Nocarrier\Hal('/orders', ['customerId' => 'CUS1234']);
$hal->asJson(true);
```

gives:

```json
{
    "customerId": "CUS1234",
    "_links": {
        "self": {"href": "/orders"}
    }
}
```

and

```php
$hal = new \Nocarrier\Hal('/orders', ['customerId' => 'CUS1234']);
$hal->asXml(true);
```

gives:

```xml
<?xml version="1.0"?>
<resource href="/orders">
    <customerId>CUS1234</customerId>
</resource>
```

### Data

The data can be set through `setData` and read with `getData`:

```php
$hal = new \Nocarrier\Hal('/orders');
$hal->setData(['customerId' => 'CUS1234']);
$hal->getData();
```

Using array keys in the data for the XML representation can be done by
prefixing the key with `@`:

```php
$hal = new \Nocarrier\Hal('/orders');
$hal->setData(['customerId' => ['CUS1234', '@type' => 'legacy']]);
```

gives:

```xml
<?xml version="1.0"?>
<resource href="/orders">
    <customerId value="CUS1234" type="legacy"/>
</resource>
```

The `@` is ignored if JSON is rendered:

```json
{
    "customerId": {
        "value": "CUS1234",
        "type":" legacy"
    },
    "_links": {
        "self": {"href": "/orders"}
    }
}
```

### Links

Links can be added to the resource by providing the rel identifying them
and a URI:

```php
$hal = new \Nocarrier\Hal('/orders', ['customerId' => 'CUS1234']);
$hal->addLink('next', '/orders?page=2');
$hal->addLink('search', '/orders?id={order_id}');
```

gives:

```json
{
    "customerId": "CUS1234",
    "_links": {
        "self": {
            "href": "/orders"
        },
        "next": {
            "href": "/orders?page=2"
        },
        "search": {
            "href": "/orders?id={order_id}"
        }
    }
}
```

If a Hal object has been created from a response returned from elsewhere it
can be helpful to retrieve the links from it.

```php
$json = '{
     "customerId": "CUS1234",
     "_links": {
         "self": {
             "href": "/orders"
         },
         "next": {
             "href": "/orders?page=2"
         },
         "search": {
             "href": "/orders?id={order_id}"
         }
     }
 }';

$hal = \Nocarrier\Hal::fromJson($json);

foreach($hal->getLinks() as $rel => $links) {
    echo $rel."\n";
    foreach($links as $link) {
        echo (string) $link."\n";
    }
}
```

```
next
/orders?page=2
search
/orders?id={order_id}
```
and

```php
$json = '{
     "customerId": "CUS1234",
     "_links": {
         "self": {
             "href": "/orders"
         },
         "next": {
             "href": "/orders?page=2"
         },
         "search": {
             "href": "/orders?id={order_id}"
         }
     }
 }';
$hal = \Nocarrier\Hal::fromJson($json);
foreach($hal->getLink('next') as $link) {
    echo (string) $link."\n";
}
```

outputs:

```
/orders?page=2
```

### Embedded Resources

As well as linking to resources so that the client can fetch them they can be
directly embedded in the resource.

```php
$hal = new \Nocarrier\Hal('/orders', ['customerId' => 'CUS1234']);

$resource = new \Nocarrier\Hal(
    '/orders/123',
    array(
        'total' => 30.00,
        'currency' => 'USD',
    )
);

$resource->addLink('customer', '/customer/bob', array('title' => 'Bob Jones <bob@jones.com>'));
$hal->addResource('order', $resource);
```
outputs:

```json
{
    "customerId": "CUS1234",
    "_links": {
        "self": {
            "href": "/orders"
        }
    },
    "_embedded": {
        "order": [
            {
                "total": 30,
                "currency": "USD",
                "_links": {
                    "self": {
                        "href": "/orders/123"
                    },
                    "customer": {
                        "href": "/customer/bob",
                        "title": "Bob Jones <bob@jones.com>"
                    }
                }
            }
        ]
    }
}
```
