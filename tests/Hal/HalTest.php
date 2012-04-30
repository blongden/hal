<?php
/**
 * This file is part of the Hal library
 *
 * (c) Ben Longden <ben@nocarrier.co.uk
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Nocarrier
 */

namespace Nocarrier\Tests;

require_once 'src/Nocarrier/Hal.php';
require_once 'src/Nocarrier/HalRenderer.php';
require_once 'src/Nocarrier/HalXmlRenderer.php';
require_once 'src/Nocarrier/HalJsonRenderer.php';

use \Nocarrier\Hal;

/**
 * HalTest
 * 
 * @package Nocarrier
 * @subpackage Tests
 * @author Ben Longden <ben@nocarrier.co.uk> 
 */
class HalTest extends \PHPUnit_Framework_TestCase
{
    public function testHalResponseReturnsMinimalValidJson()
    {
        $hal = new Hal('http://example.com/');
        $this->assertEquals('{"_links":{"self":{"href":"http:\/\/example.com\/"}}}', $hal->asJson());
    }

    public function testHalResponseReturnsMinimalValidXml()
    {
        $hal = new Hal('http://example.com/');
        $this->assertEquals("<?xml version=\"1.0\"?>\n<resource href=\"http://example.com/\"/>\n", $hal->asXml());
    }

    public function testAddLinkJsonResponse()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/1', 'My Test');

