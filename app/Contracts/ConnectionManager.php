<?php

namespace App\Contracts;

use App\Server\Connections\ControlConnection;
use App\Server\Connections\HttpConnection;
use Ratchet\ConnectionInterface;

interface ConnectionManager
{
    public function storeConnection(string $host, ?string $subdomain, ConnectionInterface $connection): ControlConnection;

    public function limitConnectionLength(ControlConnection $connection, int $maximumConnectionLength);

    public function storeHttpConnection(ConnectionInterface $httpConnection, $requestId): HttpConnection;

    public function getHttpConnectionForRequestId(string $requestId): ?HttpConnection;

    public function removeControlConnection($connection);

    public function findControlConnectionForSubdomain($subdomain): ?ControlConnection;

    public function findControlConnectionForClientId(string $clientId): ?ControlConnection;

    public function getConnections(): array;
}
