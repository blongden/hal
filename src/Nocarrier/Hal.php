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

namespace Nocarrier;

/**
 * The Hal document class
 *
 * @package Nocarrier
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
class Hal
{
    /**
     * uri
     *
     * @var mixed
     */
    protected $uri;

    /**
     * The data for this resource. An associative array of key value pairs.
     * array(
     *     'price' => 30.00,
     *     'colour' => 'blue'
     * )
     *
     * @var array
     */
    protected $data;

    /**
     * resources
     *
     * @var array
     */
    protected $resources = array();

    /**
     * links
     *
     * @var array
     */
    protected $links = array();

    /**
     * construct a new Hal. Call the parent and store the additional data on the resource.
     *
     * @param mixed $uri
     * @param array $data
     */
    public function __construct($uri, array $data = array())
    {
        $this->uri = $uri;
        $this->data = $data;
    }

    public static function fromJson($text)
    {
        $data = json_decode($text, true);
        $uri = $data['_links']['self']['href'];
        unset ($data['_links']['self']);

        $links = $data['_links'];
        unset ($data['_links']);

        $resources = isset($data['_embedded']) ? $data['_embedded'] : array();
        unset ($data['_embedded']);

        $hal = new Hal($uri, $data);
        foreach ($links as $rel => $link) {
            $hal->addLink($rel, $link['href'], $link['title']);
        }
        return $hal;
    }

    public static function fromXml($text)
    {
        $data = new \SimpleXMLElement($text);
        $children = $data->children();
        $links = clone $children->link;
        unset ($children->link);

        $hal = new Hal($data->attributes()->href, (array)$children);
        foreach ($links as $link) {
            $hal->addLink((string)$link->attributes()->rel, (string)$link->attributes()->href, (string)$link->attributes()->title);
        }

        return $hal;
    }

    /**
     * Add a link to the resource, identified by $rel, located at $uri, with an
     * optional $title
     *
     * @param string $rel
     * @param string $uri
     * @param string $title
     * @param array $attributes Other attributes, as defined by HAL spec and RFC 5988
     */
    public function addLink($rel, $uri, $title = null, array $attributes = array())
    {
        // TODO: validate uri
        $this->links[$rel][] = array(
            'uri' => $uri,
            'title' => $title,
            'attributes' => $attributes,
        );
    }

    /**
     * Add an embedded resource, identified by $rel and represented by $resource.
     *
     * @param mixed $rel
     * @param Hal $resource
     */
    public function addResource($rel, Hal $resource)
    {
        $this->resources[$rel][] = $resource;
    }

    /**
     * Get resource data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get resource links
     *
     * @return array
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Get embedded resources
     *
     * @return array
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Get resource's URI
     *
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * asJson
     * Return the current object in a application/hal+json format (links and resources)
     *
     * @param bool $pretty Enable pretty-printing
     * @return string
     */
    public function asJson($pretty=false)
    {
        $renderer = new HalJsonRenderer();
        return $renderer->render($this, $pretty);
    }


    /**
     * asXml
     * Return the current object in a application/hal+xml format (links and resources)
     *
     * @param bool $pretty Enable pretty-printing
     * @return string
     */
    public function asXml($pretty=false)
    {
        $renderer = new HalXmlRenderer();
        return $renderer->render($this, $pretty);
    }
}
