<?php

namespace App\Server\HostnameRepository;

use App\Contracts\HostnameRepository;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class DatabaseHostnameRepository implements HostnameRepository
{
    /** @var DatabaseInterface */
    protected $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    public function getHostnames(): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM hostnames ORDER by created_at DESC')
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function getHostnameById($id): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM hostnames WHERE id = :id', ['id' => $id])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows[0] ?? null);
            });

        return $deferred->promise();
    }

    public function getHostnameByName(string $name): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM hostnames WHERE hostname = :name', ['name' => $name])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows[0] ?? null);
            });

        return $deferred->promise();
    }

    public function getHostnamesByUserId($id): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM hostnames WHERE user_id = :user_id ORDER by created_at DESC', [
                'user_id' => $id,
            ])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function storeHostname(array $data): PromiseInterface
    {
        $deferred = new Deferred();

        $this->getHostnameByName($data['hostname'])
            ->then(function ($registeredHostname) use ($data, $deferred) {
                if (! is_null($registeredHostname)) {
                    $deferred->resolve(null);

                    return;
                }

                $this->database->query("
                    INSERT INTO hostnames (user_id, hostname, created_at)
                    VALUES (:user_id, :hostname, DATETIME('now'))
                ", $data)
                    ->then(function (Result $result) use ($deferred) {
                        $this->database->query('SELECT * FROM hostnames WHERE id = :id', ['id' => $result->insertId])
                            ->then(function (Result $result) use ($deferred) {
                                $deferred->resolve($result->rows[0]);
                            });
                    });
            });

        return $deferred->promise();
    }

    public function getHostnamesByUserIdAndName($id, $name): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM hostnames WHERE user_id = :user_id AND hostname = :name ORDER by created_at DESC', [
                'user_id' => $id,
                'name' => $name,
            ])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function deleteHostnameForUserId($userId, $hostnameId): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query('DELETE FROM hostnames WHERE id = :id AND user_id = :user_id', [
            'id' => $hostnameId,
            'user_id' => $userId,
        ])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result);
            });

        return $deferred->promise();
    }
}
