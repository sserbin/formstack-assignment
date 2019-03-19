<?php
namespace Tests;

use App\Domain\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;

class UserTest extends TestCase
{
    /**
     * @test
     */
    public function invalidEmailRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $user = new User('john', 'doe', '123', 'pass');
    }

    /**
     * @test
     */
    public function personDataIsStored(): void
    {
        $user = new User('john', 'doe', 'john@doe.org', 'pass');

        $this->assertEquals('john', $user->getFirstName());
        $this->assertEquals('doe', $user->getLastName());
        $this->assertEquals('john@doe.org', $user->getEmail());
    }

    /**
     * @test
     */
    public function uuidIsAssigned(): void
    {
        $user = new User('john', 'doe', 'john@doe.org', 'pass');

        $this->assertInstanceOf(UuidInterface::class, $user->getId());
    }

    /**
     * @test
     */
    public function canUpdatePersonData(): void
    {
        $user = new User('john', 'doe', 'john@doe.org', 'pass');

        $user->update('jane', 'roe', 'jane@roe.org', 'pass');
        $this->assertEquals('jane', $user->getFirstName());
        $this->assertEquals('roe', $user->getLastName());
        $this->assertEquals('jane@roe.org', $user->getEmail());
    }
}
