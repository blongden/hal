<?php
namespace Nocarrier;

interface HalRenderer
{
    public function render(Hal $resource, $pretty);
}
