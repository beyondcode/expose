<?php

namespace App\Client;

use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;

class Client
{
    /** @var LoopInterface */
    protected $loop;
    protected $host;
    protected $port;
    public static $subdomains = [];

    public function __construct(LoopInterface $loop, $host, $port)
    {
        $this->loop = $loop;
        $this->host = $host;
        $this->port = $port;
    }

    public function share($sharedUrl, array $subdomains = [])
    {
        foreach ($subdomains as $subdomain) {
            $connector = new Connector($this->loop);

            $connector->connect("{$this->host}:{$this->port}")
                ->then(function (ConnectionInterface $clientConnection) use ($sharedUrl, $subdomain) {
                    $connection = Connection::create($clientConnection, new ProxyManager($this->host, $this->port, $this->loop));
                    $connection->authenticate($sharedUrl, $subdomain);

                    $clientConnection->on('authenticated', function ($data) {
                        static::$subdomains[] = "$data->subdomain.{$this->host}:{$this->port}";
                        dump("Connected to http://$data->subdomain.{$this->host}:{$this->port}");
                    });
                });
        }
    }
}
