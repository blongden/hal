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
class HalResource extends Hal
{
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
     * construct a new HalResource. Call the parent and store the additional data on the resource.
     *
     * @param mixed $uri
     * @param array $data
     * @access public
     * @return void
     */
    public function __construct($uri, array $data = array())
    {
        parent::__construct($uri);
        $this->data = $data;
    }
}

