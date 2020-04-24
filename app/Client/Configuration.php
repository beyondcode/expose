<?php

namespace App\Client;

class Configuration
{
    /** @var string */
    protected $host;

    /** @var int */
    protected $port;

    /** @var string|null */
    protected $auth;

    /** @var string|null */
    protected $authToken;

    public function __construct(string $host, int $port, ?string $auth = null, string $authToken = null)
    {
        $this->host = $host;

        $this->port = $port;

        $this->auth = $auth;

        $this->authToken = $authToken;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function auth(): ?string
    {
        return $this->auth;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function authToken()
    {
        return $this->authToken;
    }
}
