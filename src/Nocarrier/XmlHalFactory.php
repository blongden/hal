<?php

namespace Nocarrier;

class XmlHalFactory
{
    /**
     * Decode a application/hal+xml document into a Nocarrier\Hal object.
     *
     * @param Hal $hal
     * @param $data
     * @param int $depth
     *
     * @throws \RuntimeException
     * @static
     * @access public
     * @return \Nocarrier\Hal
     */
    public static function fromXml(Hal $hal, $data, $depth = 0)
    {
        if (!$data instanceof \SimpleXMLElement) {
            try {
                $data = new \SimpleXMLElement($data);
            } catch (\Exception $e) {
                throw new \RuntimeException('The $data parameter must be valid XML');
            }
        }

        $children = $data->children();
        $links = clone $children->link;
        unset ($children->link);

        $embedded = clone $children->resource;
        unset ($children->resource);

        $hal->setUri((string)$data->attributes()->href);
        $hal->setData((array) $children);
        foreach ($links as $links) {
            if (!is_array($links)) {
                $links = array($links);
            }
            foreach ($links as $link) {
                list($rel, $href, $attributes) = self::extractKnownData($link);
                $hal->addLink($rel, $href, $attributes);
            }
        }

        if ($depth > 0) {
            foreach ($embedded as $embed) {
                list($rel, $href, $attributes) = self::extractKnownData($embed);
                $hal->addResource($rel, self::fromXml($embed, $depth - 1));
            }
        }
        $hal->setShouldStripAttributes(false);
        return $hal;
    }

    private static function extractKnownData($data)
    {
        $attributes = (array)$data->attributes();
        $attributes = $attributes['@attributes'];
        $rel = $attributes['rel'];
        $href = $attributes['href'];
        unset($attributes['rel'], $attributes['href']);

        return array($rel, $href, $attributes);
    }
}
