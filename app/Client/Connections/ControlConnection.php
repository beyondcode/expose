<?php

namespace App\Client\Connections;

use App\Client\ProxyManager;
use Evenement\EventEmitterTrait;
use Ratchet\Client\WebSocket;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\Message;

class ControlConnection
{
    use EventEmitterTrait;

    /** @var ConnectionInterface */
    protected $socket;

    /** @var ProxyManager */
    protected $proxyManager;

    /** @var string */
    protected $clientId;

    public static function create(WebSocket $socketConnection)
    {
        return new static($socketConnection, app(ProxyManager::class));
    }

    public function __construct(WebSocket $socketConnection, ProxyManager $proxyManager)
    {
        $this->socket = $socketConnection;
        $this->proxyManager = $proxyManager;

        $this->socket->on('message', function (Message $message) {
            $decodedEntry = json_decode($message);

            $this->emit($decodedEntry->event ?? '', [$decodedEntry->data]);

            if (method_exists($this, $decodedEntry->event ?? '')) {
                call_user_func([$this, $decodedEntry->event], $decodedEntry->data);
            }
        });
    }

    public function authenticated($data)
    {
        $this->clientId = $data->client_id;
    }

    public function createProxy($data)
    {
        $this->proxyManager->createProxy($this->clientId, $data);
    }

    public function createTcpProxy($data)
    {
        $this->proxyManager->createTcpProxy($this->clientId, $data);
    }

    public function authenticate(string $sharedHost, string $subdomain, $serverHost = null)
    {
        $this->socket->send(json_encode([
            'event' => 'authenticate',
            'data' => [
                'type' => 'http',
                'host' => $sharedHost,
                'server_host' => $serverHost,
                'subdomain' => empty($subdomain) ? null : $subdomain,
            ],
        ]));
    }

    public function authenticateTcp(int $port)
    {
        $this->socket->send(json_encode([
            'event' => 'authenticate',
            'data' => [
                'type' => 'tcp',
                'port' => $port,
            ],
        ]));
    }

    public function ping()
    {
        $this->socket->send(json_encode([
            'event' => 'pong',
        ]));
    }
}
