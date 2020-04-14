<?php

namespace App\HttpServer\Controllers;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;

abstract class Controller implements HttpServerInterface
{
    public function onClose(ConnectionInterface $connection)
    {
    }

    public function onError(ConnectionInterface $connection, Exception $e)
    {
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
    }
}
