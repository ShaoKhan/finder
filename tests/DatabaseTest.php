<?php

namespace App\Tests;

use App\Entity\FoundsImage;
use App\Entity\User;
use App\Repository\FoundsImageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DatabaseTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private FoundsImageRepository $foundsImageRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        // Verwende die Registry für den EntityManager
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->foundsImageRepository = $container->get('doctrine')->getRepository(FoundsImage::class);
        $this->userRepository = $container->get('doctrine')->getRepository(User::class);

        // Erstelle das Datenbank-Schema
        $this->createSchema();
    }

    private function createSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    public function testDatabaseConnection(): void
    {
        $this->assertNotNull($this->entityManager);
        $this->assertNotNull($this->foundsImageRepository);
        $this->assertNotNull($this->userRepository);
    }

    public function testCreateAndSaveUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setUuid('test-uuid-123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->assertNotNull($user->getId());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('test-uuid-123', $user->getUuid());
    }

    public function testCreateAndSaveFoundsImage(): void
    {
        // Erstelle zuerst einen User
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setUuid('test-uuid-123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Erstelle ein FoundsImage
        $image = new FoundsImage();
        $image->setUser($user);
        $image->user_uuid = $user->getUuid();
        $image->username = $user->getEmail();
        $image->filePath = 'test-image.jpg';
        $image->setName('Test Image');
        $image->createdAt = new \DateTime();
        $image->latitude = 52.5200;
        $image->longitude = 13.4050;
        $image->isPublic = false;

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        $this->assertNotNull($image->getId());
        $this->assertEquals('Test Image', $image->getName());
        $this->assertEquals($user->getUuid(), $image->user_uuid);
    }

    public function testFindUserByEmail(): void
    {
        $user = new User();
        $user->setEmail('find@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setUuid('find-uuid-123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $foundUser = $this->userRepository->findOneBy(['email' => 'find@example.com']);
        
        $this->assertNotNull($foundUser);
        $this->assertEquals('find@example.com', $foundUser->getEmail());
        $this->assertEquals('find-uuid-123', $foundUser->getUuid());
    }

    public function testFindFoundsImageByName(): void
    {
        // Erstelle User und Image
        $user = new User();
        $user->setEmail('find@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setUuid('find-uuid-123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $image = new FoundsImage();
        $image->setUser($user);
        $image->user_uuid = $user->getUuid();
        $image->username = $user->getEmail();
        $image->filePath = 'find-image.jpg';
        $image->setName('Find Test Image');
        $image->createdAt = new \DateTime();
        $image->latitude = 52.5200;
        $image->longitude = 13.4050;
        $image->isPublic = false;

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        $foundImage = $this->foundsImageRepository->findOneBy(['name' => 'Find Test Image']);
        
        $this->assertNotNull($foundImage);
        $this->assertEquals('Find Test Image', $foundImage->getName());
        $this->assertEquals($user->getUuid(), $foundImage->user_uuid);
    }

    public function testUpdateFoundsImage(): void
    {
        // Erstelle User und Image
        $user = new User();
        $user->setEmail('update@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setUuid('update-uuid-123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $image = new FoundsImage();
        $image->setUser($user);
        $image->user_uuid = $user->getUuid();
        $image->username = $user->getEmail();
        $image->filePath = 'update-image.jpg';
        $image->setName('Update Test Image');
        $image->createdAt = new \DateTime();
        $image->latitude = 52.5200;
        $image->longitude = 13.4050;
        $image->isPublic = false;

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        // Update das Image
        $image->setName('Updated Test Image');
        $image->note = 'Updated note';
        $image->isPublic = true;

        $this->entityManager->flush();

        $updatedImage = $this->foundsImageRepository->find($image->getId());
        
        $this->assertEquals('Updated Test Image', $updatedImage->getName());
        $this->assertEquals('Updated note', $updatedImage->note);
        $this->assertTrue($updatedImage->isPublic);
    }

    public function testDeleteFoundsImage(): void
    {
        // Erstelle User und Image
        $user = new User();
        $user->setEmail('delete@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setUuid('delete-uuid-123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $image = new FoundsImage();
        $image->setUser($user);
        $image->user_uuid = $user->getUuid();
        $image->username = $user->getEmail();
        $image->filePath = 'delete-image.jpg';
        $image->setName('Delete Test Image');
        $image->createdAt = new \DateTime();
        $image->latitude = 52.5200;
        $image->longitude = 13.4050;
        $image->isPublic = false;

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        $imageId = $image->getId();

        // Lösche das Image
        $this->entityManager->remove($image);
        $this->entityManager->flush();

        $deletedImage = $this->foundsImageRepository->find($imageId);
        $this->assertNull($deletedImage);
    }

    public function testFindAllFoundsImages(): void
    {
        // Erstelle mehrere Images
        $user = new User();
        $user->setEmail('findall@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setUuid('findall-uuid-123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        for ($i = 1; $i <= 3; $i++) {
            $image = new FoundsImage();
            $image->setUser($user);
            $image->user_uuid = $user->getUuid();
            $image->username = $user->getEmail();
            $image->filePath = "findall-image-{$i}.jpg";
            $image->setName("Find All Test Image {$i}");
            $image->createdAt = new \DateTime();
            $image->latitude = 52.5200;
            $image->longitude = 13.4050;
            $image->isPublic = false;

            $this->entityManager->persist($image);
        }
        $this->entityManager->flush();

        $allImages = $this->foundsImageRepository->findAll();
        $this->assertGreaterThanOrEqual(3, count($allImages));
    }

    public function testFindFoundsImagesByUser(): void
    {
        // Erstelle zwei User
        $user1 = new User();
        $user1->setEmail('user1@example.com');
        $user1->setPassword('password123');
        $user1->setRoles(['ROLE_USER']);
        $user1->setIsActive(true);
        $user1->setUuid('user1-uuid-123');

        $user2 = new User();
        $user2->setEmail('user2@example.com');
        $user2->setPassword('password123');
        $user2->setRoles(['ROLE_USER']);
        $user2->setIsActive(true);
        $user2->setUuid('user2-uuid-456');

        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->flush();

        // Erstelle Images für beide User
        for ($i = 1; $i <= 2; $i++) {
            $image = new FoundsImage();
            $image->setUser($user1);
            $image->user_uuid = $user1->getUuid();
            $image->username = $user1->getEmail();
            $image->filePath = "user1-image-{$i}.jpg";
            $image->setName("User1 Test Image {$i}");
            $image->createdAt = new \DateTime();
            $image->latitude = 52.5200;
            $image->longitude = 13.4050;
            $image->isPublic = false;

            $this->entityManager->persist($image);
        }

        for ($i = 1; $i <= 3; $i++) {
            $image = new FoundsImage();
            $image->setUser($user2);
            $image->user_uuid = $user2->getUuid();
            $image->username = $user2->getEmail();
            $image->filePath = "user2-image-{$i}.jpg";
            $image->setName("User2 Test Image {$i}");
            $image->createdAt = new \DateTime();
            $image->latitude = 52.5200;
            $image->longitude = 13.4050;
            $image->isPublic = false;

            $this->entityManager->persist($image);
        }
        $this->entityManager->flush();

        // Test finde Images von User1
        $user1Images = $this->foundsImageRepository->findBy(['user_uuid' => $user1->getUuid()]);
        $this->assertCount(2, $user1Images);

        // Test finde Images von User2
        $user2Images = $this->foundsImageRepository->findBy(['user_uuid' => $user2->getUuid()]);
        $this->assertCount(3, $user2Images);
    }

    public function testFindPublicFoundsImages(): void
    {
        // Erstelle User
        $user = new User();
        $user->setEmail('public@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setUuid('public-uuid-123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Erstelle public und private Images
        for ($i = 1; $i <= 3; $i++) {
            $image = new FoundsImage();
            $image->setUser($user);
            $image->user_uuid = $user->getUuid();
            $image->username = $user->getEmail();
            $image->filePath = "public-image-{$i}.jpg";
            $image->setName("Public Test Image {$i}");
            $image->createdAt = new \DateTime();
            $image->latitude = 52.5200;
            $image->longitude = 13.4050;
            $image->isPublic = true;

            $this->entityManager->persist($image);
        }

        for ($i = 1; $i <= 2; $i++) {
            $image = new FoundsImage();
            $image->setUser($user);
            $image->user_uuid = $user->getUuid();
            $image->username = $user->getEmail();
            $image->filePath = "private-image-{$i}.jpg";
            $image->setName("Private Test Image {$i}");
            $image->createdAt = new \DateTime();
            $image->latitude = 52.5200;
            $image->longitude = 13.4050;
            $image->isPublic = false;

            $this->entityManager->persist($image);
        }
        $this->entityManager->flush();

        // Test finde public Images
        $publicImages = $this->foundsImageRepository->findBy(['isPublic' => true]);
        $this->assertCount(3, $publicImages);

        // Test finde private Images
        $privateImages = $this->foundsImageRepository->findBy(['isPublic' => false]);
        $this->assertCount(2, $privateImages);
    }

    public function testDatabaseTransactionRollback(): void
    {
        // Test, dass Transaktionen korrekt funktionieren
        $user = new User();
        $user->setEmail('transaction@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setUuid('transaction-uuid-123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userId = $user->getId();

        // Test Rollback
        $this->entityManager->beginTransaction();
        
        $user->setEmail('updated@example.com');
        $this->entityManager->flush();
        
        $this->entityManager->rollback();

        // Prüfe, dass die Änderung nicht gespeichert wurde
        $this->entityManager->clear();
        $user = $this->userRepository->find($userId);
        $this->assertEquals('transaction@example.com', $user->getEmail());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Cleanup test data
        $this->entityManager->createQuery('DELETE FROM App\Entity\FoundsImage')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->flush();
    }
} 