<?php

namespace Nocarrier;

class JsonHalFactory
{
    /**
     * Decode a application/hal+json document into a Nocarrier\Hal object.
     *
     * @param string $text
     * @param int $depth
     * @static
     * @access public
     * @return \Nocarrier\Hal
     */
    public static function fromJson(Hal $hal, $text, $depth = 0)
    {
        list($uri, $links, $embedded, $data) = self::prepareJsonData($text);
        $hal->setUri($uri)->setData($data);
        self::addJsonLinkData($hal, $links);

        if ($depth > 0) {
            self::setEmbeddedResources($hal, $embedded, $depth);
        }
        $hal->setShouldStripAttributes(false);
        return $hal;
    }

    /**
     * @param string $text
     */
    private static function prepareJsonData($text)
    {
        $data = json_decode($text, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \RuntimeException('The $text parameter must be valid JSON');
        }
        $uri = isset($data['_links']['self']['href']) ? $data['_links']['self']['href'] : "";
        unset ($data['_links']['self']);

        $links = isset($data['_links']) ? $data['_links'] : array();
        unset ($data['_links']);

        $embedded = isset($data['_embedded']) ? $data['_embedded'] : array();
        unset ($data['_embedded']);

        return array($uri, $links, $embedded, $data);
    }

    /**
     * @param Hal $hal
     * @param array $links
     */
    private static function addJsonLinkData($hal, $links)
    {
        foreach ($links as $rel => $links) {
            if (!isset($links[0]) or !is_array($links[0])) {
                $links = array($links);
            }

            foreach ($links as $link) {
                $href = $link['href'];
                unset($link['href']);
                $hal->addLink($rel, $href, $link);
            }
        }
    }

    /**
     * @param Hal $hal
     * @param array $embedded
     * @param integer $depth
     */
    private static function setEmbeddedResources(Hal $hal, $embedded, $depth)
    {
        foreach ($embedded as $rel => $embed) {
            $isIndexed = array_values($embed) === $embed;
            $className = get_class($hal);
            if (!$isIndexed) {
                $hal->setResource($rel, self::fromJson(new $className, json_encode($embed), $depth - 1));
            } else {
                foreach ($embed as $resource) {
                    $hal->addResource($rel, self::fromJson(new $className, json_encode($resource), $depth - 1));
                }
            }
        }
    }
}
