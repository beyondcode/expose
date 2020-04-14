<?php

namespace App\HttpServer\Controllers;

use App\Logger\RequestLogger;
use GuzzleHttp\Psr7\Response;
use Ratchet\ConnectionInterface;
use function GuzzleHttp\Psr7\str;
use Psr\Http\Message\RequestInterface;

class LogController extends Controller
{
    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        /** @var RequestLogger $logger */
        $logger = app(RequestLogger::class);

        $connection->send(
            str(new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($logger->getData(), JSON_INVALID_UTF8_IGNORE)
            ))
        );

        $connection->close();
    }
}
