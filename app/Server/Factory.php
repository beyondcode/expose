<?php

namespace App\Server;

use App\Server\Connections\ConnectionManager;
use React\Socket\Server;
use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as LoopFactory;

class Factory
{
    /** @var string */
    protected $host = '127.0.0.1';

    /** @var string */
    protected $hostname = 'localhost';

    /** @var int */
    protected $port = 8080;

    /** @var \React\EventLoop\LoopInterface */
    protected $loop;

    public function __construct()
    {
        $this->loop = LoopFactory::create();
    }

    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    public function setPort(int $port)
    {
        $this->port = $port;

        return $this;
    }

    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;

        return $this;
    }

    public function setHostname(string $hostname)
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function createServer()
    {
        $socket = new Server("{$this->host}:{$this->port}", $this->loop);

        $connectionManager = new ConnectionManager($this->hostname, $this->port);

        $app = new Expose($connectionManager);

        return new IoServer($app, $socket, $this->loop);
    }

}
