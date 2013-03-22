<?php
namespace Nocarrier;

class HalLinkContainer extends \ArrayObject
{
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
