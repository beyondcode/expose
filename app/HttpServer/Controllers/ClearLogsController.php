<?php

namespace App\HttpServer\Controllers;

use App\Client\TunnelConnection;
use App\HttpServer\QueryParameters;
use App\Logger\RequestLogger;
use GuzzleHttp\Psr7\Response;
use Ratchet\ConnectionInterface;
use function GuzzleHttp\Psr7\str;
use Psr\Http\Message\RequestInterface;

class ClearLogsController extends Controller
{
    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        /** @var RequestLogger $logger */
        $logger = app(RequestLogger::class);
        $logger->clear();

        $connection->send(
            str(new Response(
                200,
                ['Content-Type' => 'application/json'],
                ''
            ))
        );

        $connection->close();
    }
}
