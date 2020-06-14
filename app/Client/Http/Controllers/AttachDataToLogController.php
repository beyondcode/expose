<?php

namespace App\Client\Http\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use App\Logger\RequestLogger;
use Ratchet\ConnectionInterface;
use function GuzzleHttp\Psr7\str;

class AttachDataToLogController extends Controller
{
    /** @var RequestLogger */
    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $loggedRequest = $this->requestLogger->findLoggedRequest($request->get('request_id', ''));

        if (! is_null($loggedRequest)) {
            $loggedRequest->setAdditionalData((array)$request->get('data', []));

            $this->requestLogger->pushLoggedRequest($loggedRequest);

            $httpConnection->send(str(new Response(200)));
            return;
        }

        $httpConnection->send(str(new Response(404)));
    }
}
