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
 * The Hal Renderer Interface
 *
 * @package Nocarrier
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
interface HalRenderer
{
    /**
     * Render the Hal resource in the appropriate form.
     *
     * Returns a string representation of the resource.
     *
     * @param \Nocarrier\Hal $resource
     * @param boolean $pretty
     * @param boolean $encode
     */
    public function render(Hal $resource, $pretty, $encode);
}
