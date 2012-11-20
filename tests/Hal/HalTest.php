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

    public function testXmlPrettyPrintResponse()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/1', 'My Test');

        $response = <<<EOD
<?xml version="1.0"?>
<resource href="http://example.com/">
  <link rel="test" href="/test/1" title="My Test"/>
</resource>

EOD;
        $this->assertEquals($response, $hal->asXml(true));
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
        $this->assertEquals(6, $json->error->id);
        $this->assertEquals('en', $json->error->lang);
        $this->assertEquals('This is a message', $json->error->message);
    }

    /**
     * @covers \Nocarrier\Hal::addLink
     * @covers \Nocarrier\HalJsonRenderer::linksForJson
     */
    public function testLinkAttributesInJson()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/{?id}', 'My Test', array(
            'anchor' => '#foo',
            'rev' => 'canonical',
            'hreflang' => 'en',
            'media' => 'screen',
            'type' => 'text/html',
            'templated' => 'true',
            'name' => 'ex',
        ));

        $result = json_decode($hal->asJson());
        $this->assertEquals('#foo', $result->_links->test->anchor);
        $this->assertEquals('canonical', $result->_links->test->rev);
        $this->assertEquals('en', $result->_links->test->hreflang);
        $this->assertEquals('screen', $result->_links->test->media);
        $this->assertEquals('text/html', $result->_links->test->type);
        $this->assertEquals('true', $result->_links->test->templated);
        $this->assertEquals('ex', $result->_links->test->name);
    }

    /**
     * @covers \Nocarrier\HalJsonRenderer::linksForJson
     * Provided for code coverage
     */
    public function testLinkAttributesInJsonWithArrayOfLinks()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/{?id}', 'My Test', array(
            'anchor' => '#foo1',
            'rev' => 'canonical1',
            'hreflang' => 'en1',
            'media' => 'screen1',
            'type' => 'text/html1',
            'templated' => 'true1',
            'name' => 'ex1',
        ));
        $hal->addLink('test', '/test/{?id}', 'My Test', array(
            'anchor' => '#foo2',
            'rev' => 'canonical2',
            'hreflang' => 'en2',
            'media' => 'screen2',
            'type' => 'text/html2',
            'templated' => 'true2',
            'name' => 'ex2',
        ));

        $result = json_decode($hal->asJson());
        $i = 1;
        foreach ($result->_links->test as $testLink) {
            $this->assertEquals('#foo' . $i, $testLink->anchor);
            $this->assertEquals('canonical' . $i, $testLink->rev);
            $this->assertEquals('en' . $i, $testLink->hreflang);
            $this->assertEquals('screen' . $i, $testLink->media);
            $this->assertEquals('text/html' . $i, $testLink->type);
            $this->assertEquals('true' . $i, $testLink->templated);
            $this->assertEquals('ex' . $i, $testLink->name);
            $i++;
        }
    }

    /**
     * @covers \Nocarrier\Hal::addLink
     * @covers \Nocarrier\HalXmlRenderer::linksForXml
     */
    public function testLinkAttributesInXml()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/{?id}', 'My Test', array(
            'anchor' => '#foo',
            'rev' => 'canonical',
            'hreflang' => 'en',
            'media' => 'screen',
            'type' => 'text/html',
            'templated' => 'true',
            'name' => 'ex',
        ));

        $result = new \SimpleXmlElement($hal->asXml());
        $data = $result->link->attributes();
        $this->assertEquals('#foo', $data['anchor']);
        $this->assertEquals('canonical', $data['rev']);
        $this->assertEquals('en', $data['hreflang']);
        $this->assertEquals('screen', $data['media']);
        $this->assertEquals('text/html', $data['type']);
        $this->assertEquals('true', $data['templated']);
        $this->assertEquals('ex', $data['name']);
    }

    /**
     * @covers \Nocarrier\HalXmlRenderer::linksForXml
     * Provided for code coverage.
     */
    public function testLinkAttributesInXmlWithArrayOfLinks()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/{?id}', 'My Test', array(
            'anchor' => '#foo1',
            'rev' => 'canonical1',
            'hreflang' => 'en1',
            'media' => 'screen1',
            'type' => 'text/html1',
            'templated' => 'true1',
            'name' => 'ex1',
        ));
        $hal->addLink('test', '/test/{?id}', 'My Test', array(
            'anchor' => '#foo2',
            'rev' => 'canonical2',
            'hreflang' => 'en2',
            'media' => 'screen2',
            'type' => 'text/html2',
            'templated' => 'true2',
            'name' => 'ex2',
        ));

        $result = new \SimpleXmlElement($hal->asXml());
        $i = 1;
        foreach ($result->link as $link) {
            $data = $link->attributes();
            $this->assertEquals('#foo' . $i, $data['anchor']);
            $this->assertEquals('canonical' . $i, $data['rev']);
            $this->assertEquals('en' . $i, $data['hreflang']);
            $this->assertEquals('screen' . $i, $data['media']);
            $this->assertEquals('text/html' . $i, $data['type']);
            $this->assertEquals('true' . $i, $data['templated']);
            $this->assertEquals('ex' . $i, $data['name']);
            $i++;
        }
    }

    public function testNumericKeysUseParentAsXmlElementName()
    {
        $hal = new Hal('/', array(
            'foo' => array(
                'bar',
                'baz',
            ),
        ));

        $result = new \SimpleXmlElement($hal->asXml());

        $this->assertEquals('bar', $result->foo[0]);
        $this->assertEquals('baz', $result->foo[1]);

        $json = json_decode($hal->asJson(), true);

        $this->assertEquals(array('bar', 'baz'), $json['foo']);
    }

    public function testBooleanOutput()
    {
        $hal = new Hal('/', array(
            'foo' => true,
            'bar' => false
        ));

        $xml = new \SimpleXMLElement($hal->asXml());
        $this->assertSame('1', (string)$xml->foo);
        $this->assertSame('0', (string)$xml->bar);

        $json = json_decode($hal->asJson());
        $this->assertTrue($json->foo);
        $this->assertFalse($json->bar);
    }
}
