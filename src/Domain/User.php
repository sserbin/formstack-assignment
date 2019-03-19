<?php

namespace App\Domain;

use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class User
{
    /** @var UuidInterface */
    private $id;

    /** @var string */
    private $first;

    /** @var string */
    private $last;

    /** @var string */
    private $email;

    /** @var string */
    private $password;

    public function __construct(string $first, string $last, string $email, string $password)
    {
        $this->id = Uuid::uuid1();

        $this->first = $first;
        $this->last = $last;

        $this->validateEmail($email);
        $this->email = $email;

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        if (!$hashed) {
            throw new RuntimeException('failed to hash password');
        }
        $this->password = $hashed;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->first;
    }

    public function getLastName(): string
    {
        return $this->last;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function update(string $first, string $last, string $email, string $password): void
    {
        $this->first = $first;
        $this->last = $last;

        $this->validateEmail($email);
        $this->email = $email;

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        if (!$hashed) {
            throw new RuntimeException('failed to hash password');
        }
        $this->password = $hashed;
    }

    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('invalid email');
        }
    }
}
