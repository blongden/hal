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
