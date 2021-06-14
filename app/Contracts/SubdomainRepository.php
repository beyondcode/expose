<?php

namespace App\Contracts;

use React\Promise\PromiseInterface;

interface SubdomainRepository
{
    public function getSubdomains(): PromiseInterface;

    public function getSubdomainById($id): PromiseInterface;

    public function getSubdomainByName(string $name): PromiseInterface;

    public function getSubdomainByNameAndDomain(string $name, string $domain): PromiseInterface;

    public function getSubdomainsByNameAndDomain(string $name, string $domain): PromiseInterface;

    public function getSubdomainsByUserId($id): PromiseInterface;

    public function getSubdomainsByUserIdAndName($id, $name): PromiseInterface;

    public function deleteSubdomainForUserId($userId, $subdomainId): PromiseInterface;

    public function storeSubdomain(array $data): PromiseInterface;
}
