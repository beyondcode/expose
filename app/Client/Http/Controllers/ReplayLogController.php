<?php

namespace App\Client\Http\Controllers;

use App\Client\Http\HttpClient;
use App\Http\Controllers\Controller;
use App\Logger\RequestLogger;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

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

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $loggedRequest = $this->requestLogger->findLoggedRequest($request->get('log'));

        if (is_null($loggedRequest)) {
            $httpConnection->send(str(new Response(404)));

            return;
        }

        $this->httpClient->performRequest($loggedRequest->getRequestData());

        $httpConnection->send(str(new Response(200)));
    }
}
