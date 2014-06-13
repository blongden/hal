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
 * The Hal document class
 *
 * @package Nocarrier
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
class Hal
{
    /**
     * The uri represented by this representation.
     *
     * @var string
     */
    protected $uri;

    /**
     * The data for this resource. An associative array of key value pairs.
     *
     * array(
     *     'price' => 30.00,
     *     'colour' => 'blue'
     * )
     *
     * @var array
     */
    protected $data;

    /**
     * An array of embedded Hal objects representing embedded resources.
     *
     * @var array
     */
    protected $resources = array();

    /**
     * A collection of \Nocarrier\HalLink objects keyed by the link relation to
     * this resource.
     *
     * array(
     *     'next' => [HalLink]
     * )
     *
     * @var array
     */
    protected $links = null;

    /**
     * Construct a new Hal object from an array of data. You can markup the
     * $data array with certain keys and values in order to affect the
     * generated JSON or XML documents if required to do so.
     *
     * '@' prefix on any array key will cause the value to be set as an
     * attribute on the XML element generated by the parent. i.e, array('x' =>
     * array('@href' => 'http://url')) will yield <x href='http://url'></x> in
     * the XML representation. The @ prefix will be stripped from the JSON
     * representation.
     *
     * Specifying the key 'value' will cause the value of this key to be set as
     * the value of the XML element instead of a child. i.e, array('x' =>
     * array('value' => 'example')) will yield <x>example</x> in the XML
     * representation. This will not affect the JSON representation.
     *
     * @param mixed $uri
     * @param array|Traversable $data
     *
     * @throws \RuntimeException
     */
    public function __construct($uri = null, $data = array())
    {
        $this->uri = $uri;

        if (!is_array($data) && !$data instanceof \Traversable) {
            throw new \RuntimeException(
                'The $data parameter must be an array or an object implementing the Traversable interface.');
        }
        $this->data = $data;

        $this->links = new HalLinkContainer();
    }

    /**
     * Decode a application/hal+json document into a Nocarrier\Hal object.
     *
     * @param string $data
     * @param int $depth
     * @static
     * @access public
     * @return \Nocarrier\Hal
     */
    public static function fromJson($data, $depth = 0)
    {
        return JsonHalFactory::fromJson(new static(), $data, $depth);
    }

    /**
     * Decode a application/hal+xml document into a Nocarrier\Hal object.
     *
     * @param int $depth
     *
     * @static
     * @access public
     * @return \Nocarrier\Hal
     */
    public static function fromXml($data, $depth = 0)
    {
        return XmlHalFactory::fromXml(new static(), $data, $depth);
    }

    /**
     * Add a link to the resource, identified by $rel, located at $uri.
     *
     * @param string $rel
     * @param string $uri
     * @param array $attributes
     *   Other attributes, as defined by HAL spec and RFC 5988.
     * @return \Nocarrier\Hal
     */
    public function addLink($rel, $uri, array $attributes = array())
    {
        $this->links[$rel][] = new HalLink($uri, $attributes);

        return $this;
    }

    /**
     * Add an embedded resource, identified by $rel and represented by $resource.
     *
     * @param string $rel
     * @param \Nocarrier\Hal $resource
     *
     * @return \Nocarrier\Hal
     */
    public function addResource($rel, \Nocarrier\Hal $resource = null)
    {
        $this->resources[$rel][] = $resource;

        return $this;
    }

    /**
     * Set an embedded resource, identified by $rel and represented by $resource
     *
     * Using this method signifies that $rel will only ever be a single object
     * (only really relevant to JSON rendering)
     *
     * @param string $rel
     * @param Hal $resource
     */
    public function setResource($rel, $resource)
    {
        if (is_array($resource)) {
            foreach ($resource as $r) {
                $this->addResource($rel, $r);
            }

            return $this;
        }

        if (!($resource instanceof Hal)) {
            throw new \InvalidArgumentException('$resource should be of type array or Nocarrier\Hal');
        }

        $this->resources[$rel] = $resource;

        return $this;
    }

    /**
     * Set resource's data
     */
    public function setData(Array $data = null)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Return an array of data (key => value pairs) representing this resource.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return an array of Nocarrier\HalLink objects representing resources
     * related to this one.
     *
     * @return array A collection of \Nocarrier\HalLink
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Lookup and return an array of HalLink objects for a given relation.
     * Will also resolve CURIE rels if required.
     *
     * @param string $rel The link relation required
     * @return array|bool
     *   Array of HalLink objects if found. Otherwise false.
     */
    public function getLink($rel)
    {
        return $this->links->get($rel);
    }

    /**
     * Return an array of Nocarrier\Hal objected embedded in this one.
     *
     * @return array
     */
    public function getResources()
    {
        $resources = array_map(function ($resource) {
            return is_array($resource) ? $resource : array($resource);
        }, $this->getRawResources());

        return $resources;
    }

    /**
     * Return an array of Nocarrier\Hal objected embedded in this one. Each key
     * may contain an array of resources, or a single resource. For a
     * consistent approach, use getResources
     *
     * @return array
     */
    public function getRawResources()
    {
        return $this->resources;
    }

    /**
     * Get the first resource for a given rel. Useful if you're only expecting
     * one resource, or you don't care about subsequent resources
     *
     * @return Hal
     */
    public function getFirstResource($rel)
    {
        $resources = $this->getResources();

        if (isset($resources[$rel])) {
            return $resources[$rel][0];
        }

        return null;
    }

    /**
     * Set resource's URI
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Get resource's URI.
     *
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Return the current object in a application/hal+json format (links and
     * resources).
     *
     * @param bool $pretty
     *   Enable pretty-printing.
     * @param bool $encode
     *   Run through json_encode
     * @return string
     */
    public function asJson($pretty = false, $encode = true)
    {
        $renderer = new HalJsonRenderer();

        return $renderer->render($this, $pretty, $encode);
    }

    /**
     * Return the current object in a application/hal+xml format (links and
     * resources).
     *
     * @param bool $pretty Enable pretty-printing
     * @return string
     */
    public function asXml($pretty = false)
    {
        $renderer = new HalXmlRenderer();

        return $renderer->render($this, $pretty);
    }

    /**
     * Create a CURIE link template, used for abbreviating custom link
     * relations.
     *
     * e.g,
     * $hal->addCurie('acme', 'http://.../rels/{rel}');
     * $hal->addLink('acme:test', 'http://.../test');
     *
     * @param string $name
     * @param string $uri
     *
     * @return \Nocarrier\Hal
     */
    public function addCurie($name, $uri)
    {
        return $this->addLink('curies', $uri, array('name' => $name, 'templated' => true));
    }
}
