<?php

namespace App\Contracts;

use React\Promise\PromiseInterface;

interface StatisticsRepository
{
    public function getStatistics($from, $until): PromiseInterface;
}
