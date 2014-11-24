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

require_once 'vendor/autoload.php';

use \Nocarrier\Hal;
use \Nocarrier\JsonHalFactory;

/**
 * HalTest
 *
 * @package Nocarrier
 * @subpackage Tests
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
class HalTest extends \PHPUnit_Framework_TestCase
{
    public function testHalJsonResponseAllowsNoSelfLink()
    {
        $hal = new Hal();
        $this->assertEquals('[]', $hal->asJson());
    }

    public function testHalXmlResponseAllowsNoSelfLink()
    {
        $hal = new Hal();
        $this->assertEquals("<?xml version=\"1.0\"?>\n<resource/>\n", $hal->asXml());
    }

    public function testHalResponseReturnsSelfLinkJson()
    {
        $hal = new Hal('http://example.com/');
        $this->assertEquals('{"_links":{"self":{"href":"http:\/\/example.com\/"}}}', $hal->asJson());
    }

    public function testHalResponseReturnsSelfLinkXml()
    {
        $hal = new Hal('http://example.com/');
        $this->assertEquals("<?xml version=\"1.0\"?>\n<resource href=\"http://example.com/\"/>\n", $hal->asXml());
    }

    public function testAddLinkJsonResponse()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/1');

        $result = json_decode($hal->asJson());
        $this->assertInstanceOf('StdClass', $result->_links->test);
        $this->assertEquals('/test/1', $result->_links->test->href);
    }

    public function testAddLinkRelAsArrayJsonResponse()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/1', array(), true);
        $json =  $hal->asJson();
        $expectedJson = '{"_links":{"self":{"href":"http:\/\/example.com\/"},"test":[{"href":"\/test\/1"}]}}';
        $this->assertEquals($json,$expectedJson);
    }


    public function testAddLinkXmlResponse()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/1');

        $result = new \SimpleXmlElement($hal->asXml());
        $data = $result->link->attributes();
        $this->assertEquals('test', $data['rel']);
        $this->assertEquals('/test/1', $data['href']);
    }

    public function testXmlPrettyPrintResponse()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/1');

        $response = <<<EOD
<?xml version="1.0"?>
<resource href="http://example.com/">
  <link rel="test" href="/test/1"/>
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

    public function testResourceJsonResponseForceAsNoArray()
    {
        $hal = new Hal('http://example.com/');
        $res = new Hal('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $hal->addResource('resource', $res, false);

        $resource = json_decode($hal->asJson());
        $this->assertInstanceOf('StdClass', $resource->_embedded);
        $this->assertInstanceOf('StdClass', $resource->_embedded->resource);
        $this->assertEquals($resource->_embedded->resource->_links->self->href, '/resource/1');
        $this->assertEquals($resource->_embedded->resource->field1, 'value1');
        $this->assertEquals($resource->_embedded->resource->field2, 'value2');
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
        $resource->addLink('customer', '/customer/bob');
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
        $hal->addLink('members', '/member/1');
        $hal->addLink('members', '/member/2');

        $result = json_decode($hal->asJson());
        $this->assertEquals('/member/1', $result->_links->members[0]->href);
        $this->assertEquals('/member/2', $result->_links->members[1]->href);
    }

    public function testAddArrayOfLinksInXml()
    {
        $hal = new Hal('/');
        $hal->addLink('members', '/member/1');
        $hal->addLink('members', '/member/2');
        $result = new \SimpleXmlElement($hal->asXml());
        $this->assertEquals('members', $result->link[0]->attributes()->rel);
        $this->assertEquals('members', $result->link[1]->attributes()->rel);
        $this->assertEquals('/member/1', $result->link[0]->attributes()->href);
        $this->assertEquals('/member/2', $result->link[1]->attributes()->href);
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
        $hal->addLink('test', '/test/{?id}', array(
            'anchor' => '#foo',
            'rev' => 'canonical',
            'hreflang' => 'en',
            'media' => 'screen',
            'type' => 'text/html',
            'templated' => 'true',
            'name' => 'ex',
            'title' => 'My Test'
        ));

        $result = json_decode($hal->asJson());
        $this->assertEquals('#foo', $result->_links->test->anchor);
        $this->assertEquals('canonical', $result->_links->test->rev);
        $this->assertEquals('en', $result->_links->test->hreflang);
        $this->assertEquals('screen', $result->_links->test->media);
        $this->assertEquals('text/html', $result->_links->test->type);
        $this->assertEquals('true', $result->_links->test->templated);
        $this->assertEquals('ex', $result->_links->test->name);
        $this->assertEquals('My Test', $result->_links->test->title);
    }

    /**
     * @covers \Nocarrier\HalJsonRenderer::linksForJson
     * Provided for code coverage
     */
    public function testLinkAttributesInJsonWithArrayOfLinks()
    {
        $hal = new Hal('http://example.com/');
        $hal->addLink('test', '/test/{?id}', array(
            'anchor' => '#foo1',
            'rev' => 'canonical1',
            'hreflang' => 'en1',
            'media' => 'screen1',
            'type' => 'text/html1',
            'templated' => 'true1',
            'name' => 'ex1',
        ));
        $hal->addLink('test', '/test/{?id}', array(
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
        $hal->addLink('test', '/test/{?id}', array(
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
        $hal->addLink('test', '/test/{?id}', array(
            'anchor' => '#foo1',
            'rev' => 'canonical1',
            'hreflang' => 'en1',
            'media' => 'screen1',
            'type' => 'text/html1',
            'templated' => 'true1',
            'name' => 'ex1',
        ));
        $hal->addLink('test', '/test/{?id}', array(
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

    public function testMinimalHalJsonDecoding()
    {
        $sample = '{"_links":{"self":{"href":"http:\/\/example.com\/"}}}';
        $hal = Hal::fromJson($sample);
        $this->assertEquals($sample, $hal->asJson());
    }

    public function testHalJsonDecodeWithData()
    {
        $sample = '{"_links":{"self":{"href":"http:\/\/example.com\/"}},"key":"value"}';
        $data = Hal::fromJson($sample)->getData();
        $this->assertEquals('value', $data['key']);
    }

    public function testMinimalHalXmlDecoding()
    {
        $sample = "<?xml version=\"1.0\"?>\n<resource href=\"http://example.com/\"/>\n";
        $hal = Hal::fromXml($sample);
        $this->assertEquals($sample, $hal->asXml());
    }

    public function testHalXmlDecodeWithData()
    {
        $sample = "<?xml version=\"1.0\"?>\n<resource href=\"http://example.com/\"><key>value</key></resource>\n";
        $data = Hal::fromXml($sample)->getData();
        $this->assertEquals('value', $data['key']);
    }

    public function testHalJsonDecodeWithLinks()
    {
        $x = new Hal('/test', array('name' => "Ben Longden"));
        $x->addLink('a', '/a');
        $y = Hal::fromJson($x->asJson());

        $this->assertEquals($x->asJson(true), $y->asJson(true));
    }

    public function testHalXmlDecodeWithLinks()
    {
        $x = new Hal('/test', array('name' => "Ben Longden"));
        $x->addLink('a', '/a');
        $y = Hal::fromXml($x->asXml());
        $this->assertEquals($x->asXml(), $y->asXml());
    }

    public function testHalXmlEntitySetWhenValueSpecifiedInData()
    {
        $x = new Hal('/', array('x' => array('value' => 'test')));

        $xml = new \SimpleXMLElement($x->asXml());
        $this->assertEquals('test', (string)$xml->x);
    }

    public function testHalXmlEntitySetWhenValueSpecifiedInMultiData()
    {
        $x = new Hal('/', array('x' => array('key' => 'test', 'value' => 'test')));

        $xml = new \SimpleXMLElement($x->asXml());
        $this->assertEquals('test', (string)$xml->x->key);
        $this->assertEquals('test', (string)$xml->x->value);
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

    public function testAddCurieConformsToSpecification()
    {
        $x = new Hal('/orders');
        $x->addCurie('acme', 'http://docs.acme.com/relations/{rel}');
        $obj = json_decode($x->asJson());
        $this->assertInternalType('array', $obj->_links->curies);
        $this->assertTrue($obj->_links->curies[0]->templated);
        $this->assertEquals('acme', $obj->_links->curies[0]->name);
        $this->assertEquals('http://docs.acme.com/relations/{rel}', $obj->_links->curies[0]->href);
    }

    public function testGetLinkByRelation()
    {
        $x = new Hal('/orders');
        $x->addLink('test', '/test/orders');

        $links = $x->getLink('test');
        $this->assertEquals('/test/orders', $links[0]);
    }

    public function testGetLinkByCurieRelation()
    {
        $x = new Hal('/orders');
        $x->addCurie('acme', 'http://docs.acme.com/relations/{rel}');

        $x->addLink('acme:test', '/widgets');

        $links = $x->getLink('http://docs.acme.com/relations/test');
        $this->assertEquals('/widgets', $links[0]);
    }

    public function testGetLinkReturnsFalseOnFailure()
    {
        $x = new Hal('/orders');
        $this->assertFalse($x->getLink('test'));
    }

    public function testJSONEmptyEmbeddedCollection(){
        $x = new Hal();
        $x->addResource('collection');

        $this->assertEquals('{"_embedded":{"collection":[]}}', $x->asJson());
    }

    public function testXMLEmptyEmbeddedCollection(){
        $x = new Hal();
        $x->addResource('collection');
        $response = <<<EOD
<?xml version="1.0"?>
<resource><resource rel="collection"/></resource>

EOD;
        $this->assertEquals($response, $x->asXml());
    }

    public function testLinksWithAttributesUnserialiseCorrectlyJson()
    {
        $x = new Hal('/');
        $x->addCurie('x:test', 'http://test');

        $this->assertEquals($x->asJson(), Hal::fromJson($x->asJson())->asJson());
    }

    public function testLinksWithAttributesUnserialiseCorrectlyXml()
    {
        $x = new Hal('/');
        $x->addCurie('x:test', 'http://test');

        $this->assertEquals($x->asXml(), Hal::fromXml($x->asXml())->asXml());
    }

    public function testResourceWithNullSelfLinkRendersLinksInJson()
    {
        $x = new Hal(null);
        $x->addLink('testrel', 'http://test');
        $data = json_decode($x->asJson());
        $this->assertEquals('http://test', $data->_links->testrel->href);
    }

    public function testDataCanBeTraversable()
    {
        $it = new \ArrayIterator(array('traversable' => new \ArrayIterator(array('key' => 'value'))));
        $x = new Hal('', $it);

        $response = <<<EOD
<?xml version="1.0"?>
<resource href=""><traversable><key>value</key></traversable></resource>

EOD;
        $this->assertEquals($response, $x->asXml());
    }

    public function testJsonAllowingDisableEncode()
    {
        $hal = new Hal();
        $this->assertSame(array(), $hal->asJson(false, false));
    }

    public function testSetResourceWithArrayOfResources()
    {
        $hal = new Hal('http://example.com/');
        $res1 = new Hal('/resource/1', array('field1' => '1'));
        $res2 = new Hal('/resource/2', array('field1' => '2'));
        $hal->setResource('resource', array($res1, $res2));

        $resource = json_decode($hal->asJson());
        $this->assertInstanceOf('StdClass', $resource->_embedded);
        $this->assertInternalType('array', $resource->_embedded->resource);
        $this->assertEquals($resource->_embedded->resource[0]->field1, '1');
        $this->assertEquals($resource->_embedded->resource[1]->field1, '2');
    }

    public function testSetResourceThrowsIfNotPassedAHalOrArray()
    {
        $this->setExpectedException('\InvalidArgumentException', '$resource should be of type array or Nocarrier\Hal');
        $hal = new Hal('http://example.com/');
        $hal->setResource('resource', new \stdClass());
    }

    public function testSetResourceJsonResponse()
    {
        $hal = new Hal('http://example.com/');
        $res = new Hal('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $hal->setResource('resource', $res);

        $resource = json_decode($hal->asJson());
        $this->assertInstanceOf('StdClass', $resource->_embedded);
        $this->assertInstanceOf('StdClass', $resource->_embedded->resource);
        $this->assertEquals($resource->_embedded->resource->_links->self->href, '/resource/1');
        $this->assertEquals($resource->_embedded->resource->field1, 'value1');
        $this->assertEquals($resource->_embedded->resource->field2, 'value2');
    }

    public function testSetResourceXmlResponse()
    {
        $hal = new Hal('http://example.com/');
        $res = new Hal('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $hal->setResource('resource', $res);

        $result = new \SimpleXmlElement($hal->asXml());
        $this->assertEquals('/resource/1', $result->resource->attributes()->href);
        $this->assertEquals('value1', $result->resource->field1);
        $this->assertEquals('value2', $result->resource->field2);
    }

    public function testHalJsonDecodeWithCollectionOfEmbeddedItems()
    {
        $sample = <<<JSON
        {
            "_links":{
                "self":{"href":"http:\/\/example.com\/"}
            },
            "_embedded":{
                "item":[
                    {
                        "_links":{
                            "self":{"href":"http:\/\/example.com\/"}
                        },
                        "key": "value1"
                    },
                    {
                        "_links":{
                            "self":{"href":"http:\/\/example.com\/"}
                        },
                        "key": "value2"
                    }
                ]
            }
        }
JSON;
        $resources = Hal::fromJson($sample, 1)->getResources();
        $data = $resources['item'][0]->getData();
        $this->assertEquals('value1', $data['key']);
        $data = $resources['item'][1]->getData();
        $this->assertEquals('value2', $data['key']);
    }

    public function testHalJsonDecodeWithSingleEmbeddedItem()
    {
        $sample = <<<JSON
        {
            "_links":{
                "self":{"href":"http:\/\/example.com\/"}
            },
            "_embedded":{
                "item": {
                    "_links":{
                        "self":{"href":"http:\/\/example.com\/"}
                    },
                    "key": "value"
                }
            }
        }
JSON;
        $resources = Hal::fromJson($sample, 1)->getResources();
        $this->assertInstanceOf('Nocarrier\Hal', $resources['item'][0]);
        $data = $resources['item'][0]->getData();
        $this->assertEquals('value', $data['key']);
    }

    public function testGetFirstResourceReturnsSingleItem()
    {
        $hal = new Hal('http://example.com/');
        $res = new Hal('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $hal->setResource('resource', $res);

        $this->assertEquals($res, $hal->getFirstResource("resource"));
    }

    public function testGetFirstResourceReturnsFirstOfMultipleItems()
    {
        $hal = new Hal('http://example.com/');
        $res1 = new Hal('/resource/1', array('field1' => 'value1', 'field2' => 'value2'));
        $res2 = new Hal('/resource/2', array('field2' => 'value2', 'field2' => 'value2'));
        $hal->addResource('resource', $res1);
        $hal->addResource('resource', $res2);

        $this->assertEquals($res1, $hal->getFirstResource("resource"));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testHalFromJsonThrowsExceptionOnInvalidJSON()
    {
        $invalidJson = 'foo';
        Hal::fromJson($invalidJson);
    }

    public function testCanDefineThatAttributesShouldNotBeStripped()
    {
        $hal = new Hal('http://example.com/');
        $hal->setShouldStripAttributes(false);
        $this->assertEquals(false, $hal->getShouldStripAttributes());
    }

    public function testStripAttributeMarkersIsNotCalledWhenRenderingWithStripAttributesSetToFalse()
    {
        $hal = new Hal('http://example.com/', array('@xml:key' => 'value'));
        $hal->setShouldStripAttributes(false);
        $json = json_decode($hal->asJson(true));
        $this->assertEquals('value', $json->{'@xml:key'});
    }

    public function testStripAttributeMarkersIsNotCalledWhenRenderingFromJSON()
    {
        $sample = <<<JSON
{
    "@xml:key": "value",
    "_links": {
        "self": {
            "href": "http://example.com/"
        }
    }
}
JSON;
        $hal = JsonHalFactory::fromJson(new Hal(), $sample);
        $json = json_decode($hal->asJson(true));
        $this->assertEquals('value', $json->{'@xml:key'});
    }
}
