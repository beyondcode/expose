<?php

namespace App\Server\Messages\RequestModifiers;

use App\Server\Connections\Connection;
use App\Server\Connections\ConnectionManager;
use Psr\Http\Message\RequestInterface;

interface RequestModifier
{
    public function modify(RequestInterface $request, string $requestId, Connection $clientConnection, ConnectionManager $connectionManager): RequestInterface;
}
