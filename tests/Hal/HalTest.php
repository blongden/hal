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
        $res = new HalResource('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
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
        $res = new HalResource('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $hal->addResource('resource', $res);

        $result = new \SimpleXmlElement($hal->asXml());
        $this->assertEquals('/resource/1', $result->resource->attributes()->href);
        $this->assertEquals('value1', $result->resource->field1);
        $this->assertEquals('value2', $result->resource->field2);
    }
}
