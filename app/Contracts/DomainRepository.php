<?php

namespace App\Contracts;

use React\Promise\PromiseInterface;

interface DomainRepository
{
    public function getDomains(): PromiseInterface;

    public function getDomainById($id): PromiseInterface;

    public function getDomainByName(string $name): PromiseInterface;

    public function getDomainsByUserId($id): PromiseInterface;

    public function getDomainsByUserIdAndName($id, $name): PromiseInterface;

    public function deleteDomainForUserId($userId, $domainId): PromiseInterface;

    public function storeDomain(array $data): PromiseInterface;

    public function updateDomain($id, array $data): PromiseInterface;
}
