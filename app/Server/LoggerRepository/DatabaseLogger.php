<?php

namespace App\Server\LoggerRepository;

use App\Contracts\LoggerRepository;
use App\Contracts\UserRepository;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class DatabaseLogger implements LoggerRepository
{
    /** @var DatabaseInterface */
    protected $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    public function logSubdomain($authToken, $subdomain)
    {
        app(UserRepository::class)->getUserByToken($authToken)
            ->then(function ($user) use ($subdomain) {
                $this->database->query("
                    INSERT INTO logs (user_id, subdomain, created_at)
                    VALUES (:user_id, :subdomain, DATETIME('now'))
                ", [
                    'user_id' => $user['id'],
                    'subdomain' => $subdomain,
                ])->then(function () {
                    $this->cleanOldLogs();
                });
            });
    }

    public function cleanOldLogs()
    {
        $this->database->query("DELETE FROM logs WHERE created_at < date('now', '-30 day')");
    }

    public function getLogsBySubdomain($subdomain): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('
                SELECT
                       logs.id AS log_id,
                       logs.subdomain,
                       users.*
                FROM logs
                INNER JOIN users
                ON users.id = logs.user_id
                WHERE logs.subdomain = :subdomain', ['subdomain' => $subdomain])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function getLogs(): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('
                SELECT
                       logs.id AS log_id,
                       logs.subdomain,
                       users.*
                FROM logs
                INNER JOIN users
                ON users.id = logs.user_id')
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }
}
