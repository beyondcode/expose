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

        $protocol = $this->configuration->port() === 443 ? "wss" : "ws";

        connect($protocol."://{$this->configuration->host()}:{$this->configuration->port()}/__expose_control__?authToken={$token}", [], [
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
                    $this->logger->info("Connected to http://$data->subdomain.{$this->configuration->host()}:{$this->configuration->port()}");

                    static::$subdomains[] = "$data->subdomain.{$this->configuration->host()}:{$this->configuration->port()}";
                });

            }, function (\Exception $e) {
                $this->logger->error("Could not connect to the server.");
                $this->logger->error($e->getMessage());
                exit(1);
            });
    }
}
