<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LoadsViews;
use App\Http\Controllers\Concerns\ParsesIncomingRequest;
use function GuzzleHttp\Psr7\parse_request;
use Illuminate\Http\Request;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;

abstract class Controller implements HttpServerInterface
{
    use LoadsViews;
    use ParsesIncomingRequest;

    protected $keepConnectionOpen = false;

    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        $connection->contentLength = $this->findContentLength($request->getHeaders());

        $connection->requestBuffer = (string) $request->getBody();

        $connection->request = $request;

        $this->checkContentLength($connection);
    }

    public function onClose(ConnectionInterface $connection)
    {
        unset($connection->laravelRequest);
        unset($connection->requestBuffer);
        unset($connection->contentLength);
        unset($connection->request);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        if (! isset($from->requestBuffer)) {
            $request = parse_request($msg);
            $from->contentLength = $this->findContentLength($request->getHeaders());
            $from->request = $request;
            $from->requestBuffer = (string) $request->getBody();
        } else {
            $from->requestBuffer .= $msg;
        }

        $this->checkContentLength($from);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        //
    }

    abstract public function handle(Request $request, ConnectionInterface $httpConnection);
}