        $result = json_decode($hal->asJson());
        $this->assertInstanceOf('StdClass', $result->_links->test);
        $this->assertEquals('/test/1', $result->_links->test->href);
        $this->assertEquals('My Test', $result->_links->test->title);
    }

    public function testAddLinkXmlResponse()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/1', 'My Test');

        $result = new \SimpleXmlElement($hal->asXml());
        $data = $result->link->attributes();
        $this->assertEquals('test', $data['rel']);
        $this->assertEquals('/test/1', $data['href']);
        $this->assertEquals('My Test', $data['title']);
    }

    public function testResourceJsonResponse()
    {
        $hal = new Hal('http://example.com/');
        $res = new Hal('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $hal->addResource('resource', $res);

        $resource = json_decode($hal->asJson());
        $this->assertInstanceOf('StdClass', $resource->_embedded);
        $this->assertInternalType('array', $resource->_embedded->resource);
        $this->assertEquals($resource->_embedded->resource[0]->_links->self->href, '/resource/1');
        $this->assertEquals($resource->_embedded->resource[0]->field1, 'value1');
        $this->assertEquals($resource->_embedded->resource[0]->field2, 'value2');
    }

    public function testResourceXmlResponse()
    {
        $hal = new Hal('http://example.com/');
        $res = new Hal('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $hal->addResource('resource', $res);

        $result = new \SimpleXmlElement($hal->asXml());
        $this->assertEquals('/resource/1', $result->resource->attributes()->href);
        $this->assertEquals('value1', $result->resource->field1);
        $this->assertEquals('value2', $result->resource->field2);
    }

    public function testEmbeddedResourceInResourceJsonResponse()
    {
        $hal = new Hal('http://example.com/');
        $res = new Hal('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $res->addResource(
            'item', 
            new Hal(
                '/resource/1/item/1',
                array(
                    'itemField1' => 'itemValue1'
                )
            )
        );

        $hal->addResource('resource', $res);
        $result = json_decode($hal->asJson());
        $this->assertInternalType('array', $result->_embedded->resource[0]->_embedded->item);
        $this->assertEquals('/resource/1/item/1', $result->_embedded->resource[0]->_embedded->item[0]->_links->self->href);
        $this->assertEquals('itemValue1', $result->_embedded->resource[0]->_embedded->item[0]->itemField1);
    }

    public function testEmbeddedResourceInResourceXmlResponse()
    {
        $hal = new Hal('http://example.com/');
        $res = new Hal('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $res->addResource(
            'item', 
            new Hal(
                '/resource/1/item/1',
                array(
                    'items' => array(
                        array(
                            'itemField1' => 'itemValue1'
                        )
                    )
                )
            )
        );

        $hal->addResource('resource', $res);
        $result = new \SimpleXmlElement($hal->asXml());
        $this->assertEquals('item', $result->resource->resource->attributes()->rel);
        $this->assertEquals('/resource/1/item/1', $result->resource->resource->attributes()->href);
        $this->assertEquals('itemValue1', $result->resource->resource->items[0]->itemField1);
    }

    public function testResourceWithListRendersCorrectlyInXmlResponse()
    {
        $hal = new Hal('/orders');
        $hal->addLink('next', '/orders?page=2');
        $hal->addLink('search', '/orders?id={order_id}');

        $resource = new Hal(
            '/orders/123',
            array(
                'tests' => array(
                    array(
                        'total' => 30.00,
                        'currency' => 'USD'
                    ),
                    array(
                        'total' => 40.00,
                        'currency' => 'GBP'
                    )
                )
            )
        );
        $resource->addLink('customer', '/customer/bob', 'Bob Jones <bob@jones.com>');
        $hal->addResource('order', $resource);
        $result = new \SimpleXmlElement($hal->asXml());
        $this->assertEquals(30, (string)$result->resource->tests[0]->total);
        $this->assertEquals('USD', (string)$result->resource->tests[0]->currency);
        $this->assertEquals(40, (string)$result->resource->tests[1]->total);
        $this->assertEquals('GBP', (string)$result->resource->tests[1]->currency);
    }

    public function testAddingDataToRootResource()
    {
        $hal = new Hal(
            '/root',
            array(
                'firstname' => 'Ben',
                'surname' => 'Longden'
            )
        );

        $result = json_decode($hal->asJson(true));
        $this->assertEquals('Ben', $result->firstname);
        $this->assertEquals('Longden', $result->surname);
    }

    public function testAddArrayOfLinksInJson()
    {
        $hal = new Hal('/');
        $hal->addLink('members', '/member/1', 'Member 1');
        $hal->addLink('members', '/member/2', 'Member 2');

        $result = json_decode($hal->asJson());
        $this->assertEquals('/member/1', $result->_links->members[0]->href);
        $this->assertEquals('/member/2', $result->_links->members[1]->href);
        $this->assertEquals('Member 1', $result->_links->members[0]->title);
        $this->assertEquals('Member 2', $result->_links->members[1]->title);
    }

    public function testAddArrayOfLinksInXml()
    {
        $hal = new Hal('/');
        $hal->addLink('members', '/member/1', 'Member 1');
        $hal->addLink('members', '/member/2', 'Member 2');
        $result = new \SimpleXmlElement($hal->asXml());
        $this->assertEquals('members', $result->link[0]->attributes()->rel);
        $this->assertEquals('members', $result->link[1]->attributes()->rel);
        $this->assertEquals('/member/1', $result->link[0]->attributes()->href);
        $this->assertEquals('/member/2', $result->link[1]->attributes()->href);
        $this->assertEquals('Member 1', $result->link[0]->attributes()->title);
        $this->assertEquals('Member 2', $result->link[1]->attributes()->title);
    }

    public function testAttributesInXmlRepresentation()
    {
        $hal = new Hal(
            '/',
            array(
                'error' => array(
                    '@id' => 6,
                    '@xml:lang' => 'en',
                    'message' => 'This is a message'
                )
            )
        );

        $xml = new \SimpleXMLElement($hal->asXml());
        $this->assertEquals(6, (string)$xml->error->attributes()->id);
        $this->assertEquals('en', (string)$xml->error->attributes()->lang);
        $this->assertEquals('This is a message', (string)$xml->error->message);

        $json = json_decode($hal->asJson(true));
        $this->markTestIncomplete();
        $this->assertEquals(6, $json->error->id);
    }
}
