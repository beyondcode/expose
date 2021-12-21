<?php

namespace App\Server\Connections;

use App\Contracts\ConnectionManager as ConnectionManagerContract;
use App\Contracts\LoggerRepository;
use App\Contracts\StatisticsCollector;
use App\Contracts\SubdomainGenerator;
use App\Http\QueryParameters;
use App\Server\Exceptions\NoFreePortAvailable;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\Socket\Server;

class ConnectionManager implements ConnectionManagerContract
{
    /** @var array */
    protected $connections = [];

    /** @var array */
    protected $httpConnections = [];

    /** @var SubdomainGenerator */
    protected $subdomainGenerator;

    /** @var LoopInterface */
    protected $loop;

    /** @var StatisticsCollector */
    protected $statisticsCollector;

    /** @var LoggerRepository */
    protected $logger;

    public function __construct(SubdomainGenerator $subdomainGenerator, StatisticsCollector $statisticsCollector, LoggerRepository $logger, LoopInterface $loop)
    {
        $this->subdomainGenerator = $subdomainGenerator;
        $this->loop = $loop;
        $this->statisticsCollector = $statisticsCollector;
        $this->logger = $logger;
    }

    public function limitConnectionLength(ControlConnection $connection, int $maximumConnectionLength)
    {
        if ($maximumConnectionLength === 0) {
            return;
        }

        $connection->setMaximumConnectionLength($maximumConnectionLength);

        $this->loop->addTimer($maximumConnectionLength * 60, function () use ($connection) {
            $connection->socket->close();
        });
    }

    public function storeConnection(string $host, ?string $subdomain, ?string $serverHost, ConnectionInterface $connection): ControlConnection
    {
        $clientId = (string) uniqid();

        $connection->client_id = $clientId;

        $storedConnection = new ControlConnection(
            $connection,
            $host,
            $subdomain ?? $this->subdomainGenerator->generateSubdomain(),
            $clientId,
            $serverHost,
            $this->getAuthTokenFromConnection($connection)
        );

        $this->connections[] = $storedConnection;

        $this->statisticsCollector->siteShared($this->getAuthTokenFromConnection($connection));

        $this->logger->logSubdomain($storedConnection->authToken, $storedConnection->subdomain);

        $this->performConnectionCallback($storedConnection);

        return $storedConnection;
    }

    protected function performConnectionCallback(ControlConnection $connection)
    {
        $connectionCallback = config('expose.admin.connection_callback');

        if ($connectionCallback !== null && class_exists($connectionCallback)) {
            app($connectionCallback)->handle($connection);
        }
    }

    public function storeTcpConnection(int $port, ConnectionInterface $connection): ControlConnection
    {
        $clientId = (string) uniqid();

        $connection->client_id = $clientId;

        $storedConnection = new TcpControlConnection(
            $connection,
            $port,
            $this->getSharedTcpServer(),
            $clientId,
            $this->getAuthTokenFromConnection($connection)
        );

        $this->connections[] = $storedConnection;

        $this->statisticsCollector->portShared($this->getAuthTokenFromConnection($connection));

        return $storedConnection;
    }

    protected function getSharedTcpServer(): Server
    {
        $portRange = config('expose.admin.tcp_port_range');

        $port = $portRange['from'] ?? 50000;
        $maxPort = $portRange['to'] ?? 60000;

        do {
            try {
                $portFound = true;
                $server = new Server('0.0.0.0:'.$port, $this->loop);
            } catch (\RuntimeException $exception) {
                $portFound = false;
                $port++;

                if ($port > $maxPort) {
                    throw new NoFreePortAvailable();
                }
            }
        } while (! $portFound);

        return $server;
    }

    public function storeHttpConnection(ConnectionInterface $httpConnection, $requestId): HttpConnection
    {
        $this->httpConnections[$requestId] = new HttpConnection($httpConnection);

        return $this->httpConnections[$requestId];
    }

    public function getHttpConnectionForRequestId(string $requestId): ?HttpConnection
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

            $controlConnection = collect($this->connections)->first(function ($connection) use ($clientId) {
                return $connection->client_id == $clientId;
            });

            if ($controlConnection instanceof TcpControlConnection) {
                $controlConnection->stop();
                $controlConnection = null;
            }

            $this->connections = collect($this->connections)->reject(function ($connection) use ($clientId) {
                return $connection->client_id == $clientId;
            })->toArray();
        }
    }

    public function findControlConnectionForSubdomainAndServerHost($subdomain, $serverHost): ?ControlConnection
    {
        return collect($this->connections)->last(function ($connection) use ($subdomain, $serverHost) {
            return $connection->subdomain == $subdomain && $connection->serverHost === $serverHost;
        });
    }

    public function findControlConnectionForClientId(string $clientId): ?ControlConnection
    {
        return collect($this->connections)->last(function ($connection) use ($clientId) {
            return $connection->client_id == $clientId;
        });
    }

    public function findControlConnectionsForIp(string $ip): array
    {
        return collect($this->connections)->filter(function (ControlConnection $connection) use ($ip) {
            return $connection->socket->remoteAddress == $ip;
        })->toArray();
    }

    public function findControlConnectionsForAuthToken(string $token): array
    {
        return collect($this->connections)->filter(function (ControlConnection $connection) use ($token) {
            return $connection->authToken === $token;
        })->toArray();
    }

    public function getConnections(): array
    {
        return $this->connections;
    }

    protected function getAuthTokenFromConnection(ConnectionInterface $connection): string
    {
        return QueryParameters::create($connection->httpRequest)->get('authToken');
    }

    public function getConnectionsForAuthToken(string $authToken): array
    {
        return collect($this->connections)
            ->filter(function ($connection) use ($authToken) {
                return $connection->authToken === $authToken;
            })
            ->filter(function ($connection) {
                return get_class($connection) === ControlConnection::class;
            })
            ->map(function ($connection) {
                return $connection->toArray();
            })
            ->values()
            ->toArray();
    }

    public function getTcpConnectionsForAuthToken(string $authToken): array
    {
        return collect($this->connections)
            ->filter(function ($connection) use ($authToken) {
                return $connection->authToken === $authToken;
            })
            ->filter(function ($connection) {
                return get_class($connection) === TcpControlConnection::class;
            })
            ->map(function ($connection) {
                return $connection->toArray();
            })
            ->values()
            ->toArray();
    }
}
