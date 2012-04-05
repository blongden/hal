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
class Hal extends HalResource
{
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
     * @param HalResource $resource
     * @return array
     */
    protected function arrayForJson(HalResource $resource)
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
     * linksForXml
     * Add links in hal+xml format to a SimpleXmlElement object
     *
     * @param SimpleXmlElement $doc
     * @param array $links
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
     *
     * @param array $data
     * @param \SimpleXmlElement $element
     */
    protected function array_to_xml($data, $element) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $element->addChild($key);
                    $this->array_to_xml($value, $subnode);
                } else{
                    $this->array_to_xml($value, $element);
                }
            } else {
                $element->addChild($key, $value);
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

            $this->array_to_xml($resource->data, $element);
        }
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
