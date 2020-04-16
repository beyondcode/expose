<?php

namespace App\HttpServer\Controllers;

use App\Client\Client;
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
                $this->getView()
            ))
        );

        $connection->close();
    }

    protected function getView(): string
    {
        $view = file_get_contents(base_path('resources/views/index.html'));

        $view = str_replace('%subdomains%', implode(' ', Client::$subdomains), $view);

        return $view;
    }
}
