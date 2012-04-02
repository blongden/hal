<?php

namespace Nocarrier;

class HalResource extends Hal
{
    protected $rel;

    protected $data;

    public function __construct($uri, array $data = array())
    {
        parent::__construct($uri);
        $this->data = $data;
    }
}

