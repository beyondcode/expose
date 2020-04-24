<?php

namespace App\Server\Connections;

use App\Contracts\ConnectionManager as ConnectionManagerContract;
use App\Contracts\SubdomainGenerator;
use Ratchet\ConnectionInterface;

class ConnectionManager implements ConnectionManagerContract
{
    /** @var array */
    protected $connections = [];

    /** @var array */
    protected $httpConnections = [];

    /** @var SubdomainGenerator */
    protected $subdomainGenerator;

    public function __construct(SubdomainGenerator $subdomainGenerator)
    {
        $this->subdomainGenerator = $subdomainGenerator;
    }

    public function storeConnection(string $host, ?string $subdomain, ConnectionInterface $connection): ControlConnection
    {
        $clientId = (string)uniqid();

        $connection->client_id = $clientId;

        $storedConnection = new ControlConnection($connection, $host, $subdomain ?? $this->subdomainGenerator->generateSubdomain(), $clientId);

        $this->connections[] = $storedConnection;

        return $storedConnection;
    }

    public function storeHttpConnection(ConnectionInterface $httpConnection, $requestId): ConnectionInterface
    {
        $this->httpConnections[$requestId] = $httpConnection;

        return $httpConnection;
    }

    public function getHttpConnectionForRequestId(string $requestId): ?ConnectionInterface
    {
        return $this->httpConnections[$requestId] ?? null;
    }

    public function removeControlConnection($connection)
    {
        if (isset($connection->request_id)) {
            if (isset($this->httpConnections[$connection->request_id])) {
                unset($this->httpConnections[$connection->request_id]);
            }
        }

        if (isset($connection->client_id)) {
            $clientId = $connection->client_id;
            $this->connections = collect($this->connections)->reject(function ($connection) use ($clientId) {
                return $connection->client_id == $clientId;
            })->toArray();
        }
    }

    public function findControlConnectionForSubdomain($subdomain): ?ControlConnection
    {
        return collect($this->connections)->last(function ($connection) use ($subdomain) {
            return $connection->subdomain == $subdomain;
        });
    }

    public function findControlConnectionForClientId(string $clientId): ?ControlConnection
    {
        return collect($this->connections)->last(function ($connection) use ($clientId) {
            return $connection->client_id == $clientId;
        });
    }

    public function getConnections(): array
    {
        return $this->connections;
    }
}
