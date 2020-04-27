<?php

namespace App\Server\Http\Controllers;

use App\Contracts\ConnectionManager;
use App\HttpServer\QueryParameters;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use Ratchet\WebSocket\MessageComponentInterface;
use stdClass;
use Ratchet\ConnectionInterface;

class ControlMessageController implements MessageComponentInterface
{

    /** @var ConnectionManager */
    protected $connectionManager;

    /** @var DatabaseInterface */
    protected $database;

    public function __construct(ConnectionManager $connectionManager, DatabaseInterface $database)
    {
        $this->connectionManager = $connectionManager;
        $this->database = $database;
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
            return $this->sendRequestToHttpConnection($connection->request_id, $msg);
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

    protected function sendRequestToHttpConnection(string $requestId, $request)
    {
        $httpConnection = $this->connectionManager->getHttpConnectionForRequestId($requestId);
        $httpConnection->send($request);
    }

    protected function authenticate(ConnectionInterface $connection, $data)
    {
        if (config('expose.validate_auth_tokens') === true) {
            $this->verifyAuthToken($connection);
        }

        if (! $this->hasValidSubdomain($connection, $data->subdomain)) {
            return;
        }

        $connectionInfo = $this->connectionManager->storeConnection($data->host, $data->subdomain, $connection);

        $connection->send(json_encode([
            'event' => 'authenticated',
            'subdomain' => $connectionInfo->subdomain,
            'client_id' => $connectionInfo->client_id
        ]));
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

    protected function verifyAuthToken(ConnectionInterface $connection)
    {
        $authToken = QueryParameters::create($connection->httpRequest)->get('authToken');

        $this->database
            ->query("SELECT * FROM users WHERE auth_token = :token", ['token' => $authToken])
            ->then(function (Result $result) use ($connection) {
                if (count($result->rows) === 0) {
                    $connection->send(json_encode([
                        'event' => 'authenticationFailed',
                        'data' => []
                    ]));
                    $connection->close();
                }
        });
    }

    protected function hasValidSubdomain(ConnectionInterface $connection, ?string $subdomain): bool
    {
        if (! is_null($subdomain)) {
            $controlConnection = $this->connectionManager->findControlConnectionForSubdomain($subdomain);
            if (! is_null($controlConnection) || $subdomain === config('expose.dashboard_subdomain')) {
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
