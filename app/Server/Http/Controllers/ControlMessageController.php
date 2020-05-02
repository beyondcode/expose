<?php

namespace App\Server\Http\Controllers;

use App\Contracts\ConnectionManager;
use App\Contracts\UserRepository;
use App\Http\QueryParameters;
use Ratchet\WebSocket\MessageComponentInterface;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use stdClass;
use Ratchet\ConnectionInterface;

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
     * @inheritDoc
     */
    function onOpen(ConnectionInterface $connection)
    {
    }

    /**
     * @inheritDoc
     */
    function onClose(ConnectionInterface $connection)
    {
        if (isset($connection->request_id)) {
            $httpConnection = $this->connectionManager->getHttpConnectionForRequestId($connection->request_id);
            $httpConnection->close();
        }

        $this->connectionManager->removeControlConnection($connection);
    }

    /**
     * @inheritDoc
     */
    function onMessage(ConnectionInterface $connection, $msg)
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
            ->then(function () use ($connection, $data) {
                if (! $this->hasValidSubdomain($connection, $data->subdomain)) {
                    return;
                }

                $connectionInfo = $this->connectionManager->storeConnection($data->host, $data->subdomain, $connection);

                $this->connectionManager->limitConnectionLength($connectionInfo, config('expose.admin.maximum_connection_length'));

                $connection->send(json_encode([
                    'event' => 'authenticated',
                    'subdomain' => $connectionInfo->subdomain,
                    'client_id' => $connectionInfo->client_id
                ]));
            }, function () use ($connection) {
                $connection->send(json_encode([
                    'event' => 'authenticationFailed',
                    'data' => []
                ]));
                $connection->close();
            });
    }

    protected function registerProxy(ConnectionInterface $connection, $data)
    {
        $connection->request_id = $data->request_id;

        $connectionInfo = $this->connectionManager->findControlConnectionForClientId($data->client_id);

        $connectionInfo->emit('proxy_ready_' . $data->request_id, [
            $connection,
        ]);
    }

    /**
     * @inheritDoc
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        //
    }

    protected function verifyAuthToken(ConnectionInterface $connection): PromiseInterface
    {
        if (config('expose.admin.validate_auth_tokens') !== true) {
            return new FulfilledPromise();
        }

        $deferred = new Deferred();

        $authToken = QueryParameters::create($connection->httpRequest)->get('authToken');

        $this->userRepository
            ->getUserByToken($authToken)
            ->then(function ($user) use ($connection, $deferred) {
                if (is_null($user)) {
                    $deferred->reject();
                } else {
                    $deferred->resolve($user);
                }
            });

        return $deferred->promise();
    }

    protected function hasValidSubdomain(ConnectionInterface $connection, ?string $subdomain): bool
    {
        if (!is_null($subdomain)) {
            $controlConnection = $this->connectionManager->findControlConnectionForSubdomain($subdomain);
            if (!is_null($controlConnection) || $subdomain === config('expose.admin.subdomain')) {
                $connection->send(json_encode([
                    'event' => 'subdomainTaken',
                    'data' => [
                        'subdomain' => $subdomain,
                    ]
                ]));
                $connection->close();

                return false;
            }
        }

        return true;
    }
}
