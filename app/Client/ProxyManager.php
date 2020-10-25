<?php

namespace App\Client;

use App\Client\Http\HttpClient;
use function Ratchet\Client\connect;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Frame;
use React\EventLoop\LoopInterface;
use React\Socket\Connector;

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
        $protocol = $this->configuration->port() === 443 ? 'wss' : 'ws';

        connect($protocol."://{$this->configuration->host()}:{$this->configuration->port()}/expose/control", [], [
            'X-Expose-Control' => 'enabled',
        ], $this->loop)
            ->then(function (WebSocket $proxyConnection) use ($clientId, $connectionData) {
                $proxyConnection->on('message', function ($message) use ($proxyConnection, $connectionData) {
                    $this->performRequest($proxyConnection, (string) $message, $connectionData);
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

    public function createTcpProxy(string $clientId, $connectionData)
    {
        $protocol = $this->configuration->port() === 443 ? 'wss' : 'ws';

        connect($protocol."://{$this->configuration->host()}:{$this->configuration->port()}/expose/control", [], [
            'X-Expose-Control' => 'enabled',
        ], $this->loop)
            ->then(function (WebSocket $proxyConnection) use ($clientId, $connectionData) {
                $connector = new Connector($this->loop);

                $connector->connect('127.0.0.1:'.$connectionData->port)->then(function ($connection) use ($proxyConnection) {
                    $connection->on('data', function ($data) use ($proxyConnection) {
                        $binaryMsg = new Frame($data, true, Frame::OP_BINARY);
                        $proxyConnection->send($binaryMsg);
                    });

                    $proxyConnection->on('message', function ($message) use ($connection) {
                        $connection->write($message);
                    });
                });

                $proxyConnection->send(json_encode([
                    'event' => 'registerTcpProxy',
                    'data' => [
                        'tcp_request_id' => $connectionData->tcp_request_id ?? null,
                        'client_id' => $clientId,
                    ],
                ]));
            });
    }

    protected function performRequest(WebSocket $proxyConnection, string $requestData, $connectionData)
    {
        app(HttpClient::class)->performRequest((string) $requestData, $proxyConnection, $connectionData);
    }
}
