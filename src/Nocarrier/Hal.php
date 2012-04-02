<?php
namespace Nocarrier;

class Hal
{
    protected $uri;

    protected $links = array();

    protected $resources = array();

    public function __construct($uri)
    {
        // TODO: validate uri
        $this->uri = $uri;
    }

    public function addLink($rel, $uri, $title = null)
    {
        // TODO: validate uri
        $this->links[$rel] = array(
            'uri' => $uri,
            'title' => $title
        );
    }

    public function addResource($rel, HalResource $resource)
    {
        $this->resources[$rel][] = $resource;
    }

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

    public function asJson()
    {
        $data = $this->linksForJson($this->uri, $this->links);

        foreach($this->resources as $rel => $resources) {
            $data = array_merge($data, $this->resourcesForJson($rel, $resources));
        }

        $options = 0;
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            $options = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        }
        return json_encode($data, $options);
    }

    protected function linksForXml($doc, $links)
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

    protected function resourcesForXml($doc, $rel, $resources)
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

    public function asXml()
    {
        $doc = new \SimpleXMLElement('<resource></resource>');
        $doc->addAttribute('href', $this->uri);
        $this->linksForXml($doc, $this->links);

        foreach($this->resources as $rel => $resources) {
            $this->resourcesForXml($doc, $rel, $resources);
        }

        $dom = dom_import_simplexml($doc);
        $dom->ownerDocument->preserveWhiteSpace = false;
        $dom->ownerDocument->formatOutput = true;
        return $dom->ownerDocument->saveXML();
    }
}

