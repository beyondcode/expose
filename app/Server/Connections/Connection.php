<?php

namespace App\Server\Connections;

use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use React\Stream\Util;

class Connection
{
    /** @var IoConnection */
    public $socket;
    public $host;
    public $subdomain;
    public $client_id;
    public $proxies = [];

    public function __construct(IoConnection $socket, string $host, string $subdomain, string $clientId)
    {
        $this->socket = $socket;
        $this->host = $host;
        $this->subdomain = $subdomain;
        $this->client_id = $clientId;
    }

    public function registerProxy($requestId)
    {
        $this->socket->send(json_encode([
            'event' => 'createProxy',
            'request_id' => $requestId,
            'client_id' => $this->client_id,
        ]) . "||");
    }

    public function pipeRequestThroughProxy(HttpRequestConnection $httpConnection, string $requestId, Request $request)
    {
        $this->registerProxy($requestId);

        $this->socket->getConnection()->once('proxy_ready_' . $requestId, function (IoConnection $proxy) use ($request, $requestId, $httpConnection) {
            Util::pipe($proxy->getConnection(), $httpConnection->getConnection());

            $proxy->send(\GuzzleHttp\Psr7\str($request));
        });
    }
}
