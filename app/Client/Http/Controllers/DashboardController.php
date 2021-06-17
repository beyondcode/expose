<?php

namespace App\Client\Http\Controllers;

use App\Client\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class DashboardController extends Controller
{
    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(respond_html($this->getView($httpConnection, 'client.dashboard', [
            'user' => Client::$user,
            'subdomains' => Client::$subdomains,
            'max_logs'=> config()->get('expose.max_logged_requests', 10),
        ])));
    }
}
