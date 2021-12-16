<?php

namespace App\Contracts;

use App\Server\Connections\ControlConnection;
use App\Server\Connections\HttpConnection;
use Ratchet\ConnectionInterface;

interface ConnectionManager
{
    public function storeConnection(string $host, ?string $subdomain, ?string $serverHost, ConnectionInterface $connection): ControlConnection;

    public function storeTcpConnection(int $port, ConnectionInterface $connection): ControlConnection;

    public function limitConnectionLength(ControlConnection $connection, int $maximumConnectionLength);

    public function storeHttpConnection(ConnectionInterface $httpConnection, $requestId): HttpConnection;

    public function getHttpConnectionForRequestId(string $requestId): ?HttpConnection;

    public function removeControlConnection($connection);

    public function findControlConnectionForSubdomainAndServerHost($subdomain, $serverHost): ?ControlConnection;

    public function findControlConnectionForClientId(string $clientId): ?ControlConnection;

    public function getConnections(): array;

    public function getConnectionsForAuthToken(string $authToken): array;

    public function getTcpConnectionsForAuthToken(string $authToken): array;

    public function findControlConnectionsForIp(string $ip): array;

    public function findControlConnectionsForAuthToken(string $token): array;
}
