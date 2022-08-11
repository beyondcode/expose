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

    /** @var string|null */
    protected $basicAuth;

    /** @var bool */
    protected $isSecureSharedUrl = false;

    public function __construct(string $host, int $port, ?string $auth = null, ?string $basicAuth = null)
    {
        $this->serverHost = $this->host = $host;

        $this->port = $port;

        $this->auth = $auth;

        $this->basicAuth = $basicAuth;
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

    public function basicAuth(): ?string
    {
        return $this->basicAuth;
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

    public function isSecureSharedUrl(): bool
    {
        return $this->isSecureSharedUrl;
    }

    public function setIsSecureSharedUrl($value)
    {
        $this->isSecureSharedUrl = $value;
    }
}
