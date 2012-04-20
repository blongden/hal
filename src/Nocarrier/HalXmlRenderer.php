<?php
namespace Nocarrier;

class HalXmlRenderer implements HalRenderer
{
    public function render(Hal $resource, $pretty)
    {
        $doc = new \SimpleXMLElement('<resource></resource>');
        $doc->addAttribute('href', $resource->getUri());
        $this->linksForXml($doc, $resource->getLinks());

        foreach($resource->getResources() as $rel => $resources) {
            $this->resourcesForXml($doc, $rel, $resources);
        }

        $dom = dom_import_simplexml($doc);
        if ($pretty) {
            $dom->ownerDocument->preserveWhiteSpace = false;
            $dom->ownerDocument->formatOutput = true;
        }
        return $dom->ownerDocument->saveXML();
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
    protected function array_to_xml($data, $element, $parent=null) {
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
            $element->addAttribute('href', $resource->getUri());

            $this->linksForXml($element, $resource->getLinks());

            foreach($resource->getResources() as $innerRel => $innerRes) {
                $this->resourcesForXml($element, $innerRel, $innerRes);
            }

            $this->array_to_xml($resource->getData(), $element);
        }
    }
}
