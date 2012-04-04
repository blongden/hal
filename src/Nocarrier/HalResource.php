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
 * The Hal resource class
 *
 * @uses Hal
 * @package Nocarrier
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
class HalResource
{
    /**
     * uri
     * 
     * @var mixed
     * @access public
     */
    protected $uri;

    /**
     * The data for this resource. An associative array of key value pairs.
     * array(
     *     'price' => 30.00,
     *     'colour' => 'blue'
     * )
     *
     * @var array
     * @access protected
     */
    protected $data;

    /**
     * resources
     * 
     * @var array
     * @access public
     */
    protected $resources = array();

    /**
     * links
     * 
     * @var array
     * @access public
     */
    protected $links = array();

    /**
     * construct a new HalResource. Call the parent and store the additional data on the resource.
     *
     * @param mixed $uri
     * @param array $data
     * @access public
     * @return void
     */
    public function __construct($uri, array $data = array())
    {
        $this->uri = $uri;
        $this->data = $data;
    }
    
    public function addLink($rel, $uri, $title = null)
    {
        // TODO: validate uri
        $this->links[$rel] = array(
            'uri' => $uri,
            'title' => $title
        );
    }

    /**
     * Add an embedded resource, identified by $rel and represented by $resource.
     *
     * @param mixed $rel
     * @param HalResource $resource
     * @access public
     * @return void
     */
    public function addResource($rel, HalResource $resource)
    {
        $this->resources[$rel][] = $resource;
    }
}

