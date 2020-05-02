<?php

namespace App\Client;

use App\Client\Connections\ControlConnection;
use App\Logger\CliRequestLogger;
use Carbon\Carbon;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function Ratchet\Client\connect;

class Client
{
    /** @var LoopInterface */
    protected $loop;

    /** @var Configuration */
    protected $configuration;

    /** @var CliRequestLogger */
    protected $logger;

    /** @var int */
    protected $timeConnected = 0;

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
            $this->connectToServer($sharedUrl, $subdomain, config('expose.auth_token'));
        }
    }

    public function connectToServer(string $sharedUrl, $subdomain, $authToken = ''): PromiseInterface
    {
        $deferred = new \React\Promise\Deferred();
        $promise = $deferred->promise();

        $wsProtocol = $this->configuration->port() === 443 ? "wss" : "ws";

        connect($wsProtocol."://{$this->configuration->host()}:{$this->configuration->port()}/expose/control?authToken={$authToken}", [], [
            'X-Expose-Control' => 'enabled',
        ], $this->loop)
            ->then(function (WebSocket $clientConnection) use ($sharedUrl, $subdomain, $deferred) {
                $connection = ControlConnection::create($clientConnection);

                $connection->authenticate($sharedUrl, $subdomain);

                $clientConnection->on('close', function() use ($deferred) {
                    $this->logger->error('Connection to server closed.');

                    $this->exit($deferred);
                });

                $connection->on('authenticationFailed', function ($data) use ($deferred) {
                    $this->logger->error("Authentication failed. Please check your authentication token and try again.");

                    $this->exit($deferred);
                });

                $connection->on('subdomainTaken', function ($data) use ($deferred) {
                    $this->logger->error("The chosen subdomain \"{$data->data->subdomain}\" is already taken. Please choose a different subdomain.");

                    $this->exit($deferred);
                });

                $connection->on('setMaximumConnectionLength', function ($data) {
                    $this->loop->addPeriodicTimer(1, function() use ($data) {
                        $this->timeConnected++;

                        $carbon = Carbon::createFromFormat('s', str_pad($data->length * 60 - $this->timeConnected, 2, 0, STR_PAD_LEFT));

                        $this->logger->info('Remaining time: '.$carbon->format('H:i:s'));
                    });
                });

                $connection->on('authenticated', function ($data) use ($deferred) {
                    $httpProtocol = $this->configuration->port() === 443 ? "https" : "http";
                    $host = $this->configuration->host();

                    if ($httpProtocol !== 'https') {
                        $host .= ":{$this->configuration->port()}";
                    }

                    $this->logger->info("Connected to {$httpProtocol}://{$data->subdomain}.{$host}");

                    static::$subdomains[] = "$data->subdomain.{$this->configuration->host()}:{$this->configuration->port()}";

                    $deferred->resolve();
                });

            }, function (\Exception $e) use ($deferred) {
                $this->logger->error("Could not connect to the server.");
                $this->logger->error($e->getMessage());

                $this->exit($deferred);
            });

        return $promise;
    }

    protected function exit(Deferred $deferred)
    {
        $deferred->reject();

        $this->loop->futureTick(function(){
            exit(1);
        });
    }
}
