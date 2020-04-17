<?php

namespace App\Server\Messages;

use App\Server\Connections\Connection;
use App\Server\Connections\ConnectionManager;
use App\Server\Connections\HttpRequestConnection;
use App\Server\Connections\IoConnection;
use App\Server\Messages\RequestModifiers\ModifyHostHeader;
use BFunky\HttpParser\HttpRequestParser;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use React\Stream\Util;
use function GuzzleHttp\Psr7\parse_request;

class TunnelMessage implements Message
{
    /** @var HttpRequestConnection */
    protected $connection;

    /** @var ConnectionManager */
    private $connectionManager;

    protected $requestModifiers = [
        ModifyHeaders::class,
    ];

    public function __construct(HttpRequestConnection $connection, ConnectionManager $connectionManager)
    {
        $this->connection = $connection;

        $this->connectionManager = $connectionManager;
    }

    public function respond()
    {
        if ($this->connection->hasBufferedAllData()) {
            $clientConnection = $this->connectionManager->findConnectionForSubdomain($this->detectSubdomain());

            if (is_null($clientConnection)) {
                $this->connection->send(\GuzzleHttp\Psr7\str(new Response(404, [], 'Not found')));
                $this->connection->close();
                return;
            }

            $this->copyDataToClient($clientConnection);
        }
    }

    protected function detectSubdomain(): ?string
    {
        $host = $this->connection->getRequest()->getHeader('Host')[0];

        $domainParts = explode('.', $host);

        return trim($domainParts[0]);
    }

    protected function passRequestThroughModifiers(string $requestId, Request $request, Connection $clientConnection): Request
    {
        foreach ($this->requestModifiers as $requestModifier) {
            $request = app($requestModifier)->modify($request, $requestId, $clientConnection, $this->connectionManager);
        }

        return $request;
    }

    protected function copyDataToClient(Connection $clientConnection)
    {
        $requestId = uniqid();

        $request = $this->passRequestThroughModifiers($requestId, $this->connection->getRequest(), $clientConnection);

        $clientConnection->pipeRequestThroughProxy($this->connection, $requestId, $request);

        unset($this->connection->buffer);
    }
}
