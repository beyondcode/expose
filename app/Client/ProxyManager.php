<?php

namespace App\Client;

use App\Client\Http\HttpClient;
use function Ratchet\Client\connect;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;

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
        $protocol = $this->configuration->ssl() ? 'wss' : 'ws';

        connect($protocol."://{$this->configuration->host()}:{$this->configuration->port()}/expose/control", [], [
            'X-Expose-Control' => 'enabled',
        ], $this->loop)
            ->then(function (WebSocket $proxyConnection) use ($clientId, $connectionData) {
                $proxyConnection->on('message', function ($message) use ($proxyConnection, $connectionData) {
                    $this->performRequest($proxyConnection, $connectionData->request_id, (string) $message);
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
        app(HttpClient::class)->performRequest((string) $requestData, $proxyConnection, $requestId);
    }
}
