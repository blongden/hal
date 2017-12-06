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
 * The HalLinkContainer class
 *
 * @package Nocarrier
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
class HalLinkContainer extends \ArrayObject
{
    /**
     * Retrieve a link from the container by rel. Also resolve any curie links
     * if they are set.
     *
     * @param string $rel
     *   The link relation required.
     * @return array|bool
     *   Link if found. Otherwise false.
     */
    public function get($rel)
    {
        if (array_key_exists($rel, $this)) {
            return $this[$rel];
        }

        if (isset($this['curies'])) {
            foreach ($this['curies'] as $link) {
                $prefix = strstr($link->getUri(), '{rel}', true);
                if (strpos($rel, $prefix) === 0) {
                    // looks like it is
                    $shortrel = substr($rel, strlen($prefix));
                    $attrs = $link->getAttributes();
                    $curie = "{$attrs['name']}:$shortrel";
                    if (isset($this[$curie])) {
                        return $this[$curie];
                    }
                }
            }
        }

        return false;
    }
}
