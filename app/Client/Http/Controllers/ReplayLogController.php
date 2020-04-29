<?php

namespace App\Client\Http\Controllers;

use App\Client\Http\HttpClient;
use App\Http\Controllers\Controller;
use App\Http\QueryParameters;
use App\Logger\RequestLogger;
use GuzzleHttp\Psr7\Response;
use Ratchet\ConnectionInterface;
use function GuzzleHttp\Psr7\str;
use Psr\Http\Message\RequestInterface;

class ReplayLogController extends Controller
{
    /** @var RequestLogger */
    protected $requestLogger;

    /** @var HttpClient */
    protected $httpClient;

    public function __construct(RequestLogger $requestLogger, HttpClient $httpClient)
    {
        $this->requestLogger = $requestLogger;
        $this->httpClient = $httpClient;
    }

    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        $loggedRequest = $this->requestLogger->findLoggedRequest(QueryParameters::create($request)->get('log'));

        if (is_null($loggedRequest)) {
            $connection->send(
                str(new Response(
                    404,
                    ['Content-Type' => 'application/json'],
                ))
            );

            $connection->close();
            return;
        }

        $requestData = $loggedRequest->getRequestData();

        /** @var HttpClient $tunnel */
        $this->httpClient->performRequest($requestData);

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
