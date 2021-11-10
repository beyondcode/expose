<?php

namespace App\Contracts;

use React\Promise\PromiseInterface;

interface LoggerRepository
{
    public function logSubdomain($authToken, $subdomain);

    public function getLogs(): PromiseInterface;

    public function getLogsBySubdomain($subdomain): PromiseInterface;
}
