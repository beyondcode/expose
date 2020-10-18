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

    /** @var bool|null */
    protected $ssl;

    public function __construct(string $host, int $port, ?string $auth = null, $ssl = null)
    {
        $this->host = $host;

        $this->port = $port;

        $this->auth = $auth;

        $this->ssl = $ssl;
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
        return intval($this->port);
    }

    public function ssl(): bool
    {
        if ($this->ssl === null) {
            return $this->port() === 443;
        }

        return boolval($this->ssl);
    }
}
