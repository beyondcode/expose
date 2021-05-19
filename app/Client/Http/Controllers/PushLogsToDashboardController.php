<?php

namespace App\Client\Http\Controllers;

use App\Http\Controllers\Controller;
use App\WebSockets\Socket;
use Exception;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

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

            $httpConnection->send(Message::toString(new Response(200)));
        } catch (Exception $e) {
            $httpConnection->send(Message::toString(new Response(500, [], $e->getMessage())));
        }
    }
}
