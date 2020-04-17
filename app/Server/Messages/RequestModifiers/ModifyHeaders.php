<?php

namespace App\Server\Messages\RequestModifiers;

use App\Server\Connections\Connection;
use App\Server\Connections\ConnectionManager;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use function GuzzleHttp\Psr7\modify_request;

class ModifyHeaders implements RequestModifier
{
    public function modify(RequestInterface $request, string $requestId, Connection $clientConnection, ConnectionManager $connectionManager): RequestInterface
    {
        return modify_request($request, [
            'set_headers' => [
                'Host' => $clientConnection->host,
                'X-Expose-Request-ID' => $requestId,
                'X-Exposed-By' => config('app.name') . ' '. config('app.version'),
                'X-Original-Host' => "{$clientConnection->subdomain}.{$connectionManager->host()}:{$connectionManager->port()}",
            ]
        ]);
    }
}
