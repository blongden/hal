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
     * The URI of the current resource
     *
     * @var mixed
     * @access protected
     */
    protected $uri;

    /**
     * Any related links
     *
     * @var mixed
     * @access protected
     */
    protected $links = array();

    /**
     * An array of resources to be embedded in this representation
     *
     * @var mixed
     * @access protected
     */
    protected $resources = array();

    /**
     * Construct a new Hal representation representing the resource found at $uri
     *
     * @param mixed $uri
     * @access public
     * @return void
     */
    public function __construct($uri)
    {
        // TODO: validate uri
        $this->uri = $uri;
    }

    /**
     * Add an associated link to the current resource, identified by $ref and found at $uri. $title is optional.
     *
     * @param mixed $rel
     * @param mixed $uri
     * @param mixed $title
     * @access public
     * @return void
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
     * @param HalResource $resource
     * @access public
     * @return void
     */
    public function addResource($rel, HalResource $resource)
    {
        $this->resources[$rel][] = $resource;
    }

    /**
     * Return an array (compatible with the hal+json format) representing associated links
     *
     * @param mixed $uri
     * @param mixed $links
     * @access protected
     * @return array
     */
    protected function linksForJson($uri, $links)
    {
        $data = array(
            "_links" => array(
                "self" => array(
                    "href" => $uri
                )
            )
        );
        foreach($links as $rel => $link) {
            $data['_links'][$rel] = array('href' => $link['uri']);
            if (!is_null($link['title'])) { 
                $data['_links'][$rel]['title'] = $link['title'];
            }
        }

        return $data;
    }

    /**
     * Return an array (compatible with the hal+json format) representing associated resources
     *
     * @param mixed $rel
     * @param mixed $resources
     * @param array $data
     * @access protected
     * @return array
     */
    protected function resourcesForJson($rel, $resources, $data = array())
    {
        foreach($resources as $resource) {
            $item = array_merge(
                $this->linksForJson($resource->uri, $resource->links),
                $resource->data
            );

            if (!empty($resource->resources)) {
                foreach($resource->resources as $innerRel => $innerRes) {
                    $item = $this->resourcesForJson($innerRel, $innerRes, $item);
                }
            }
            $data['_embedded'][$rel][] = $item;
        }

        return $data;
    }

    /**
     * asJson
     * Return the current object in a application/hal+json format (links and resources)
     *
     * @access public
     * @return string
     */
    public function asJson($pretty=false)
    {
        $data = $this->linksForJson($this->uri, $this->links);

        foreach($this->resources as $rel => $resources) {
            $data = array_merge($data, $this->resourcesForJson($rel, $resources));
        }

        $options = 0;
        if (version_compare(PHP_VERSION, '5.4.0') >= 0 and $pretty) {
            $options = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        }
        return json_encode($data, $options);
    }

    /**
     * linksForXml
     * Add links in hal+xml format to a SimpleXmlElement object
     *
     * @param SimpleXmlElement $doc
     * @param array $links
     * @access protected
     * @return void
     */
    protected function linksForXml(\SimpleXmlElement $doc, array $links)
    {
        foreach($links as $rel => $link) {
            $element = $doc->addChild('link');
            $element->addAttribute('rel', $rel);
            $element->addAttribute('href', $link['uri']);
            if (!is_null($link['title'])) {
                $element->addAttribute('title', $link['title']);
            }
        }
    }

    /**
     * resourcesForXml
     * Add resources in hal+xml format (identified by $rel) to a SimpleXmlElement object
     *
     * @param SimpleXmlElement $doc
     * @param mixed $rel
     * @param array $resources
     * @access protected
     * @return void
     */
    protected function resourcesForXml(\SimpleXmlElement $doc, $rel, array $resources)
    {
        foreach($resources as $resource) {
            $element = $doc->addChild('resource');
            $element->addAttribute('rel', $rel);
            $element->addAttribute('href', $resource->uri);

            $this->linksForXml($element, $resource->links);

            foreach($resource->resources as $innerRel => $innerRes) {
                $this->resourcesForXml($element, $innerRel, $innerRes);
            }

            foreach($resource->data as $key => $val) {
                if (is_array($val)) {
                    foreach($val as $v) {
                        $item = $element->addChild($key);
                        foreach($v as $k => $v) {
                            $item->addChild($k, $v);
                        }
                    }
                } else {
                    $element->addChild($key, $val);
                }
            }
        }
    }

    /**
     * asXml
     * Return the current object in a application/hal+xml format (links and resources)
     *
     * @access public
     * @return void
     */
    public function asXml($pretty=false)
    {
        $doc = new \SimpleXMLElement('<resource></resource>');
        $doc->addAttribute('href', $this->uri);
        $this->linksForXml($doc, $this->links);

        foreach($this->resources as $rel => $resources) {
            $this->resourcesForXml($doc, $rel, $resources);
        }

        $dom = dom_import_simplexml($doc);
        if ($pretty) {
            $dom->ownerDocument->preserveWhiteSpace = false;
            $dom->ownerDocument->formatOutput = true;
        }
        return $dom->ownerDocument->saveXML();
    }
}

