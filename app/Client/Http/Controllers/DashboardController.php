<?php

namespace App\Client\Http\Controllers;

use App\Client\Client;
use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\str;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;

class DashboardController extends Controller
{
    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        $connection->send(
            str(new Response(
                200,
                ['Content-Type' => 'text/html'],
                $this->getView('client.dashboard', [
                    'subdomains' => Client::$subdomains,
                ])
            ))
        );

        $connection->close();
    }
}
