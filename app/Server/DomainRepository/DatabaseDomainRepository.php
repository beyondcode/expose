<?php

namespace App\Server\DomainRepository;

use App\Contracts\DomainRepository;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class DatabaseDomainRepository implements DomainRepository
{
    /** @var DatabaseInterface */
    protected $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    public function getDomains(): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM domains ORDER by created_at DESC')
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function getDomainById($id): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM domains WHERE id = :id', ['id' => $id])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows[0] ?? null);
            });

        return $deferred->promise();
    }

    public function getDomainByName(string $name): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM domains WHERE domain = :name', ['name' => $name])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows[0] ?? null);
            });

        return $deferred->promise();
    }

    public function getDomainsByUserId($id): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM domains WHERE user_id = :user_id ORDER by created_at DESC', [
                'user_id' => $id,
            ])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function storeDomain(array $data): PromiseInterface
    {
        $deferred = new Deferred();

        $this->getDomainByName($data['domain'])
            ->then(function ($registeredDomain) use ($data, $deferred) {
                $this->database->query("
                    INSERT INTO domains (user_id, domain, created_at)
                    VALUES (:user_id, :domain, DATETIME('now'))
                ", $data)
                    ->then(function (Result $result) use ($deferred) {
                        $this->database->query('SELECT * FROM domains WHERE id = :id', ['id' => $result->insertId])
                            ->then(function (Result $result) use ($deferred) {
                                $deferred->resolve($result->rows[0]);
                            });
                    });
            });

        return $deferred->promise();
    }

    public function getDomainsByUserIdAndName($id, $name): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM domains WHERE user_id = :user_id AND domain = :name ORDER by created_at DESC', [
                'user_id' => $id,
                'name' => $name,
            ])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function deleteDomainForUserId($userId, $domainId): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query('DELETE FROM domains WHERE id = :id AND user_id = :user_id', [
            'id' => $domainId,
            'user_id' => $userId,
        ])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result);
            });

        return $deferred->promise();
    }

    public function updateDomain($id, array $data): PromiseInterface
    {
        $deferred = new Deferred();

        // TODO

        return $deferred->promise();
    }
}
