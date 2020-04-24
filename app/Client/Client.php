<?php

namespace App\Client;

use App\Client\Connections\ControlConnection;
use App\Logger\CliRequestLogger;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use function Ratchet\Client\connect;

class Client
{
    /** @var LoopInterface */
    protected $loop;

    /** @var Configuration */
    protected $configuration;

    /** @var CliRequestLogger */
    protected $logger;

    public static $subdomains = [];

    public function __construct(LoopInterface $loop, Configuration $configuration, CliRequestLogger $logger)
    {
        $this->loop = $loop;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    public function share(string $sharedUrl, array $subdomains = [])
    {
        $this->logger->info("Sharing http://{$sharedUrl}");

        foreach ($subdomains as $subdomain) {
            $this->connectToServer($sharedUrl, $subdomain);
        }
    }

    protected function connectToServer(string $sharedUrl, $subdomain)
    {
        connect("ws://{$this->configuration->host()}:{$this->configuration->port()}/__expose_control__?authToken={$this->configuration->authToken()}", [], [
            'X-Expose-Control' => 'enabled',
        ], $this->loop)
            ->then(function (WebSocket $clientConnection) use ($sharedUrl, $subdomain) {
                $connection = ControlConnection::create($clientConnection);

                $connection->authenticate($sharedUrl, $subdomain);

                $connection->on('authenticationFailed', function ($data) {
                    $this->logger->error("Authentication failed. Please check your authentication token and try again.");
                    exit(1);
                });

                $connection->on('authenticated', function ($data) {
                    $this->logger->info("Connected to http://$data->subdomain.{$this->configuration->host()}:{$this->configuration->port()}");

                    static::$subdomains[] = "$data->subdomain.{$this->configuration->host()}:{$this->configuration->port()}";
                });

            }, function ($e) {
                echo "Could not connect: {$e->getMessage()}\n";
            });
    }
}
