<?php

namespace App\Client;

class Configuration
{
    /** @var string */
    protected $host;

    /** @var string */
    protected $serverHost;

    /** @var int */
    protected $port;

    /** @var string|null */
    protected $auth;

    public function __construct(string $host, int $port, ?string $auth = null)
    {
        $this->serverHost = $this->host = $host;

        $this->port = $port;

        $this->auth = $auth;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function serverHost(): string
    {
        return $this->serverHost;
    }

    public function setServerHost($serverHost)
    {
        $this->serverHost = $serverHost;
    }

    public function auth(): ?string
    {
        return $this->auth;
    }

    public function port(): int
    {
        return intval($this->port);
    }

    public function getUrl(string $subdomain): string
    {
        $httpProtocol = $this->port() === 443 ? 'https' : 'http';
        $host = $this->serverHost();

        if ($httpProtocol !== 'https') {
            $host .= ":{$this->port()}";
        }

        return "{$subdomain}.{$host}";
    }
}
