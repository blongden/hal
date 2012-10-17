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
 * HalJsonRenderer
 * 
 * @uses HalRenderer
 * @package Nocarrier
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
class HalJsonRenderer implements HalRenderer
{
    /**
     * render
     *
     * @param Hal $resource
     * @param bool $pretty
     * @return string
     */
    public function render(Hal $resource, $pretty)
    {
        $options = 0;

        if (version_compare(PHP_VERSION, '5.4.0') >= 0 and $pretty) {
            $options = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        }

        return json_encode($this->arrayForJson($resource), $options);
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

        foreach($links as $rel => $links) {
            if (count($links) === 1) {
                $data[$rel] = array('href' => $links[0]['uri']);
                if (!is_null($links[0]['title'])) {
                    $data[$rel]['title'] = $links[0]['title'];
                }
                foreach ($links[0]['attributes'] as $attribute => $value) {
                    $data[$rel][$attribute] = $value;
                }
            } else {
                $data[$rel] = array();
                foreach ($links as $link) {
                    $item = array('href' => $link['uri']);
                    if (!is_null($link['title'])) {
                        $item['title'] = $link['title'];
                    }
                    foreach ($link['attributes'] as $attribute => $value) {
                        $item[$attribute] = $value;
                    }
                    $data[$rel][] = $item;
                }
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

    protected function stripAttributeMarker(array $data)
    {
        foreach ($data as $key => $value) {
            if (substr($key, 0, 5) == '@xml:') {
                $data[substr($key, 5)] = $value;
                unset ($data[$key]);
            } elseif (substr($key, 0, 1) == '@') {
                $data[substr($key, 1)] = $value;
                unset ($data[$key]);
            }

            if (is_array($value)) {
                $data[$key] = $this->stripAttributeMarker($value);
            }
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
        $data = $this->stripAttributeMarker($data);

        $data['_links'] = $this->linksForJson($resource->getUri(), $resource->getLinks());
        foreach($resource->getResources() as $rel => $resources) {
            $data['_embedded'][$rel] = $this->resourcesForJson($resources);
        }

        return $data;
    }

}
