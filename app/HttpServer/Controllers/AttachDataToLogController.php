<?php

namespace App\HttpServer\Controllers;

use Illuminate\Http\Request;
use App\Logger\RequestLogger;
use Ratchet\ConnectionInterface;

class AttachDataToLogController extends PostController
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

            $this->requestLogger->pushLogs();
        }
    }
}
