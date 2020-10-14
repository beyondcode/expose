<?php

namespace App\Server\Connections;

use Evenement\EventEmitterTrait;
use Ratchet\ConnectionInterface;

class ControlConnection
{
    use EventEmitterTrait;

    /** @var ConnectionInterface */
    public $socket;
    public $host;
    public $authToken;
    public $subdomain;
    public $client_id;
    public $proxies = [];
    protected $shared_at;

    public function __construct(ConnectionInterface $socket, string $host, string $subdomain, string $clientId, string $authToken = '')
    {
        $this->socket = $socket;
        $this->host = $host;
        $this->subdomain = $subdomain;
        $this->client_id = $clientId;
        $this->authToken = $authToken;
        $this->shared_at = now()->toDateTimeString();
    }

    public function setMaximumConnectionLength(int $maximumConnectionLength)
    {
        $this->socket->send(json_encode([
            'event' => 'setMaximumConnectionLength',
            'data' => [
                'length' => $maximumConnectionLength,
            ],
        ]));
    }

    public function registerProxy($requestId)
    {
        $this->socket->send(json_encode([
            'event' => 'createProxy',
            'data' => [
                'request_id' => $requestId,
                'client_id' => $this->client_id,
            ],
        ]));
    }

    public function close()
    {
        $this->socket->close();
    }

    public function toArray()
    {
        return [
            'type' => 'http',
            'host' => $this->host,
            'client_id' => $this->client_id,
            'auth_token' => $this->authToken,
            'subdomain' => $this->subdomain,
            'shared_at' => $this->shared_at,
        ];
    }
}
