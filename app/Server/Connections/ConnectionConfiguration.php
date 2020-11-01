<?php

namespace App\Server\Connections;

class ConnectionConfiguration
{
    protected $hostname;
    protected $subdomain;

    private function __construct($subdomain, $hostname)
    {
        $this->subdomain = $subdomain;
        $this->hostname = $hostname;
    }

    public static function withSubdomain($subdomain)
    {
        return new static($subdomain, null);
    }

    public static function withHostname($hostname)
    {
        return new static(null, $hostname);
    }

    public function getSubdomain()
    {
        return $this->subdomain;
    }

    public function getHostname()
    {
        return $this->hostname;
    }
}
