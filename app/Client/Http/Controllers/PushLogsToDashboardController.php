<?php

namespace App\Client\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use App\WebSockets\Socket;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Ratchet\ConnectionInterface;
use function GuzzleHttp\Psr7\str;
use Psr\Http\Message\RequestInterface;

class PushLogsToDashboardController extends Controller
{

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        try {
            /*
             * This is the post payload from our PHPUnit tests.
             * Send it to the connected connections.
             */
            foreach (Socket::$connections as $webSocketConnection) {
                $webSocketConnection->send($request->getContent());
            }

            $httpConnection->send(str(new Response(200)));
        } catch (Exception $e) {
            $httpConnection->send(str(new Response(500, [], $e->getMessage())));
        }
    }
}
