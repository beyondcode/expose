<?php

namespace App\Client;

use App\Client\Connections\ControlConnection;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use function Ratchet\Client\connect;

class Client
{
    /** @var LoopInterface */
    protected $loop;

    /** @var Configuration */
    protected $configuration;

    public static $subdomains = [];

    public function __construct(LoopInterface $loop, Configuration $configuration)
    {
        $this->loop = $loop;
        $this->configuration = $configuration;
    }

    public function share(string $sharedUrl, array $subdomains = [])
    {
        foreach ($subdomains as $subdomain) {
            $this->connectToServer($sharedUrl, $subdomain);
        }
    }

    protected function connectToServer(string $sharedUrl, $subdomain)
    {
        connect("ws://{$this->configuration->host()}:{$this->configuration->port()}/__expose_control__", [], [
            'X-Expose-Control' => 'enabled',
        ], $this->loop)
            ->then(function (WebSocket $clientConnection) use ($sharedUrl, $subdomain) {
                $connection = ControlConnection::create($clientConnection);

                $connection->authenticate($sharedUrl, $subdomain);

                $connection->on('authenticated', function ($data) {
                    dump("Connected to http://$data->subdomain.{$this->configuration->host()}:{$this->configuration->port()}");
                    static::$subdomains[] = "$data->subdomain.{$this->configuration->host()}:{$this->configuration->port()}";
                });

            }, function ($e) {
                echo "Could not connect: {$e->getMessage()}\n";
            });
    }
}
