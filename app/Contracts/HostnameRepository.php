<?php

namespace App\Contracts;

use React\Promise\PromiseInterface;

interface HostnameRepository
{
    public function getHostnames(): PromiseInterface;

    public function getHostnameById($id): PromiseInterface;

    public function getHostnameByName(string $name): PromiseInterface;

    public function getHostnamesByUserId($id): PromiseInterface;

    public function getHostnamesByUserIdAndName($id, $name): PromiseInterface;

    public function deleteHostnameForUserId($userId, $hostnameId): PromiseInterface;

    public function storeHostname(array $data): PromiseInterface;
}
