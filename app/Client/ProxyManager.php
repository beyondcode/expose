<?php

namespace App\Client;

use App\Logger\RequestLogger;
use BFunky\HttpParser\HttpRequestParser;
use BFunky\HttpParser\HttpResponseParser;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Stream\ThroughStream;
use React\Stream\Util;
use React\Stream\WritableResourceStream;
use GuzzleHttp\Psr7 as gPsr;
use function GuzzleHttp\Psr7\parse_request;

class ProxyManager
{
    private $host;
    private $port;
    private $loop;

    public function __construct($host, $port, $loop)
    {
        $this->host = $host;
        $this->port = $port;
        $this->loop = $loop;
    }

    public function createProxy(ConnectionInterface $clientConnection, $connectionData)
    {
        $connector = new Connector($this->loop);
        $connector->connect("{$this->host}:{$this->port}")->then(function (ConnectionInterface $proxyConnection) use ($clientConnection, $connector, $connectionData) {
            $proxyConnection->write(json_encode([
                'event' => 'registerProxy',
                'data' => [
                    'request_id' => $connectionData->request_id ?? null,
                    'client_id' => $clientConnection->_id,
                ],
            ]));

            $proxyConnection->on('data', function ($data) use (&$proxyData, $proxyConnection, $connector) {
                if (!isset($proxyConnection->buffer)) {
                    $proxyConnection->buffer = '';
                }

                $proxyConnection->buffer .= $data;

                if ($this->hasBufferedAllData($proxyConnection)) {
                    $tunnel = app(TunnelConnection::class);

                    $tunnel->performRequest($proxyConnection->buffer, $proxyConnection);
                }
            });
        });
    }

    protected function getContentLength($proxyConnection): ?int
    {
        $request = parse_request($proxyConnection->buffer);

        return Arr::first($request->getHeader('Content-Length'));
    }

    protected function hasBufferedAllData($proxyConnection)
    {
        return is_null($this->getContentLength($proxyConnection)) || strlen(Str::after($proxyConnection->buffer, "\r\n\r\n")) >= $this->getContentLength($proxyConnection);
    }
}
