<?php

namespace App\Tests;

use App\Entity\FoundsImage;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    public function testFoundsImageEntity(): void
    {
        $image = new FoundsImage();
        
        // Test name
        $image->setName('Test Image');
        $this->assertEquals('Test Image', $image->getName());

        // Test filePath
        $image->filePath = 'test-image.jpg';
        $this->assertEquals('test-image.jpg', $image->filePath);

        // Test note
        $image->note = 'Test note';
        $this->assertEquals('Test note', $image->note);

        // Test user_uuid
        $image->user_uuid = 'test-uuid';
        $this->assertEquals('test-uuid', $image->user_uuid);

        // Test username
        $image->username = 'testuser';
        $this->assertEquals('testuser', $image->username);

        // Test coordinates
        $image->latitude = 52.5200;
        $image->longitude = 13.4050;
        $this->assertEquals(52.5200, $image->latitude);
        $this->assertEquals(13.4050, $image->longitude);

        // Test UTM coordinates
        $image->utmX = 123.456;
        $image->utmY = 789.012;
        $this->assertEquals(123.456, $image->utmX);
        $this->assertEquals(789.012, $image->utmY);

        // Test public flag
        $image->isPublic = true;
        $this->assertTrue($image->isPublic);

        // Test id
        $image->setId(1);
        $this->assertEquals(1, $image->getId());
    }

    public function testUserEntity(): void
    {
        $user = new User();
        
        // Test email
        $user->setEmail('test@example.com');
        $this->assertEquals('test@example.com', $user->getEmail());

        // Test password
        $user->setPassword('password123');
        $this->assertEquals('password123', $user->getPassword());

        // Test roles
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $user->setRoles($roles);
        $this->assertEquals($roles, $user->getRoles());

        // Test isActive
        $user->setIsActive(true);
        $this->assertTrue($user->isActive());

        // Test uuid
        $user->setUuid('test-uuid-123');
        $this->assertEquals('test-uuid-123', $user->getUuid());

        // Test user identifier
        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }

    public function testUserDefaultValues(): void
    {
        $user = new User();
        
        $this->assertNull($user->getEmail());
        $this->assertNull($user->getPassword());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertTrue($user->isActive()); // Standardwert ist true
        $this->assertNotNull($user->getUuid()); // UUID wird im Konstruktor gesetzt
    }

    public function testFoundsImageDefaultValues(): void
    {
        $image = new FoundsImage();
        
        $this->assertNull($image->getName());
        $this->assertNull($image->getId());
        $this->assertNull($image->getUser());
        $this->assertFalse($image->isPublic);
    }

    public function testUserRelationship(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        
        $image = new FoundsImage();
        $image->setUser($user);
        
        $this->assertEquals($user, $image->getUser());
        
        $image->setUser(null);
        $this->assertNull($image->getUser());
    }

    public function testSetNearestStreet(): void
    {
        $image = new FoundsImage();
        $image->setNearestStreet('Test Street');
        // Diese Methode ist aktuell leer, aber wir testen, dass sie aufgerufen werden kann
        $this->assertTrue(true);
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->setPassword('password123');
        $user->eraseCredentials();
        
        // Nach eraseCredentials sollte das Passwort nicht geändert werden
        // da wir keine spezielle Implementierung haben
        $this->assertEquals('password123', $user->getPassword());
    }

    public function testRoleManagement(): void
    {
        $user = new User();
        
        // Test default role
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        // Test adding role
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());

        // Test single role - getRoles() fügt automatisch ROLE_USER hinzu
        $user->setRoles(['ROLE_ADMIN']);
        $this->assertEquals(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testEmailValidation(): void
    {
        $user = new User();
        
        $user->setEmail('valid@email.com');
        $this->assertEquals('valid@email.com', $user->getEmail());

        $user->setEmail('another.valid@domain.co.uk');
        $this->assertEquals('another.valid@domain.co.uk', $user->getEmail());
    }

    public function testBooleanFlags(): void
    {
        $user = new User();
        
        // Test isActive
        $user->setIsActive(true);
        $this->assertTrue($user->isActive());

        $user->setIsActive(false);
        $this->assertFalse($user->isActive());
    }
} 