<?php

namespace App\WebSockets;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class Socket implements MessageComponentInterface
{
    public static $connections = [];

    public function onOpen(ConnectionInterface $connection)
    {
        self::$connections[] = $connection;
    }

    public function onMessage(ConnectionInterface $from, MessageInterface $msg)
    {
    }

    public function onClose(ConnectionInterface $connection)
    {
    }

    public function onError(ConnectionInterface $connection, \Exception $e)
    {
    }
}
