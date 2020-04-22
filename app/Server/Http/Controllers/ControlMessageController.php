<?php

namespace App\Server\Http\Controllers;

use App\Contracts\ConnectionManager;
use stdClass;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class ControlMessageController implements MessageComponentInterface
{

    /** @var ConnectionManager */
    protected $connectionManager;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
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
    function onOpen(ConnectionInterface $conn)
    {
        //
    }

    /**
     * @inheritDoc
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        //
    }
}
