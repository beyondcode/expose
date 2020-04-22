<?php

namespace App\HttpServer\Controllers;

use App\Logger\RequestLogger;
use GuzzleHttp\Psr7\Response;
use Ratchet\ConnectionInterface;
use function GuzzleHttp\Psr7\str;
use Psr\Http\Message\RequestInterface;

class LogController extends Controller
{
    /** @var RequestLogger */
    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        $connection->send(
            str(new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($this->requestLogger->getData(), JSON_INVALID_UTF8_IGNORE)
            ))
        );

        $connection->close();
    }
}
