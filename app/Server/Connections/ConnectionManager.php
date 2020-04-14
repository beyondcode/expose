<?php

namespace App\Server\Connections;

use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;

class ConnectionManager
{
    /** @var array */
    protected $connections = [];
    protected $host;
    protected $port;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function storeConnection(string $host, ?string $subdomain, IoConnection $connection)
    {
        $clientId = (string)uniqid();

        $storedConnection = new Connection($connection, $host, $subdomain ?? $this->generateSubdomain(), $clientId);

        $this->connections[] = $storedConnection;

        return $storedConnection;
    }

    public function findConnectionForSubdomain($subdomain): ?Connection
    {
        return collect($this->connections)->last(function ($connection) use ($subdomain) {
            return $connection->subdomain == $subdomain;
        });
    }

    public function findConnectionForClientId(string $clientId): ?Connection
    {
        return collect($this->connections)->last(function ($connection) use ($clientId) {
            return $connection->client_id == $clientId;
        });
    }

    protected function generateSubdomain(): string
    {
        return strtolower(Str::random(10));
    }

    public function host()
    {
        return $this->host === '127.0.0.1' ? 'localhost' : $this->host;
    }

    public function port()
    {
        return $this->port;
    }
}
