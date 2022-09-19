<?php

namespace App\Server\Connections;

use App\Http\QueryParameters;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\Frame;
use React\Socket\Server;

class TcpControlConnection extends ControlConnection
{
    public $proxy;
    public $proxyConnection;
    public $port;
    public $shared_port;
    public $shared_server;
    public $client_version;

    public function __construct(ConnectionInterface $socket, int $port, Server $sharedServer, string $clientId, string $authToken = '')
    {
        $this->socket = $socket;
        $this->client_id = $clientId;
        $this->shared_server = $sharedServer;
        $this->port = $port;
        $this->shared_at = now()->toDateTimeString();
        $this->shared_port = parse_url($sharedServer->getAddress(), PHP_URL_PORT);
        $this->authToken = $authToken;
        $this->client_version = QueryParameters::create($socket->httpRequest)->get('version');

        $this->configureServer($sharedServer);
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

    public function registerTcpProxy($requestId)
    {
        $this->socket->send(json_encode([
            'event' => 'createTcpProxy',
            'data' => [
                'port' => $this->port,
                'tcp_request_id' => $requestId,
                'client_id' => $this->client_id,
            ],
        ]));
    }

    public function stop()
    {
        $this->shared_server->close();
        $this->shared_server = null;
    }

    public function close()
    {
        $this->socket->close();
    }

    public function toArray()
    {
        return [
            'type' => 'tcp',
            'port' => $this->port,
            'auth_token' => $this->authToken,
            'client_id' => $this->client_id,
            'client_version' => $this->client_version,
            'shared_port' => $this->shared_port,
            'shared_at' => $this->shared_at,
        ];
    }

    protected function configureServer(Server $sharedServer)
    {
        $requestId = uniqid();

        $sharedServer->on('connection', function (\React\Socket\ConnectionInterface $connection) use ($requestId) {
            $this->proxyConnection = $connection;

            $this->once('tcp_proxy_ready_'.$requestId, function (ConnectionInterface $proxy) use ($connection) {
                $this->proxy = $proxy;

                $connection->on('data', function ($data) use ($proxy) {
                    $binaryMsg = new Frame($data, true, Frame::OP_BINARY);
                    $proxy->send($binaryMsg);
                });

                $connection->resume();
            });

            $connection->pause();
            $this->registerTcpProxy($requestId);
        });
    }
}
