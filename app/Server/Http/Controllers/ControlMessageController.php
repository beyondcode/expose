<?php

namespace App\Server\Http\Controllers;

use App\Contracts\ConnectionManager;
use App\Contracts\UserRepository;
use App\Http\QueryParameters;
use Illuminate\Support\Arr;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
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

    protected function registerProxy(ConnectionInterface $connection, $data)
    {
        $connection->request_id = $data->request_id;

        $connectionInfo = $this->connectionManager->findControlConnectionForClientId($data->client_id);

        $connectionInfo->emit('proxy_ready_'.$data->request_id, [
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
}
