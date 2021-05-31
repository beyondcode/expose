<?php

namespace App\Contracts;

use React\Promise\PromiseInterface;

interface UserRepository
{
    public function getUsers(): PromiseInterface;

    public function getUserById($id): PromiseInterface;

    public function paginateUsers(string $searchQuery, int $perPage, int $currentPage): PromiseInterface;

    public function getUserByToken(string $authToken): PromiseInterface;

    public function storeUser(array $data): PromiseInterface;

    public function deleteUser($id): PromiseInterface;

    public function getUsersByTokens(array $authTokens): PromiseInterface;

    public function updateLastSharedAt($id): PromiseInterface;
}
