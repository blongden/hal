Nocarrier\Hal
=============

Nocarrier\Hal is a library for creating documents in the [application/hal+json and application/hal+xml][1] hypermedia formats

```php
```

Nocarrier\Hal requires PHP 5.3 or later.

```php
<?php
$hal = new Hal('/orders');
$hal->addLink('next', '/orders?page=2');
$hal->addLink('search', '/orders?id={order_id}');

$resource = new HalResource(
    '/orders/123',
    array(
        'total' => 30.00,
        'currency' => 'USD',
    )
);
$resource->addLink('customer', '/customer/bob', 'Bob Jones <bob@jones.com>');
$hal->addResource('order', $resource);
echo $hal->asJson();
```

## Installation

Install from github manually, or use the nocarrier/hal project in composer via [packagist][2]

## More Information

## License

Nocarrier\Hal is licensed under the MIT license.

[1]: http://stateless.co/hal_specification.html
[2]: http://packagist.org/packages/nocarrier/hal
