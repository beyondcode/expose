<?php

namespace App\Server\Connections;

use Evenement\EventEmitterTrait;
use GuzzleHttp\Psr7\Request;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Nyholm\Psr7\Factory\Psr17Factory;
use Ratchet\Client\WebSocket;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\WebSocket\WsConnection;
use React\Stream\Util;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class ControlConnection implements Arrayable
{
    use EventEmitterTrait;

    /** @var ConnectionInterface */
    public $socket;
    public $host;
    public $subdomain;
    public $client_id;
    public $proxies = [];
    protected $shared_at;

    public function __construct(ConnectionInterface $socket, string $host, string $subdomain, string $clientId)
    {
        $this->socket = $socket;
        $this->host = $host;
        $this->subdomain = $subdomain;
        $this->client_id = $clientId;
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
            'host' => $this->host,
            'client_id' => $this->client_id,
            'subdomain' => $this->subdomain,
            'shared_at' => $this->shared_at,
        ];
    }
}
