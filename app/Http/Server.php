<?php

namespace App\Http;

use Ratchet\Http\HttpServerInterface;

class Server extends \Ratchet\Http\HttpServer
{
    public function __construct(HttpServerInterface $component)
    {
        parent::__construct($component);

        $this->_reqParser->maxSize = 15242880000;
    }
}
