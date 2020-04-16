<?php

namespace App\HttpServer\Controllers;

use Illuminate\Http\Request;
use App\Logger\RequestLogger;

class AttachDataToLogController extends PostController
{
    public function handle(Request $request)
    {
        /** @var RequestLogger $requestLogger */
        $requestLogger = app(RequestLogger::class);
        $loggedRequest = $requestLogger->findLoggedRequest($request->get('request_id', ''));

        if (! is_null($loggedRequest)) {
            $loggedRequest->setAdditionalData((array)$request->get('data', []));

            $requestLogger->pushLogs();
        }
    }
}
