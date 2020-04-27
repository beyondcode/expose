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
        $token = config('expose.auth_token');

        $wsProtocol = $this->configuration->port() === 443 ? "wss" : "ws";

        connect($wsProtocol."://{$this->configuration->host()}:{$this->configuration->port()}/expose/control?authToken={$token}", [], [
            'X-Expose-Control' => 'enabled',
        ], $this->loop)
            ->then(function (WebSocket $clientConnection) use ($sharedUrl, $subdomain) {
                $connection = ControlConnection::create($clientConnection);

                $connection->authenticate($sharedUrl, $subdomain);

                $clientConnection->on('close', function() {
                    $this->logger->error('Connection to server closed.');
                    exit(1);
                });

                $connection->on('authenticationFailed', function ($data) {
                    $this->logger->error("Authentication failed. Please check your authentication token and try again.");
                    exit(1);
                });

                $connection->on('subdomainTaken', function ($data) {
                    $this->logger->error("The chosen subdomain \"{$data->data->subdomain}\" is already taken. Please choose a different subdomain.");
                    exit(1);
                });

                $connection->on('authenticated', function ($data) {
                    $httpProtocol = $this->configuration->port() === 443 ? "https" : "http";
                    $host = $this->configuration->host();

                    if ($httpProtocol !== 'https') {
                        $host .= ":{$this->configuration->port()}";
                    }

                    $this->logger->info("Connected to {$httpProtocol}://{$data->subdomain}.{$host}");

                    static::$subdomains[] = "$data->subdomain.{$this->configuration->host()}:{$this->configuration->port()}";
                });

            }, function (\Exception $e) {
                $this->logger->error("Could not connect to the server.");
                $this->logger->error($e->getMessage());
                exit(1);
            });
    }
}
