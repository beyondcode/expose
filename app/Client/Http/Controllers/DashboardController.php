<?php

namespace App\Client\Http\Controllers;

use App\Client\Client;
use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use function GuzzleHttp\Psr7\str;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;

class DashboardController extends Controller
{

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(respond_html($this->getView('client.dashboard', [
            'subdomains' => Client::$subdomains,
        ])));
    }
}
