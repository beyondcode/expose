<?php

namespace App\Server\Messages;

use App\Server\Connections\Connection;
use App\Server\Connections\ConnectionManager;
use App\Server\Connections\IoConnection;
use BFunky\HttpParser\HttpRequestParser;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use React\Stream\Util;
use function GuzzleHttp\Psr7\parse_request;

class TunnelMessage implements Message
{
    /** string */
    protected $payload;

    /** @var \Ratchet\ConnectionInterface */
    protected $connection;

    /** @var ConnectionManager */
    private $connectionManager;

    public function __construct($payload, ConnectionInterface $connection, ConnectionManager $connectionManager)
    {
        $this->payload = $payload;

        $this->connection = $connection;

        $this->connectionManager = $connectionManager;
    }

    public function respond()
    {
        $clientConnection = $this->connectionManager->findConnectionForSubdomain($this->detectSubdomain());

        if (is_null($clientConnection)) {
            return;
        }

        if ($this->hasBufferedAllData()) {
            $this->copyDataToClient($clientConnection);
        }
    }

    protected function getContentLength(): ?int
    {
        $request = parse_request($this->connection->buffer);

        return Arr::first($request->getHeader('Content-Length'));
    }

    protected function detectSubdomain(): ?string
    {
        $subdomain = '';

        $headers = collect(explode("\r\n", $this->connection->buffer))->map(function ($header) use (&$subdomain) {
            $headerData = explode(':', $header);
            if ($headerData[0] === 'Host') {
                $domainParts = explode('.', $headerData[1]);
                $subdomain = trim($domainParts[0]);
            }
        });

        return $subdomain;
    }

    private function copyDataToClient(Connection $clientConnection)
    {
        $data = $clientConnection->rewriteHostInformation($this->connectionManager->host(), $this->connectionManager->port(), $this->connection->buffer);

        $requestId = uniqid();

        // Ask client to create a new proxy
        $clientConnection->socket->send(json_encode([
                'event' => 'createProxy',
                'request_id' => $requestId,
                'client_id' => $clientConnection->client_id,
            ]) . "||");

        $clientConnection->socket->getConnection()->once('proxy_ready_' . $requestId, function (IoConnection $proxy) use ($data, $requestId) {
            Util::pipe($proxy->getConnection(), $this->connection->getConnection());

            $proxy->send($data);
        });

        unset($this->connection->buffer);
    }

    protected function hasBufferedAllData()
    {
        return is_null($this->getContentLength()) || strlen(Str::after($this->connection->buffer, "\r\n\r\n")) === $this->getContentLength();
    }
}
