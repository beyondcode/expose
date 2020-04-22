<?php

namespace App\HttpServer\Controllers;

use App\Client\Http\HttpClient;
use App\HttpServer\QueryParameters;
use App\Logger\RequestLogger;
use GuzzleHttp\Psr7\Response;
use Ratchet\ConnectionInterface;
use function GuzzleHttp\Psr7\str;
use Psr\Http\Message\RequestInterface;

class ClearLogsController extends Controller
{
    /** @var RequestLogger */
    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        $this->requestLogger->clear();

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
