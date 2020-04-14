<?php

namespace App\Client;

use App\Logger\RequestLogger;
use Laminas\Http\Request;
use Laminas\Http\Response;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Stream\Util;

class TunnelConnection
{
    /** @var LoopInterface */
    protected $loop;

    /** @var RequestLogger */
    protected $logger;
    protected $request;

    public function __construct(LoopInterface $loop, RequestLogger $logger)
    {
        $this->loop = $loop;
        $this->logger = $logger;
    }

    public function performRequest($requestData, ConnectionInterface $proxyConnection = null)
    {
        $this->request = $this->parseRequest($requestData);

        $this->logger->logRequest($requestData, $this->request);

        dump($this->request->getMethod() . ' ' . $this->request->getUri()->getPath());

        if (! is_null($proxyConnection)) {
            $proxyConnection->pause();
        }

        (new Connector($this->loop))
            ->connect("localhost:80")
            ->then(function (ConnectionInterface $connection) use ($requestData, $proxyConnection) {
                $connection->on('data', function ($data) use (&$chunks, &$contentLength, $connection, $proxyConnection) {
                    if (!isset($connection->httpBuffer)) {
                        $connection->httpBuffer = '';
                    }

                    $connection->httpBuffer .= $data;

                    try {
                        $response = $this->parseResponse($connection->httpBuffer);

                        $this->logger->logResponse($this->request, $connection->httpBuffer, $response);

                        unset($connection->httpBuffer);
                    } catch (\Throwable $e) {
                        //
                    }

                });

                if (! is_null($proxyConnection)) {
                    Util::pipe($connection, $proxyConnection, ['end' => true]);
                }

                $connection->write($requestData);

                if (! is_null($proxyConnection)) {
                    $proxyConnection->resume();

                    unset($proxyConnection->buffer);
                }
            });
    }

    protected function parseResponse(string $response)
    {
        try {
            return Response::fromString($response);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function parseRequest($data)
    {
        return Request::fromString($data);
    }
}
