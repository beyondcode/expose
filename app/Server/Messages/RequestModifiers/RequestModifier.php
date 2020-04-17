<?php

namespace App\Server\Messages\RequestModifiers;

use App\Server\Connections\Connection;
use App\Server\Connections\ConnectionManager;
use GuzzleHttp\Psr7\Request;

interface RequestModifier
{
    public function modify(Request $request, string $requestId, Connection $clientConnection, ConnectionManager $connectionManager): Request;
}
