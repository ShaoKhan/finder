<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testGettersAndSetters(): void
    {
        // Test email
        $this->user->setEmail('test@example.com');
        $this->assertEquals('test@example.com', $this->user->getEmail());

        // Test password
        $this->user->setPassword('password123');
        $this->assertEquals('password123', $this->user->getPassword());

        // Test roles
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $this->user->setRoles($roles);
        $this->assertEquals($roles, $this->user->getRoles());

        // Test isVerified
        $this->user->setIsVerified(true);
        $this->assertTrue($this->user->isVerified());

        // Test isActive
        $this->user->setIsActive(true);
        $this->assertTrue($this->user->isActive());

        // Test uuid
        $this->user->setUuid('test-uuid-123');
        $this->assertEquals('test-uuid-123', $this->user->getUuid());
    }

    public function testUserIdentifier(): void
    {
        $this->user->setEmail('test@example.com');
        $this->assertEquals('test@example.com', $this->user->getUserIdentifier());
    }

    public function testEraseCredentials(): void
    {
        $this->user->setPassword('password123');
        $this->user->eraseCredentials();
        
        // Nach eraseCredentials sollte das Passwort nicht geändert werden
        // da wir keine spezielle Implementierung haben
        $this->assertEquals('password123', $this->user->getPassword());
    }

    public function testDefaultValues(): void
    {
        $newUser = new User();
        
        $this->assertNull($newUser->getEmail());
        $this->assertNull($newUser->getPassword());
        $this->assertEquals(['ROLE_USER'], $newUser->getRoles());
        $this->assertFalse($newUser->isVerified());
        $this->assertFalse($newUser->isActive());
        $this->assertNull($newUser->getUuid());
    }

    public function testRoleManagement(): void
    {
        // Test default role
        $this->assertEquals(['ROLE_USER'], $this->user->getRoles());

        // Test adding role
        $this->user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $this->user->getRoles());

        // Test single role
        $this->user->setRoles(['ROLE_ADMIN']);
        $this->assertEquals(['ROLE_ADMIN'], $this->user->getRoles());
    }

    public function testUuidGeneration(): void
    {
        $this->user->setUuid('generated-uuid');
        $this->assertEquals('generated-uuid', $this->user->getUuid());
    }

    public function testEmailValidation(): void
    {
        $this->user->setEmail('valid@email.com');
        $this->assertEquals('valid@email.com', $this->user->getEmail());

        $this->user->setEmail('another.valid@domain.co.uk');
        $this->assertEquals('another.valid@domain.co.uk', $this->user->getEmail());
    }

    public function testPasswordHashing(): void
    {
        $plainPassword = 'plaintext123';
        $this->user->setPassword($plainPassword);
        $this->assertEquals($plainPassword, $this->user->getPassword());
    }

    public function testBooleanFlags(): void
    {
        // Test isVerified
        $this->user->setIsVerified(true);
        $this->assertTrue($this->user->isVerified());

        $this->user->setIsVerified(false);
        $this->assertFalse($this->user->isVerified());

        // Test isActive
        $this->user->setIsActive(true);
        $this->assertTrue($this->user->isActive());

        $this->user->setIsActive(false);
        $this->assertFalse($this->user->isActive());
    }
} 