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

class HalLink
{
    /**
     * The URI represented by this HalLink
     *
     * @value string
     */
    protected $uri;

    /**
     * Any attributes on this link
     * array(
     *  'templated' => 0,
     *  'type' => 'application/hal+json',
     *  'deprecation' => 1,
     *  'name' => 'latest',
     *  'profile' => 'http://.../profile/order',
     *  'title' => 'The latest order',
     *  'hreflang' => 'en'
     * )
     */
    protected $attributes;

    /**
     * The HalLink object. Supported attributes in Hal (specification section 5)
     */
    public function __construct($uri, $attributes)
    {
        $this->uri = $uri;
        $this->attributes = $attributes;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getTitle()
    {
        return isset($this->attributes['title']) ? $this->attributes['title'] : null;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function __toString()
    {
        return $this->uri;
    }
}
