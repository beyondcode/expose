<?php

namespace App\Server\Messages;

use App\Server\Connections\ConnectionManager;
use Ratchet\ConnectionInterface;

class MessageFactory
{
    public static function createForMessage(string $message, ConnectionInterface $connection, ConnectionManager $connectionManager)
    {
        $payload = json_decode($message);

        return json_last_error() === JSON_ERROR_NONE
            ? new ControlMessage($payload, $connection, $connectionManager)
            : new TunnelMessage($message, $connection, $connectionManager);
    }
}
