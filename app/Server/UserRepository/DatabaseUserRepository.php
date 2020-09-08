<?php

namespace App\Server\UserRepository;

use App\Contracts\ConnectionManager;
use App\Contracts\UserRepository;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class DatabaseUserRepository implements UserRepository
{
    /** @var DatabaseInterface */
    protected $database;

    /** @var ConnectionManager */
    protected $connectionManager;

    public function __construct(DatabaseInterface $database, ConnectionManager $connectionManager)
    {
        $this->database = $database;
        $this->connectionManager = $connectionManager;
    }

    public function getUsers(): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM users ORDER by created_at DESC')
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            });

        return $deferred->promise();
    }

    public function paginateUsers(int $perPage, int $currentPage): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM users ORDER by created_at DESC LIMIT :limit OFFSET :offset', [
                'limit' => $perPage + 1,
                'offset' => $currentPage < 2 ? 0 : ($currentPage - 1) * $perPage,
            ])
            ->then(function (Result $result) use ($deferred, $perPage, $currentPage) {
                if (count($result->rows) == $perPage + 1) {
                    array_pop($result->rows);
                    $nextPage = $currentPage + 1;
                }

                $users = collect($result->rows)->map(function ($user) {
                    return $this->getUserDetails($user);
                })->toArray();

                $paginated = [
                    'users' => $users,
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'next_page' => $nextPage ?? null,
                    'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
                ];

                $deferred->resolve($paginated);
            });

        return $deferred->promise();
    }

    protected function getUserDetails(array $user)
    {
        $user['sites'] = $user['auth_token'] !== '' ? $this->connectionManager->getConnectionsForAuthToken($user['auth_token']) : [];
        $user['tcp_connections'] = $user['auth_token'] !== '' ? $this->connectionManager->getTcpConnectionsForAuthToken($user['auth_token']) : [];

        return $user;
    }

    public function getUserById($id): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM users WHERE id = :id', ['id' => $id])
            ->then(function (Result $result) use ($deferred) {
                $user = $result->rows[0] ?? null;

                if (! is_null($user)) {
                    $user = $this->getUserDetails($user);
                }

                $deferred->resolve($user);
            });

        return $deferred->promise();
    }

    public function getUserByToken(string $authToken): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM users WHERE auth_token = :token', ['token' => $authToken])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows[0] ?? null);
            });

        return $deferred->promise();
    }

    public function storeUser(array $data): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query("
            INSERT INTO users (name, auth_token, can_specify_subdomains, can_share_tcp_ports, created_at)
            VALUES (:name, :auth_token, :can_specify_subdomains, :can_share_tcp_ports, DATETIME('now'))
        ", $data)
            ->then(function (Result $result) use ($deferred) {
                $this->database->query('SELECT * FROM users WHERE id = :id', ['id' => $result->insertId])
                    ->then(function (Result $result) use ($deferred) {
                        $deferred->resolve($result->rows[0]);
                    });
            });

        return $deferred->promise();
    }

    public function deleteUser($id): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query('DELETE FROM users WHERE id = :id', ['id' => $id])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result);
            });

        return $deferred->promise();
    }
}
