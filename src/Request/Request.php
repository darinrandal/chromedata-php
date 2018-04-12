<?php

namespace Darinrandal\ChromeData\Request;


use Darinrandal\ChromeData\Adapter\Adapter;

class Request
{
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

}