---
title: User Repository
order: 2
---

# User Repository

The expose server tries to load users out of the built-in SQLite database by default. 

If you want to change the default implementation and load your users from a different storage engine, you can implement the `UserRepository` interface and change it in your expose configuration file.

This is how the interface looks like:

```php
use React\Promise\PromiseInterface;

interface UserRepository
{
    public function getUsers(): PromiseInterface;

    public function getUserById($id): PromiseInterface;

    public function getUserByToken(string $authToken): PromiseInterface;

    public function storeUser(array $data): PromiseInterface;

    public function deleteUser($id): PromiseInterface;
}
```