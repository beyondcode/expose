<?php

namespace App\Contracts;

use App\Server\Connections\ControlConnection;
use Ratchet\ConnectionInterface;

interface ConnectionManager
{
    public function storeConnection(string $host, ?string $subdomain, ConnectionInterface $connection): ControlConnection;

    public function storeHttpConnection(ConnectionInterface $httpConnection, $requestId): ConnectionInterface;

    public function getHttpConnectionForRequestId(string $requestId): ?ConnectionInterface;

    public function removeControlConnection($connection);

    public function findControlConnectionForSubdomain($subdomain): ?ControlConnection;

    public function findControlConnectionForClientId(string $clientId): ?ControlConnection;

    public function getConnections(): array;
}
