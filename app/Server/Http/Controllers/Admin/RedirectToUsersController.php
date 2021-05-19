<?php

namespace App\Server\Http\Controllers\Admin;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;

class RedirectToUsersController extends AdminController
{
    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(Message::toString(new Response(301, [
            'Location' => '/sites',
        ])));
    }
}
