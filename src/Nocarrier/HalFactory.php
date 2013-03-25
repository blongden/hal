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

use Nocarrier\Exception\InvalidHalException;

/**
 * Factory class to unserialise representations of HAL resources
 *
 * @package Nocarrier
 */
class HalFactory
{

    /**
     * Convert a JSON representation of a HAL resource into a Hal instance.
     *
     * @param string $json Valid JSON representation of a HAL resource
     * @return Hal
     * @throws InvalidHalException
     */
    public function fromJson($json)
    {
        $decoded = json_decode($json, true);

        // error if no valid JSON
        if (is_null($decoded)) {
            throw new InvalidHalException('Unable to decode JSON');
        }

        return $this->fromDecodedJson($decoded);
    }

    /**
     * Recursively build a Hal instance from the output of json_decode().
     *
     * @param array $decoded
     * @return Hal
     * @throws InvalidHalException
     */
    public function fromDecodedJson(array $decoded)
    {
        $links = array();
        $uri = null;

        if (isset($decoded['_links'])) {
            if (isset($decoded['_links']['self']['href'])) {
                // extract uri
                $uri = $decoded['_links']['self']['href'];
                unset($decoded['_links']['self']);
            }

            // extract links
            $links = $decoded['_links'];
            unset($decoded['_links']);
        }

        $embedded = array();

        // extract embedded resources
        if (isset($decoded['_embedded'])) {
            $embedded = $decoded['_embedded'];
            unset($decoded['_embedded']);
        }

        // create the basic resource with remaining attributes
        $hal = new Hal($uri, $decoded);

        // add the links
        foreach ($links as $rel => $linkdef) {
            // error if link syntax incorrect
            if (!is_array($linkdef)) {
                throw new InvalidHalException('Link definition for "' . $rel . '" should be an array');
            }

            // treat everything as a collection of links
            if (isset($linkdef['href'])) {
                $linkdef = array($linkdef);
            }

            foreach ($linkdef as $link) {
                // error if no href given
                if (!isset($link['href'])) {
                    throw new InvalidHalException('No "href" field in link definition "' . $rel . '"');
                }

                $href = $link['href'];
                $title = (isset($link['title'])) ? $link['title'] : null;
                unset($link['href'], $link['title']);
                $hal->addLink($rel, $href, $title, $link);
            }
        }

        foreach ($embedded as $rel => $resourcedef) {
            // error if resource syntax incorrect
            if (!is_array($resourcedef)) {
                throw new InvalidHalException('Embedded resource definition for "' . $rel . '" should be an array');
            }

            // treat everything as a collection of resources
            if (isset($resourcedef['links'])) {
                $resourcedef = array($resourcedef);
            }

            foreach ($resourcedef as $resource) {
                $hal->addResource($rel, $this->fromDecodedJson($resource));
            }
        }

        return $hal;
    }

    /**
     * Convert a XML representation of a HAL resource into a Hal instance.
     *
     * @param string $xml Valid XML representation of a HAL resource
     * @return Hal
     * @throws InvalidHalException
     */
    public function fromXml($xml)
    {
        libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);

        if (!$document) {
            throw new InvalidHalException('Unable to decode XML');
        }

        $decoded = json_decode(json_encode($document, JSON_UNESCAPED_UNICODE), true);
        return $this->fromDecodedXml($decoded);
    }

    /**
     * Recursively build a Hal instance from the output of json_decode().
     *
     * @param array $decoded
     * @return Hal
     * @throws InvalidHalException
     */
    public function fromDecodedXml(array $decoded)
    {
        $uri = null;

        if (isset($decoded['@attributes']['href'])) {
            // extract uri
            $uri = $decoded['@attributes']['href'];
            unset($decoded['@attributes']);
        }

        $links = array();

        // extract links
        if (isset($decoded['link'])) {
            $links[] = $decoded['link'];
            unset($decoded['link']);
        }

        $embedded = array();

        // extract embedded resources
        if (isset($decoded['resource'])) {
            $embedded[] = $decoded['resource'];
            unset($decoded['resource']);
        }

        // create the basic resource with remaining attributes
        $hal = new Hal($uri, $decoded);

        // add the links
        foreach ($links as $linkdef) {
            // treat everything as a collection of links
            if (isset($linkdef['@attributes'])) {
                $linkdef = array($linkdef);
            }

            foreach ($linkdef as $link) {
                // error if link syntax incorrect
                if (!is_array($link)) {
                    throw new InvalidHalException('Link should be an array');
                }

                // error if no rel given
                if (!isset($link['@attributes']['rel'])) {
                    throw new InvalidHalException('No "rel" attribute in link');
                }

                $rel = $link['@attributes']['rel'];

                // error if no href given
                if (!isset($link['@attributes']['href'])) {
                    throw new InvalidHalException('No "href" attribute in link "' . $rel . '"');
                }

                $href = $link['@attributes']['href'];

                $title = (isset($link['@attributes']['title'])) ? $link['@attributes']['title'] : null;
                unset($link['@attributes']['rel'], $link['@attributes']['href'], $link['@attributes']['title']);
                $hal->addLink($rel, $href, $title, $link['@attributes']);
            }
        }

        foreach ($embedded as $resourcedef) {
            // treat everything as a collection of resources
            if (isset($resourcedef['@attributes'])) {
                $resourcedef = array($resourcedef);
            }

            foreach ($resourcedef as $resource) {
                // error if resource syntax incorrect
                if (!is_array($resourcedef)) {
                    throw new InvalidHalException('Embedded resource definition should be an array');
                }

                // error if no rel given
                if (!isset($resource['@attributes']['rel'])) {
                    throw new InvalidHalException('No "rel" attribute in link');
                }

                $rel = $resource['@attributes']['rel'];
                unset($resource['@attributes']['rel']);
                $hal->addResource($rel, $this->fromDecodedXml($resource));
            }
        }

        return $hal;
    }
}