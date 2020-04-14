<?php

namespace App\Server\Messages;

use App\Server\Connections\ConnectionManager;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use stdClass;

class ControlMessage implements Message
{
    /** \stdClass */
    protected $payload;

    /** @var \Ratchet\ConnectionInterface */
    protected $connection;

    /** @var ConnectionManager */
    protected $connectionManager;

    public function __construct($payload, ConnectionInterface $connection, ConnectionManager $connectionManager)
    {
        $this->payload = $payload;

        $this->connection = $connection;

        $this->connectionManager = $connectionManager;
    }

    public function respond()
    {
        $eventName = $this->payload->event;

        if (method_exists($this, $eventName)) {
            call_user_func([$this, $eventName], $this->connection, $this->payload->data ?? new stdClass());
        }
    }

    protected function authenticate(ConnectionInterface $connection, $data)
    {
        $connectionInfo = $this->connectionManager->storeConnection($data->host, $data->subdomain, $connection);

        $connection->send(json_encode([
            'event' => 'authenticated',
            'subdomain' => $connectionInfo->subdomain,
            'client_id' => $connectionInfo->client_id
        ]));
    }

    protected function registerProxy(ConnectionInterface $connection, $data)
    {
        $connectionInfo = $this->connectionManager->findConnectionForClientId($data->client_id);

        $connectionInfo->socket->getConnection()->emit('proxy_ready_'.$data->request_id, [
            $connection,
        ]);

        $connectionInfo->setProxy($connection);
    }
}
