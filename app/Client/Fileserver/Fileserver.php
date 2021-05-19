<?php

namespace App\Client\Fileserver;

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\Server;
use React\Socket\Server as SocketServer;

class Fileserver
{
    /** @var SocketServer */
    protected $socket;

    public function __construct($rootFolder, $name, $port, $address, LoopInterface $loop)
    {
        $server = new Server($loop, function (ServerRequestInterface $request) use ($rootFolder, $name, $loop) {
            return (new ConnectionHandler($rootFolder, $name, $loop))->handle($request);
        });

        $this->socket = new SocketServer("{$address}:{$port}", $loop);

        $server->listen($this->socket);
    }

    public function getSocket(): SocketServer
    {
        return $this->socket;
    }
}
