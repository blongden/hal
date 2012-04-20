<?php
namespace Nocarrier;

class HalJsonRenderer implements HalRenderer
{
    public function render(Hal $resource, $pretty)
    {
        $options = 0;

        if (version_compare(PHP_VERSION, '5.4.0') >= 0 and $pretty) {
            $options = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        }

        return json_encode($this->arrayForJson($resource), $options);
    }
    
    /**
     * Return an array (compatible with the hal+json format) representing associated links
     *
     * @param mixed $uri
     * @param array $links
     * @return array
     */
    protected function linksForJson($uri, $links)
    {
        $data = array('self' => array('href' => $uri));

        foreach($links as $rel => $link) {
            $data[$rel] = array('href' => $link['uri']);
            if (!is_null($link['title'])) {
                $data[$rel]['title'] = $link['title'];
            }
        }

        return $data;
    }

    /**
     * Return an array (compatible with the hal+json format) representing associated resources
     *
     * @param mixed $resources
     * @return array
     */
    protected function resourcesForJson($resources)
    {
        $data = array();

        foreach ($resources as $resource) {
            $data[] = $this->arrayForJson($resource);
        }

        return $data;
    }

    /**
     * Return an array (compatible with the hal+json format) representing the
     * complete response
     *
     * @param Hal $resource
     * @return array
     */
    protected function arrayForJson(Hal $resource)
    {
        $data = $resource->getData();
        $data['_links'] = $this->linksForJson($resource->getUri(), $resource->getLinks());

        foreach($resource->getResources() as $rel => $resources) {
            $data['_embedded'][$rel] = $this->resourcesForJson($resources);
        }

        return $data;
    }

}
