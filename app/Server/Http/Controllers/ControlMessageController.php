<?php

namespace App\Server\Http\Controllers;

use App\Contracts\ConnectionManager;
use App\Contracts\UserRepository;
use App\Http\QueryParameters;
use App\Server\Exceptions\NoFreePortAvailable;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use stdClass;

class ControlMessageController implements MessageComponentInterface
{
    /** @var ConnectionManager */
    protected $connectionManager;

    /** @var UserRepository */
    protected $userRepository;

    public function __construct(ConnectionManager $connectionManager, UserRepository $userRepository)
    {
        $this->connectionManager = $connectionManager;
        $this->userRepository = $userRepository;
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
        $this->verifyAuthToken($connection)
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

    protected function handleHttpConnection(ConnectionInterface $connection, $data, $user = null)
    {
        if (! $this->hasValidSubdomain($connection, $data->subdomain, $user)) {
            return;
        }

        $connectionInfo = $this->connectionManager->storeConnection($data->host, $data->subdomain, $connection);

        $this->connectionManager->limitConnectionLength($connectionInfo, config('expose.admin.maximum_connection_length'));

        $connection->send(json_encode([
            'event' => 'authenticated',
            'data' => [
                'message' => config('expose.admin.messages.message_of_the_day'),
                'subdomain' => $connectionInfo->subdomain,
                'client_id' => $connectionInfo->client_id,
            ],
        ]));
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

        $connection->send(json_encode([
            'event' => 'authenticated',
            'data' => [
                'message' => config('expose.admin.messages.message_of_the_day'),
                'port' => $connectionInfo->port,
                'shared_port' => $connectionInfo->shared_port,
                'client_id' => $connectionInfo->client_id,
            ],
        ]));
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
                    $deferred->resolve($user);
                }
            });

        return $deferred->promise();
    }

    protected function hasValidSubdomain(ConnectionInterface $connection, ?string $subdomain, ?array $user): bool
    {
        if (! is_null($user) && $user['can_specify_subdomains'] === 0 && ! is_null($subdomain)) {
            $connection->send(json_encode([
                'event' => 'subdomainTaken',
                'data' => [
                    'message' => config('expose.admin.messages.custom_subdomain_unauthorized'),
                ],
            ]));
            $connection->close();

            return false;
        }

        if (! is_null($subdomain)) {
            $controlConnection = $this->connectionManager->findControlConnectionForSubdomain($subdomain);
            if (! is_null($controlConnection) || $subdomain === config('expose.admin.subdomain')) {
                $message = config('expose.admin.messages.subdomain_taken');
                $message = str_replace(':subdomain', $subdomain, $message);

                $connection->send(json_encode([
                    'event' => 'subdomainTaken',
                    'data' => [
                        'message' => $message,
                    ],
                ]));
                $connection->close();

                return false;
            }
        }

        return true;
    }

    protected function canShareTcpPorts(ConnectionInterface $connection, $data, $user)
    {
        if (! is_null($user) && $user['can_share_tcp_ports'] === 0) {
            $connection->send(json_encode([
                'event' => 'authenticationFailed',
                'data' => [
                    'message' => config('expose.admin.messages.custom_subdomain_unauthorized'),
                ],
            ]));
            $connection->close();

            return false;
        }

        return true;
    }
}
