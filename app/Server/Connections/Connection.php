<?php

namespace App\Server\Connections;

use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;

class Connection
{
    /** @var IoConnection */
    public $socket;
    public $host;
    public $subdomain;
    public $client_id;
    public $proxies = [];

    public function __construct(IoConnection $socket, string $host, string $subdomain, string $clientId)
    {
        $this->socket = $socket;
        $this->host = $host;
        $this->subdomain = $subdomain;
        $this->client_id = $clientId;
    }

    public function setProxy(ConnectionInterface $proxy)
    {
        $this->proxies[] = $proxy;
    }

    public function getProxy(): ?ConnectionInterface
    {
        return array_pop($this->proxies);
    }

    public function rewriteHostInformation($serverHost, $port, $requestId, string $data)
    {
        $appName = config('app.name');
        $appVersion = config('app.version');

        $originalHost = "{$this->subdomain}.{$serverHost}:{$port}";

        $data = preg_replace('/Host: '.$this->subdomain.'.'.$serverHost.'(.*)\r\n/', "Host: {$this->host}\r\n" .
            "X-Exposed-By: {$appName} {$appVersion}\r\n" .
            "X-Expose-Request-ID: {$requestId}\r\n" .
            "X-Original-Host: {$originalHost}\r\n", $data);

        return $data;
    }
}
