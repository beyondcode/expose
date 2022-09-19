<?php

namespace App\Server\Http\Controllers;

use App\Contracts\ConnectionManager;
use App\Contracts\DomainRepository;
use App\Contracts\SubdomainRepository;
use App\Contracts\UserRepository;
use App\Http\QueryParameters;
use App\Server\Configuration;
use App\Server\Exceptions\NoFreePortAvailable;
use Illuminate\Support\Arr;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use stdClass;

class ControlMessageController implements MessageComponentInterface
{
    /** @var ConnectionManager */
    protected $connectionManager;

    /** @var UserRepository */
    protected $userRepository;

    /** @var SubdomainRepository */
    protected $subdomainRepository;

    /** @var DomainRepository */
    protected $domainRepository;

    /** @var Configuration */
    protected $configuration;

    public function __construct(ConnectionManager $connectionManager, UserRepository $userRepository, SubdomainRepository $subdomainRepository, Configuration $configuration, DomainRepository $domainRepository)
    {
        $this->connectionManager = $connectionManager;
        $this->userRepository = $userRepository;
        $this->subdomainRepository = $subdomainRepository;
        $this->domainRepository = $domainRepository;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $connection)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $connection)
    {
        if (isset($connection->request_id)) {
            $httpConnection = $this->connectionManager->getHttpConnectionForRequestId($connection->request_id);
            $httpConnection->close();
        }

        $this->connectionManager->removeControlConnection($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $connection, $msg)
    {
        if (isset($connection->request_id)) {
            return $this->sendResponseToHttpConnection($connection->request_id, $msg);
        }
        if (isset($connection->tcp_request_id)) {
            $connectionInfo = $this->connectionManager->findControlConnectionForClientId($connection->tcp_client_id);
            $connectionInfo->proxyConnection->write($msg);
        }

        try {
            $payload = json_decode($msg);
            $eventName = $payload->event;

            if (method_exists($this, $eventName)) {
                return call_user_func([$this, $eventName], $connection, $payload->data ?? new stdClass());
            }
        } catch (\Throwable $exception) {
            //
        }
    }

    protected function sendResponseToHttpConnection(string $requestId, $response)
    {
        $httpConnection = $this->connectionManager->getHttpConnectionForRequestId($requestId);

        $httpConnection->send($response);
    }

    protected function authenticate(ConnectionInterface $connection, $data)
    {
        if (! isset($data->subdomain)) {
            $data->subdomain = null;
        }
        if (! isset($data->type)) {
            $data->type = 'http';
        }
        if (! isset($data->server_host) || is_null($data->server_host)) {
            $data->server_host = $this->configuration->hostname();
        }

        $this->verifyAuthToken($connection)
            ->then(function ($user) use ($connection) {
                $maximumConnectionCount = config('expose.admin.maximum_open_connections_per_user', 0);

                if (is_null($user)) {
                    $connectionCount = count($this->connectionManager->findControlConnectionsForIp($connection->remoteAddress));
                } else {
                    $maximumConnectionCount = Arr::get($user, 'max_connections', $maximumConnectionCount);

                    $connectionCount = count($this->connectionManager->findControlConnectionsForAuthToken($user['auth_token']));
                }

                if ($maximumConnectionCount > 0 && $connectionCount + 1 > $maximumConnectionCount) {
                    $connection->send(json_encode([
                        'event' => 'authenticationFailed',
                        'data' => [
                            'message' => config('expose.admin.messages.maximum_connection_count'),
                        ],
                    ]));
                    $connection->close();

                    reject(null);
                }

                return $user;
            })
            ->then(function ($user) use ($connection, $data) {
                if ($data->type === 'http') {
                    $this->handleHttpConnection($connection, $data, $user);
                } elseif ($data->type === 'tcp') {
                    $this->handleTcpConnection($connection, $data, $user);
                }
            }, function () use ($connection) {
                $connection->send(json_encode([
                    'event' => 'authenticationFailed',
                    'data' => [
                        'message' => config('expose.admin.messages.invalid_auth_token'),
                    ],
                ]));
                $connection->close();
            });
    }

    protected function resolveConnectionMessage($connectionInfo, $user)
    {
        $deferred = new Deferred();

        $connectionMessageResolver = config('expose.admin.messages.resolve_connection_message')($connectionInfo, $user);

        if ($connectionMessageResolver instanceof PromiseInterface) {
            $connectionMessageResolver->then(function ($connectionMessage) use ($connectionInfo, $deferred) {
                $connectionInfo->message = $connectionMessage;
                $deferred->resolve($connectionInfo);
            });
        } else {
            $connectionInfo->message = $connectionMessageResolver;

            return \React\Promise\resolve($connectionInfo);
        }

        return $deferred->promise();
    }

    protected function handleHttpConnection(ConnectionInterface $connection, $data, $user = null)
    {
        $this->hasValidDomain($connection, $data->server_host, $user)
            ->then(function () use ($connection, $data, $user) {
                return $this->hasValidSubdomain($connection, $data->subdomain, $user, $data->server_host);
            })
            ->then(function ($subdomain) use ($data, $connection, $user) {
                if ($subdomain === false) {
                    return;
                }

                $data->subdomain = $subdomain;

                $connectionInfo = $this->connectionManager->storeConnection($data->host, $data->subdomain, $data->server_host, $connection);

                $this->connectionManager->limitConnectionLength($connectionInfo, config('expose.admin.maximum_connection_length'));

                return $this->resolveConnectionMessage($connectionInfo, $user);
            })
            ->then(function ($connectionInfo) use ($connection, $user) {
                $connection->send(json_encode([
                    'event' => 'authenticated',
                    'data' => [
                        'message' => $connectionInfo->message,
                        'subdomain' => $connectionInfo->subdomain,
                        'server_host' => $connectionInfo->serverHost,
                        'user' => $user,
                        'client_id' => $connectionInfo->client_id,
                    ],
                ]));
            });
    }

    protected function handleTcpConnection(ConnectionInterface $connection, $data, $user = null)
    {
        if (! $this->canShareTcpPorts($connection, $data, $user)) {
            return;
        }

        try {
            $connectionInfo = $this->connectionManager->storeTcpConnection($data->port, $connection);
        } catch (NoFreePortAvailable $exception) {
            $connection->send(json_encode([
                'event' => 'authenticationFailed',
                'data' => [
                    'message' => config('expose.admin.messages.no_free_tcp_port_available'),
                ],
            ]));
            $connection->close();

            return;
        }

        $this->resolveConnectionMessage($connectionInfo, $user)
            ->then(function ($connectionInfo) use ($connection, $user) {
                $connection->send(json_encode([
                    'event' => 'authenticated',
                    'data' => [
                        'message' => $connectionInfo->message,
                        'user' => $user,
                        'port' => $connectionInfo->port,
                        'shared_port' => $connectionInfo->shared_port,
                        'client_id' => $connectionInfo->client_id,
                    ],
                ]));
            });
    }

    protected function registerProxy(ConnectionInterface $connection, $data)
    {
        $connection->request_id = $data->request_id;

        $connectionInfo = $this->connectionManager->findControlConnectionForClientId($data->client_id);

        $connectionInfo->emit('proxy_ready_'.$data->request_id, [
            $connection,
        ]);
    }

    protected function registerTcpProxy(ConnectionInterface $connection, $data)
    {
        $connection->tcp_client_id = $data->client_id;
        $connection->tcp_request_id = $data->tcp_request_id;

        $connectionInfo = $this->connectionManager->findControlConnectionForClientId($data->client_id);

        $connectionInfo->emit('tcp_proxy_ready_'.$data->tcp_request_id, [
            $connection,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        //
    }

    protected function verifyAuthToken(ConnectionInterface $connection): PromiseInterface
    {
        if (config('expose.admin.validate_auth_tokens') !== true) {
            return \React\Promise\resolve(null);
        }

        $deferred = new Deferred();

        $authToken = QueryParameters::create($connection->httpRequest)->get('authToken');

        $this->userRepository
            ->getUserByToken($authToken)
            ->then(function ($user) use ($deferred) {
                if (is_null($user)) {
                    $deferred->reject();
                } else {
                    $this->userRepository
                        ->updateLastSharedAt($user['id'])
                        ->then(function () use ($deferred, $user) {
                            $deferred->resolve($user);
                        });
                }
            });

        return $deferred->promise();
    }

    protected function hasValidDomain(ConnectionInterface $connection, ?string $serverHost, ?array $user): PromiseInterface
    {
        if (! is_null($user) && $serverHost !== $this->configuration->hostname()) {
            $deferred = new Deferred();

            $this->domainRepository
                ->getDomainsByUserId($user['id'])
                ->then(function ($domains) use ($connection, $deferred, $serverHost) {
                    $userDomain = collect($domains)->first(function ($domain) use ($serverHost) {
                        return strtolower($domain['domain']) === strtolower($serverHost);
                    });

                    if (is_null($userDomain)) {
                        $connection->send(json_encode([
                            'event' => 'authenticationFailed',
                            'data' => [
                                'message' => config('expose.admin.messages.custom_domain_unauthorized').PHP_EOL,
                            ],
                        ]));
                        $connection->close();

                        $deferred->reject(null);

                        return;
                    }

                    $deferred->resolve(null);
                });

            return $deferred->promise();
        } else {
            return \React\Promise\resolve(null);
        }
    }

    protected function hasValidSubdomain(ConnectionInterface $connection, ?string $subdomain, ?array $user, string $serverHost): PromiseInterface
    {
        /**
         * Check if the user can specify a custom subdomain in the first place.
         */
        if (! is_null($user) && $user['can_specify_subdomains'] === 0 && ! is_null($subdomain)) {
            $connection->send(json_encode([
                'event' => 'error',
                'data' => [
                    'message' => config('expose.admin.messages.custom_subdomain_unauthorized').PHP_EOL,
                ],
            ]));

            return \React\Promise\resolve(null);
        }

        /**
         * Check if the given subdomain is reserved for a different user.
         */
        if (! is_null($subdomain)) {
            return $this->subdomainRepository->getSubdomainsByNameAndDomain($subdomain, $serverHost)
                ->then(function ($foundSubdomains) use ($connection, $subdomain, $user, $serverHost) {
                    $ownSubdomain = collect($foundSubdomains)->first(function ($subdomain) use ($user) {
                        return $subdomain['user_id'] === $user['id'];
                    });

                    if (count($foundSubdomains) > 0 && ! is_null($user) && is_null($ownSubdomain)) {
                        $message = config('expose.admin.messages.subdomain_reserved', '');
                        $message = str_replace(':subdomain', $subdomain, $message);

                        $connection->send(json_encode([
                            'event' => 'subdomainTaken',
                            'data' => [
                                'message' => $message,
                            ],
                        ]));
                        $connection->close();

                        return \React\Promise\resolve(false);
                    }

                    $controlConnection = $this->connectionManager->findControlConnectionForSubdomainAndServerHost($subdomain, $serverHost);

                    if (! is_null($controlConnection) || $subdomain === config('expose.admin.subdomain') || in_array($subdomain, config('expose.admin.reserved_subdomains', []))) {
                        $message = config('expose.admin.messages.subdomain_taken');
                        $message = str_replace(':subdomain', $subdomain, $message);

                        $connection->send(json_encode([
                            'event' => 'subdomainTaken',
                            'data' => [
                                'message' => $message,
                            ],
                        ]));
                        $connection->close();

                        return \React\Promise\resolve(false);
                    }

                    return \React\Promise\resolve($subdomain);
                });
        }

        return \React\Promise\resolve($subdomain);
    }

    protected function canShareTcpPorts(ConnectionInterface $connection, $data, $user)
    {
        if (! config('expose.admin.allow_tcp_port_sharing', true)) {
            $connection->send(json_encode([
                'event' => 'authenticationFailed',
                'data' => [
                    'message' => config('expose.admin.messages.tcp_port_sharing_disabled'),
                ],
            ]));
            $connection->close();

            return false;
        }

        if (! is_null($user) && $user['can_share_tcp_ports'] === 0) {
            $connection->send(json_encode([
                'event' => 'authenticationFailed',
                'data' => [
                    'message' => config('expose.admin.messages.tcp_port_sharing_unauthorized'),
                ],
            ]));
            $connection->close();

            return false;
        }

        return true;
    }
}
