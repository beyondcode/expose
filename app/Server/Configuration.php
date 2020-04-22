<?php

namespace App\Server;

class Configuration
{
    /** @var string */
    protected $hostname;

    /** @var int */
    protected $port;

    public function __construct(string $hostname, int $port)
    {
        $this->hostname = $hostname;

        $this->port = $port;
    }

    public function hostname(): string
    {
        return $this->hostname;
    }

    public function port(): int
    {
        return $this->port;
    }
}
