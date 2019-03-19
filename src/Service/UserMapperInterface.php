<?php

namespace App\Service;

use App\Domain\User;

interface UserMapperInterface
{
    /**
     * @throws UniqueConstraintViolation
     */
    public function add(User $user): void;

    public function update(User $user): void;

    public function delete(User $user): void;

    public function find(string $id): ?User;

    /**
     * @return User[]
     */
    public function getAll(): array;
}
