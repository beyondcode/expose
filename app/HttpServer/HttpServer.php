<?php

namespace App\HttpServer;

use Ratchet\Http\HttpServerInterface;

class HttpServer extends \Ratchet\Http\HttpServer
{
    public function __construct(HttpServerInterface $component)
    {
        parent::__construct($component);

        $this->_reqParser->maxSize = 15242880;
    }
}
