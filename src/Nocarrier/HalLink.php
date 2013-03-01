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
    protected $uri;
    protected $title;
    protected $attributes;

    public function __construct($uri, $title, $attributes)
    {
        // TODO: validate uri
        $this->uri = $uri;
        $this->title = $title;
        $this->attributes = $attributes;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getTitle()
    {
        return $this->title;
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
