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
     */
    public function addLink($rel, $uri, $title = null)
    {
        // TODO: validate uri
        $this->links[$rel] = array(
            'uri' => $uri,
            'title' => $title
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
     * Return an array (compatible with the hal+json format) representing associated links
     *
     * @param mixed $uri
     * @param array $links
     * @return array
     */
    protected function linksForJson($uri, $links)
    {
        $data = array('self' => array('href' => $uri));

        foreach($links as $rel => $link) {
            $data[$rel] = array('href' => $link['uri']);
            if (!is_null($link['title'])) {
                $data[$rel]['title'] = $link['title'];
            }
        }

        return $data;
    }

    /**
     * Return an array (compatible with the hal+json format) representing associated resources
     *
     * @param mixed $resources
     * @return array
     */
    protected function resourcesForJson($resources)
    {
        $data = array();

        foreach ($resources as $resource) {
            $data[] = $this->arrayForJson($resource);
        }

        return $data;
    }

    /**
     * Return an array (compatible with the hal+json format) representing the
     * complete response
     *
     * @param Hal $resource
     * @return array
     */
    protected function arrayForJson(Hal $resource)
    {
        $data = $resource->getData();
        $data['_links'] = $this->linksForJson($resource->getUri(), $resource->getLinks());

        foreach($resource->getResources() as $rel => $resources) {
            $data['_embedded'][$rel] = $this->resourcesForJson($resources);
        }

        return $data;
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
        $options = 0;

        if (version_compare(PHP_VERSION, '5.4.0') >= 0 and $pretty) {
            $options = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        }

        return json_encode($this->arrayForJson($this), $options);
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
