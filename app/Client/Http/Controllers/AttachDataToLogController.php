<?php

namespace App\Client\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Logger\RequestLogger;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

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
            $loggedRequest->setAdditionalData((array) $request->get('data', []));

            $this->requestLogger->pushLoggedRequest($loggedRequest);

            $httpConnection->send(str(new Response(200)));

            return;
        }

        $httpConnection->send(str(new Response(404)));
    }
}
