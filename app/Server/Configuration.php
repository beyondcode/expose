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

    public function __isset($key)
    {
        return property_exists($this, $key) || ! is_null(config('expose.admin.'.$key));
    }

    public function __get($key)
    {
        return $this->$key ?? config('expose.admin.'.$key);
    }
}
