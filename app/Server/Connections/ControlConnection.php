<?php

namespace App\Server\Connections;

use App\Http\QueryParameters;
use Evenement\EventEmitterTrait;
use Ratchet\ConnectionInterface;

class ControlConnection
{
    use EventEmitterTrait;

    /** @var ConnectionInterface */
    public $socket;
    public $host;
    public $serverHost;
    public $authToken;
    public $subdomain;
    public $client_id;
    public $client_version;
    public $message;
    public $proxies = [];
    protected $shared_at;

    public function __construct(ConnectionInterface $socket, string $host, string $subdomain, string $clientId, string $serverHost, string $authToken = '')
    {
        $this->socket = $socket;
        $this->host = $host;
        $this->subdomain = $subdomain;
        $this->client_id = $clientId;
        $this->authToken = $authToken;
        $this->serverHost = $serverHost;
        $this->shared_at = now()->toDateTimeString();
        $this->client_version = QueryParameters::create($socket->httpRequest)->get('version');
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
                'host' => $this->host,
                'subdomain' => $this->subdomain,
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
            'remote_address' => $this->socket->remoteAddress ?? null,
            'server_host' => $this->serverHost,
            'client_id' => $this->client_id,
            'client_version' => $this->client_version,
            'auth_token' => $this->authToken,
            'subdomain' => $this->subdomain,
            'shared_at' => $this->shared_at,
        ];
    }
}
