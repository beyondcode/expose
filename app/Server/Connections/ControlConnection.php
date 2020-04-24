<?php

namespace App\Server\Connections;

use Evenement\EventEmitterTrait;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Str;
use Nyholm\Psr7\Factory\Psr17Factory;
use Ratchet\Client\WebSocket;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\WebSocket\WsConnection;
use React\Stream\Util;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class ControlConnection
{
    use EventEmitterTrait;

    /** @var ConnectionInterface */
    public $socket;
    public $host;
    public $subdomain;
    public $client_id;
    public $proxies = [];

    public function __construct(ConnectionInterface $socket, string $host, string $subdomain, string $clientId)
    {
        $this->socket = $socket;
        $this->host = $host;
        $this->subdomain = $subdomain;
        $this->client_id = $clientId;
        $this->shared_at = now()->toDateTimeString();
    }

    public function registerProxy($requestId)
    {
        $this->socket->send(json_encode([
            'event' => 'createProxy',
            'request_id' => $requestId,
            'client_id' => $this->client_id,
        ]));
    }
}
