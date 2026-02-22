<?php

namespace Core\Auth\Contracts;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?array;
    public function findById(int|string $id): ?array;
    public function updateRememberToken(int|string $id, ?string $token): void;
}
