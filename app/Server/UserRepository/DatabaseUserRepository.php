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

    public function paginateUsers(string $searchQuery, int $perPage, int $currentPage): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT COUNT(*) AS count FROM users')
            ->then(function (Result $result) use ($searchQuery, $deferred, $perPage, $currentPage) {
                $totalUsers = $result->rows[0]['count'];

                $query = 'SELECT * FROM users ';

                $bindings = [
                    'limit' => $perPage + 1,
                    'offset' => $currentPage < 2 ? 0 : ($currentPage - 1) * $perPage,
                ];

                if ($searchQuery !== '') {
                    $query .= "WHERE name LIKE '%".$searchQuery."%' ";
                    $bindings['search'] = $searchQuery;
                }

                $query .= ' ORDER by created_at DESC LIMIT :limit OFFSET :offset';

                $this->database
                    ->query($query, $bindings)
                    ->then(function (Result $result) use ($deferred, $perPage, $currentPage, $totalUsers) {
                        if (count($result->rows) == $perPage + 1) {
                            array_pop($result->rows);
                            $nextPage = $currentPage + 1;
                        }

                        $users = collect($result->rows)->map(function ($user) {
                            return $this->getUserDetails($user);
                        })->toArray();

                        $paginated = [
                            'total' => $totalUsers,
                            'users' => $users,
                            'current_page' => $currentPage,
                            'per_page' => $perPage,
                            'next_page' => $nextPage ?? null,
                            'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
                        ];

                        $deferred->resolve($paginated);
                    });
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

    public function updateLastSharedAt($id): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query("UPDATE users SET last_shared_at = date('now') WHERE id = :id", ['id' => $id])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve();
            });

        return $deferred->promise();
    }

    public function getUserByToken(string $authToken): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database
            ->query('SELECT * FROM users WHERE auth_token = :token', ['token' => $authToken])
            ->then(function (Result $result) use ($deferred) {
                $user = $result->rows[0] ?? null;

                if (! is_null($user)) {
                    $user = $this->getUserDetails($user);
                }

                $deferred->resolve($user);
            });

        return $deferred->promise();
    }

    public function storeUser(array $data): PromiseInterface
    {
        $deferred = new Deferred();

        $this->getUserByToken($data['auth_token'])
            ->then(function ($existingUser) use ($data, $deferred) {
                if (is_null($existingUser)) {
                    $this->database->query("
            INSERT INTO users (name, auth_token, can_specify_subdomains, can_specify_domains, can_share_tcp_ports, max_connections, created_at)
            VALUES (:name, :auth_token, :can_specify_subdomains, :can_specify_domains, :can_share_tcp_ports, :max_connections, DATETIME('now'))
        ", $data)
                        ->then(function (Result $result) use ($deferred) {
                            $this->database->query('SELECT * FROM users WHERE id = :id', ['id' => $result->insertId])
                                ->then(function (Result $result) use ($deferred) {
                                    $deferred->resolve($result->rows[0]);
                                });
                        });
                } else {
                    $this->database->query('
            UPDATE users
            SET
                name = :name,
                can_specify_subdomains = :can_specify_subdomains,
                can_specify_domains = :can_specify_domains,
                can_share_tcp_ports = :can_share_tcp_ports,
                max_connections = :max_connections
            WHERE
                auth_token = :auth_token
        ', $data)
                        ->then(function (Result $result) use ($existingUser, $deferred) {
                            $this->database->query('SELECT * FROM users WHERE id = :id', ['id' => $existingUser['id']])
                                ->then(function (Result $result) use ($deferred) {
                                    $deferred->resolve($result->rows[0]);
                                });
                        });
                }
            });

        return $deferred->promise();
    }

    public function deleteUser($id): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query('DELETE FROM users WHERE id = :id OR auth_token = :id', ['id' => $id])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result);
            });

        return $deferred->promise();
    }

    public function getUsersByTokens(array $authTokens): PromiseInterface
    {
        $deferred = new Deferred();

        $authTokenString = collect($authTokens)->map(function ($token) {
            return '"'.$token.'"';
        })->join(',');

        $this->database->query('SELECT * FROM users WHERE auth_token IN ('.$authTokenString.')')
            ->then(function (Result $result) use ($deferred) {
                $users = collect($result->rows)->map(function ($user) {
                    return $this->getUserDetails($user);
                })->toArray();

                $deferred->resolve($users);
            });

        return $deferred->promise();
    }
}
