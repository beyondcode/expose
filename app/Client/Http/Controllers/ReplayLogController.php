<?php

namespace App\Client\Http\Controllers;

use App\Client\Http\HttpClient;
use App\Http\Controllers\Controller;
use App\Logger\RequestLogger;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
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
            $httpConnection->send(Message::toString(new Response(404)));

            return;
        }

        $loggedRequest->refreshId();

        $this->httpClient->performRequest($loggedRequest->getRequest()->toString());

        $httpConnection->send(Message::toString(new Response(200)));
    }
}
