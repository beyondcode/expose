<?php

namespace App\Contracts;

interface StatisticsCollector
{
    public function siteShared($authToken = null);

    public function portShared($authToken = null);

    public function incomingRequest();

    public function flush();

    public function save();

    public function shouldCollectStatistics(): bool;
}
