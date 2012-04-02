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
require_once 'src/Nocarrier/HalResource.php';

use \Nocarrier\Hal;
use \Nocarrier\HalResource;

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
        $h = new Hal('http://example.com/');
        $this->assertEquals('{"_links":{"self":{"href":"http:\/\/example.com\/"}}}', $h->asJson());
    }

    public function testHalResponseReturnsMinimalValidXml()
    {
        $h = new Hal('http://example.com/');
        $this->assertEquals("<?xml version=\"1.0\"?>\n<resource href=\"http://example.com/\"/>\n", $h->asXml());
    }

    public function testAddLinkJsonResponse()
    {
        $h = new Hal('http://example.com/');
        $h->addLink('test', '/test/1', 'My Test');

        $result = json_decode($h->asJson());
        $this->assertInstanceOf('StdClass', $result->_links->test);
        $this->assertEquals('/test/1', $result->_links->test->href);
        $this->assertEquals('My Test', $result->_links->test->title);
    }

    public function testAddLinkXmlResponse()
    {
        $h = new Hal('http://example.com/');
        $h->addLink('test', '/test/1', 'My Test');

        $result = new \SimpleXmlElement($h->asXml());
        $data = $result->link->attributes();
        $this->assertEquals('test', $data['rel']);
        $this->assertEquals('/test/1', $data['href']);
        $this->assertEquals('My Test', $data['title']);
    }

    public function testResourceJsonResponse()
    {
        $h = new Hal('http://example.com/');
        $r = new HalResource('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $h->addResource('resource', $r);

        $resource = json_decode($h->asJson());
        $this->assertInstanceOf('StdClass', $resource->_embedded);
        $this->assertInternalType('array', $resource->_embedded->resource);
        $this->assertEquals($resource->_embedded->resource[0]->_links->self->href, '/resource/1');
        $this->assertEquals($resource->_embedded->resource[0]->field1, 'value1');
        $this->assertEquals($resource->_embedded->resource[0]->field2, 'value2');
    }

    public function testResourceXmlResponse()
    {
        $h = new Hal('http://example.com/');
        $r = new HalResource('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $h->addResource('resource', $r);

        $result = new \SimpleXmlElement($h->asXml());
        $this->assertEquals('/resource/1', $result->resource->attributes()->href);
        $this->assertEquals('value1', $result->resource->field1);
        $this->assertEquals('value2', $result->resource->field2);
    }
}
