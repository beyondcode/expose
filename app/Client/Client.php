<?php

namespace App\Client;

use App\Client\Connections\ControlConnection;
use App\Logger\CliRequestLogger;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use function Ratchet\Client\connect;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class Client
{
    protected const MAX_CONNECTION_RETRIES = 3;

    /** @var LoopInterface */
    protected $loop;

    /** @var Configuration */
    protected $configuration;

    /** @var CliRequestLogger */
    protected $logger;

    /** @var int */
    protected $connectionRetries = 0;

    /** @var int */
    protected $timeConnected = 0;

    /** @var bool */
    protected $shouldExit = true;

    public static $user = [];
    public static $subdomains = [];

    public function __construct(LoopInterface $loop, Configuration $configuration, CliRequestLogger $logger)
    {
        $this->loop = $loop;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    public function shouldExit($shouldExit = true)
    {
        $this->shouldExit = $shouldExit;
    }

    public function share(string $sharedUrl, array $subdomains = [], $serverHost = null)
    {
        $sharedUrl = $this->prepareSharedUrl($sharedUrl);

        foreach ($subdomains as $subdomain) {
            $this->connectToServer($sharedUrl, $subdomain, $serverHost, $this->configuration->auth());
        }
    }

    public function sharePort(int $port)
    {
        $this->connectToServerAndShareTcp($port, $this->configuration->auth());
    }

    protected function prepareSharedUrl(string $sharedUrl): string
    {
        if (! $parsedUrl = parse_url($sharedUrl)) {
            return $sharedUrl;
        }

        $url = Arr::get($parsedUrl, 'host', Arr::get($parsedUrl, 'path'));

        $isHttps = Arr::get($parsedUrl, 'scheme') === 'https';

        $this->configuration->setIsSecureSharedUrl($isHttps);

        if ($isHttps && is_null(Arr::get($parsedUrl, 'port'))) {
            $url .= ':443';
        }

        if (! is_null($port = Arr::get($parsedUrl, 'port'))) {
            $url .= ":{$port}";
        }

        return $url;
    }

    public function connectToServer(string $sharedUrl, $subdomain, $serverHost = null, $authToken = ''): PromiseInterface
    {
        $deferred = new Deferred();
        $promise = $deferred->promise();

        $exposeVersion = config('app.version');

        $wsProtocol = $this->configuration->port() === 443 ? 'wss' : 'ws';

        connect($wsProtocol."://{$this->configuration->host()}:{$this->configuration->port()}/expose/control?authToken={$authToken}&version={$exposeVersion}", [], [
            'X-Expose-Control' => 'enabled',
        ], $this->loop)
            ->then(function (WebSocket $clientConnection) use ($sharedUrl, $subdomain, $serverHost, $deferred, $authToken) {
                $this->connectionRetries = 0;

                $connection = ControlConnection::create($clientConnection);

                $connection->authenticate($sharedUrl, $subdomain, $serverHost);

                $clientConnection->on('close', function () use ($sharedUrl, $subdomain, $serverHost, $authToken) {
                    $this->logger->error('Connection to server closed.');

                    $this->retryConnectionOrExit(function () use ($sharedUrl, $subdomain, $serverHost, $authToken) {
                        $this->connectToServer($sharedUrl, $subdomain, $serverHost, $authToken);
                    });
                });

                $this->attachCommonConnectionListeners($connection, $deferred);

                $connection->on('subdomainTaken', function ($data) use ($deferred) {
                    $this->logger->error($data->message);

                    $this->exit($deferred);
                });

                $connection->on('authenticated', function ($data) use ($deferred, $sharedUrl) {
                    $httpProtocol = $this->configuration->port() === 443 ? 'https' : 'http';

                    $httpPort = $httpProtocol === 'https' ? '' : ":{$this->configuration->port()}";

                    $host = $data->server_host ?? $this->configuration->host();

                    $this->configuration->setServerHost($host);

                    $this->logger->info($data->message);
                    $this->logger->info("Shared URL:\t\t<options=bold>{$sharedUrl}</>");
                    $this->logger->info("Dashboard:\t\t<options=bold>http://127.0.0.1:".config()->get('expose.dashboard_port').'</>');
                    $this->logger->info("Public HTTP:\t\t<options=bold>http://{$data->subdomain}.{$host}{$httpPort}</>");
                    $this->logger->info("Public HTTPS:\t\t<options=bold>https://{$data->subdomain}.{$host}</>");
                    $this->logger->line('');

                    static::$subdomains[] = "{$httpProtocol}://{$data->subdomain}.{$host}";
                    static::$user = $data->user ?? ['can_specify_subdomains' => 0];

                    $deferred->resolve($data);
                });
            }, function (\Exception $e) use ($deferred, $sharedUrl, $subdomain, $authToken) {
                if ($this->connectionRetries > 0) {
                    $this->retryConnectionOrExit(function () use ($sharedUrl, $subdomain, $authToken) {
                        $this->connectToServer($sharedUrl, $subdomain, $authToken);
                    });

                    return;
                }
                $this->logger->error('Could not connect to the server.');
                $this->logger->error($e->getMessage());

                $this->exit($deferred);
            });

        return $promise;
    }

    public function connectToServerAndShareTcp(int $port, $authToken = ''): PromiseInterface
    {
        $deferred = new Deferred();
        $promise = $deferred->promise();

        $wsProtocol = $this->configuration->port() === 443 ? 'wss' : 'ws';
        $exposeVersion = config('app.version');

        connect($wsProtocol."://{$this->configuration->host()}:{$this->configuration->port()}/expose/control?authToken={$authToken}&version={$exposeVersion}", [], [
            'X-Expose-Control' => 'enabled',
        ], $this->loop)
            ->then(function (WebSocket $clientConnection) use ($port, $deferred, $authToken) {
                $this->connectionRetries = 0;

                $connection = ControlConnection::create($clientConnection);

                $connection->authenticateTcp($port);

                $this->attachCommonConnectionListeners($connection, $deferred);

                $clientConnection->on('close', function () use ($port, $authToken) {
                    $this->logger->error('Connection to server closed.');

                    $this->retryConnectionOrExit(function () use ($port, $authToken) {
                        $this->connectToServerAndShareTcp($port, $authToken);
                    });
                });

                $connection->on('authenticated', function ($data) use ($deferred, $port) {
                    $host = $this->configuration->host();

                    $this->logger->info($data->message);
                    $this->logger->info("Local-Port:\t\t<options=bold>{$port}</>");
                    $this->logger->info("Shared-Port:\t\t<options=bold>{$data->shared_port}</>");
                    $this->logger->info("Expose-URL:\t\t<options=bold>tcp://{$host}:{$data->shared_port}</>");
                    $this->logger->line('');

                    $deferred->resolve($data);
                });
            }, function (\Exception $e) use ($deferred, $port, $authToken) {
                if ($this->connectionRetries > 0) {
                    $this->retryConnectionOrExit(function () use ($port, $authToken) {
                        $this->connectToServerAndShareTcp($port, $authToken);
                    });

                    return;
                }
                $this->logger->error('Could not connect to the server.');
                $this->logger->error($e->getMessage());

                $this->exit($deferred);
            });

        return $promise;
    }

    protected function attachCommonConnectionListeners(ControlConnection $connection, Deferred $deferred)
    {
        $connection->on('info', function ($data) {
            $this->logger->info($data->message);
        });

        $connection->on('warning', function ($data) {
            $this->logger->warn($data->message);
        });

        $connection->on('error', function ($data) {
            $this->logger->error($data->message);
        });

        $connection->on('authenticationFailed', function ($data) use ($deferred) {
            $this->logger->error($data->message);

            $this->exit($deferred);
        });

        $connection->on('setMaximumConnectionLength', function ($data) {
            $timeoutSection = $this->logger->getOutput()->section();

            $this->loop->addPeriodicTimer(1, function () use ($data, $timeoutSection) {
                $this->timeConnected++;

                $secondsRemaining = $data->length * 60 - $this->timeConnected;
                $remaining = Carbon::now()->diff(Carbon::now()->addSeconds($secondsRemaining));

                $timeoutSection->clear();
                $timeoutSection->writeln('Remaining time: '.$remaining->format('%H:%I:%S'));
            });
        });
    }

    protected function exit(Deferred $deferred)
    {
        $deferred->reject();

        $this->loop->futureTick(function () {
            if ($this->shouldExit) {
                exit(1);
            }
        });
    }

    protected function retryConnectionOrExit(callable $retry)
    {
        $this->connectionRetries++;

        if ($this->connectionRetries <= static::MAX_CONNECTION_RETRIES) {
            $this->loop->addTimer($this->connectionRetries, function () use ($retry) {
                $this->logger->info("Retrying connection ({$this->connectionRetries}/".static::MAX_CONNECTION_RETRIES.')');

                $retry();
            });
        } else {
            exit(1);
        }
    }
}
