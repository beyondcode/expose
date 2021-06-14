<?php

namespace App\Server\SubdomainRepository;

use App\Contracts\SubdomainRepository;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class DatabaseSubdomainRepository implements SubdomainRepository
{
    /** @var DatabaseInterface */
    protected $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    public function getSubdomains(): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM subdomains ORDER by created_at DESC')
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function getSubdomainById($id): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM subdomains WHERE id = :id', ['id' => $id])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows[0] ?? null);
            });

        return $deferred->promise();
    }

    public function getSubdomainByName(string $name): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM subdomains WHERE subdomain = :name', ['name' => $name])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows[0] ?? null);
            });

        return $deferred->promise();
    }

    public function getSubdomainByNameAndDomain(string $name, string $domain): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM subdomains WHERE subdomain = :name AND domain = :domain', [
                'name' => $name,
                'domain' => $domain,
            ])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows[0] ?? null);
            });

        return $deferred->promise();
    }

    public function getSubdomainsByNameAndDomain(string $name, string $domain): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM subdomains WHERE subdomain = :name AND domain = :domain', [
                'name' => $name,
                'domain' => $domain,
            ])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function getSubdomainsByUserId($id): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM subdomains WHERE user_id = :user_id ORDER by created_at DESC', [
                'user_id' => $id,
            ])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function storeSubdomain(array $data): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query("
            INSERT INTO subdomains (user_id, subdomain, domain, created_at)
            VALUES (:user_id, :subdomain, :domain, DATETIME('now'))
        ", $data)
            ->then(function (Result $result) use ($deferred) {
                $this->database->query('SELECT * FROM subdomains WHERE id = :id', ['id' => $result->insertId])
                    ->then(function (Result $result) use ($deferred) {
                        $deferred->resolve($result->rows[0]);
                    });
            });

        return $deferred->promise();
    }

    public function getSubdomainsByUserIdAndName($id, $name): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM subdomains WHERE user_id = :user_id AND subdomain = :name ORDER by created_at DESC', [
                'user_id' => $id,
                'name' => $name,
            ])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function deleteSubdomainForUserId($userId, $subdomainId): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query('DELETE FROM subdomains WHERE (id = :id OR subdomain = :id) AND user_id = :user_id', [
            'id' => $subdomainId,
            'user_id' => $userId,
        ])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result);
            });

        return $deferred->promise();
    }
}
