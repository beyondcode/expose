<?php

namespace App\Client;

use App\Client\Connections\ControlConnection;
use App\Logger\CliRequestLogger;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function Ratchet\Client\connect;

class Client
{
    protected const MAX_CONNECTION_RETRIES = 3;

    /** @var LoopInterface */
    protected $loop;

    /** @var Configuration */
    protected $configuration;

    /** @var CliRequestLogger */
    protected $logger;

    /** @var int  */
    protected $connectionRetries = 0;

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

        $sharedUrl = $this->prepareSharedUrl($sharedUrl);

        foreach ($subdomains as $subdomain) {
            $this->connectToServer($sharedUrl, $subdomain, config('expose.auth_token'));
        }
    }

    protected function prepareSharedUrl(string $sharedUrl): string
    {
        if (! $parsedUrl = parse_url($sharedUrl)) {
            return $sharedUrl;
        }

        $url = Arr::get($parsedUrl, 'host', Arr::get($parsedUrl, 'path'));

        if (Arr::get($parsedUrl, 'scheme') === 'https') {
            $url .= ':443';
        }

        return $url;
    }

    public function connectToServer(string $sharedUrl, $subdomain, $authToken = ''): PromiseInterface
    {
        $deferred = new \React\Promise\Deferred();
        $promise = $deferred->promise();

        $wsProtocol = $this->configuration->port() === 443 ? "wss" : "ws";

        connect($wsProtocol."://{$this->configuration->host()}:{$this->configuration->port()}/expose/control?authToken={$authToken}", [], [
            'X-Expose-Control' => 'enabled',
        ], $this->loop)
            ->then(function (WebSocket $clientConnection) use ($sharedUrl, $subdomain, $deferred, $authToken) {
                $this->connectionRetries = 0;

                $connection = ControlConnection::create($clientConnection);

                $connection->authenticate($sharedUrl, $subdomain);

                $clientConnection->on('close', function() use ($deferred, $sharedUrl, $subdomain, $authToken) {
                    $this->logger->error('Connection to server closed.');

                    $this->retryConnectionOrExit($sharedUrl, $subdomain, $authToken);
                });

                $connection->on('authenticationFailed', function ($data) use ($deferred) {
                    $this->logger->error($data->message);

                    $this->exit($deferred);
                });

                $connection->on('subdomainTaken', function ($data) use ($deferred) {
                    $this->logger->error($data->message);

                    $this->exit($deferred);
                });

                $connection->on('setMaximumConnectionLength', function ($data) {
                    $timeoutSection = $this->logger->getOutput()->section();

                    $this->loop->addPeriodicTimer(1, function() use ($data, $timeoutSection) {
                        $this->timeConnected++;

                        $carbon = Carbon::createFromFormat('s', str_pad($data->length * 60 - $this->timeConnected, 2, 0, STR_PAD_LEFT));

                        $timeoutSection->clear();
                        $timeoutSection->writeln('Remaining time: '.$carbon->format('H:i:s'));
                    });
                });

                $connection->on('authenticated', function ($data) use ($deferred) {
                    $httpProtocol = $this->configuration->port() === 443 ? "https" : "http";
                    $host = $this->configuration->host();

                    if ($httpProtocol !== 'https') {
                        $host .= ":{$this->configuration->port()}";
                    }

                    $this->logger->info($data->message);
                    $this->logger->info("Connected to {$httpProtocol}://{$data->subdomain}.{$host}");

                    static::$subdomains[] = "{$httpProtocol}://{$data->subdomain}.{$host}";

                    $deferred->resolve($data);
                });

            }, function (\Exception $e) use ($deferred, $sharedUrl, $subdomain, $authToken) {
                if ($this->connectionRetries > 0) {
                    $this->retryConnectionOrExit($sharedUrl, $subdomain, $authToken);
                    return;
                }
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

    protected function retryConnectionOrExit(string $sharedUrl, $subdomain, $authToken = '')
    {
        $this->connectionRetries++;

        if ($this->connectionRetries <= static::MAX_CONNECTION_RETRIES) {
            $this->loop->addTimer($this->connectionRetries, function() use ($sharedUrl, $subdomain, $authToken) {
                $this->logger->info("Retrying connection ({$this->connectionRetries}/".static::MAX_CONNECTION_RETRIES.")");

                $this->connectToServer($sharedUrl, $subdomain, $authToken);
            });
        } else {
            exit(1);
        }
    }
}
