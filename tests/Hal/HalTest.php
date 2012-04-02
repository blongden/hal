<?php
namespace Nocarrier\Tests;

require_once 'src/Nocarrier/Hal.php';
require_once 'src/Nocarrier/HalResource.php';

use \Nocarrier\Hal;
use \Nocarrier\HalResource;

class HalTest extends \PHPUnit_Framework_TestCase
{
    public function testHalResponseReturnsMinimalValidJson()
    {
        $h = new Hal('http://example.com/');
        $this->assertEquals('{"_links":{"self":{"href":"http:\/\/example.com\/"}}}', $h->asJson(false));
    }

    public function testHalResponseReturnsMinimalValidXml()
    {
        $h = new Hal('http://example.com/');
        $this->assertEquals("<?xml version=\"1.0\"?>\n<resource href=\"http://example.com/\"/>\n", $h->asXml(false));
    }
}
