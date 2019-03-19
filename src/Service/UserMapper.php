<?php

namespace App\Service;

use App\Domain\User;
use App\Service\Exception\UniqueConstraintViolation;
use PDO;
use PDOException;
use Ramsey\Uuid\Uuid;
use ReflectionClass;

class UserMapper implements UserMapperInterface
{
    /** @var PDO */
    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @throws UniqueConstraintViolation
     */
    public function add(User $user): void
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO User (id, firstName, lastName, email, password) '
            . 'VALUES (:id, :firstName, :lastName, :email, :password)'
        );
        $stmt->bindValue('id', $user->getId()->getBytes());
        $stmt->bindValue('firstName', $user->getFirstName());
        $stmt->bindValue('lastName', $user->getLastName());
        $stmt->bindValue('email', $user->getEmail());
        $stmt->bindValue('password', $user->getPassword());

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // integrity_constrain_violation as per SQLSTATE codes
                throw new UniqueConstraintViolation('duplicate email', $e);
            } else {
                throw $e; // re-throw otherwise
            }
        }
    }

    public function update(User $user): void
    {
        $stmt = $this->conn->prepare(
            'UPDATE User SET
                firstName = :firstName,
                lastName = :lastName,
                email = :email,
                password = :pass,
                avatar = :avatar
             WHERE id = :id'
        );
        $stmt->bindValue('firstName', $user->getFirstName());
        $stmt->bindValue('lastName', $user->getLastName());
        $stmt->bindValue('email', $user->getEmail());
        $stmt->bindValue('pass', $user->getPassword());
        $stmt->bindValue('avatar', $user->getAvatar());
        $stmt->bindValue('id', $user->getId()->getBytes());
        $stmt->execute();
    }

    public function delete(User $user): void
    {
        $stmt = $this->conn->prepare(
            'DELETE FROM User WHERE id = :id'
        );
        $stmt->bindValue('id', $user->getId()->getBytes());
        $stmt->execute();
    }

    public function find(string $id): ?User
    {
        $q = 'SELECT id, firstName, lastName, email, password, avatar FROM User WHERE id = :id';

        $stmt = $this->conn->prepare($q);
        $stmt->bindValue('id', Uuid::fromString($id)->getBytes());
        $stmt->execute();

        /** @var array{id:string,firstName:string,lastName:string,email:string,password:string,avatar:?string} */
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return empty($record) ? null : $this->hydrate($record);
    }

    /**
     * @return User[]
     */
    public function getAll(): array
    {
        $q = 'SELECT id, firstName, lastName, email, password, avatar FROM User';

        $stmt = $this->conn->prepare($q);
        $stmt->execute();

        /** @var array<array{id:string,firstName:string,lastName:string,email:string,password:string,avatar:?string}> */
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrate'], $records);
    }

    /**
     * @param array{id:string,firstName:string,lastName:string,email:string,password:string,avatar:?string} $record
     */
    private function hydrate(array $record): User
    {
        $refl = new ReflectionClass(User::class);

        $user = $refl->newInstanceWithoutConstructor();

        $this->setPropValue($refl, $user, 'id', Uuid::fromBytes($record['id']));

        $this->setPropValue($refl, $user, 'first', $record['firstName']);
        $this->setPropValue($refl, $user, 'last', $record['lastName']);
        $this->setPropValue($refl, $user, 'email', $record['email']);

        $this->setPropValue($refl, $user, 'password', $record['password']);
        $this->setPropValue($refl, $user, 'avatar', $record['avatar']);

        return $user;
    }

    /**
     * @param mixed $value
     */
    private function setPropValue(ReflectionClass $class, object $obj, string $prop, $value): void
    {
        $prop = $class->getProperty($prop);
        $prop->setAccessible(true);
        $prop->setValue($obj, $value);
    }
}
