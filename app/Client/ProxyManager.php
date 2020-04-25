<?php

namespace App\Client;

use App\Client\Http\HttpClient;
use Ratchet\Client\WebSocket;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use function Ratchet\Client\connect;

class ProxyManager
{
    /** @var Configuration */
    protected $configuration;

    /** @var LoopInterface */
    protected $loop;

    public function __construct(Configuration $configuration, LoopInterface $loop)
    {
        $this->configuration = $configuration;
        $this->loop = $loop;
    }

    public function createProxy(string $clientId, $connectionData)
    {
        $protocol = $this->configuration->port() === 443 ? "wss" : "ws";

        connect($protocol."://{$this->configuration->host()}:{$this->configuration->port()}/__expose_control__", [], [
            'X-Expose-Control' => 'enabled',
        ], $this->loop)
            ->then(function (WebSocket $proxyConnection) use ($clientId, $connectionData) {
                $proxyConnection->on('message', function ($message) use ($proxyConnection, $connectionData) {
                    $this->performRequest($proxyConnection, $connectionData->request_id, (string)$message);
                });

                $proxyConnection->send(json_encode([
                    'event' => 'registerProxy',
                    'data' => [
                        'request_id' => $connectionData->request_id ?? null,
                        'client_id' => $clientId,
                    ],
                ]));
            });
    }

    protected function performRequest(WebSocket $proxyConnection, $requestId, string $requestData)
    {
        app(HttpClient::class)->performRequest((string)$requestData, $proxyConnection, $requestId);
    }
}
