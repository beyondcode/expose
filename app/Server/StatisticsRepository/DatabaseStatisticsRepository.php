<?php

namespace App\Server\StatisticsRepository;

use App\Contracts\StatisticsRepository;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class DatabaseStatisticsRepository implements StatisticsRepository
{
    /** @var DatabaseInterface */
    protected $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    public function getStatistics($from, $until): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT
                timestamp,
                SUM(shared_sites) as shared_sites,
                SUM(shared_ports) as shared_ports,
                SUM(unique_shared_sites) as unique_shared_sites,
                SUM(unique_shared_ports) as unique_shared_ports,
                SUM(incoming_requests) as incoming_requests
                FROM statistics
                WHERE
                `timestamp` >= "'.$from.'" AND `timestamp` <= "'.$until.'"')
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }
}
