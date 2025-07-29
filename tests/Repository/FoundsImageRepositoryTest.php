<?php

namespace App\Tests\Repository;

use App\Entity\FoundsImage;
use App\Entity\User;
use App\Repository\FoundsImageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FoundsImageRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private FoundsImageRepository $foundsImageRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);
        $this->foundsImageRepository = $kernel->getContainer()->get(FoundsImageRepository::class);
        $this->userRepository = $kernel->getContainer()->get(UserRepository::class);
    }

    public function testFindAllFiltered(): void
    {
        $user = $this->createTestUser();
        $this->createTestImage($user);

        $query = $this->foundsImageRepository->findAllFiltered('createdAt', 'desc', '', $user);
        $results = $query->getResult();

        $this->assertNotEmpty($results);
        $this->assertInstanceOf(FoundsImage::class, $results[0]);
    }

    public function testFindAllFilteredWithSearch(): void
    {
        $user = $this->createTestUser();
        $this->createTestImage($user, 'Test Image with Search');

        $query = $this->foundsImageRepository->findAllFiltered('createdAt', 'desc', 'Test Image', $user);
        $results = $query->getResult();

        $this->assertNotEmpty($results);
        $this->assertInstanceOf(FoundsImage::class, $results[0]);
    }

    public function testFindAllFilteredWithDifferentSort(): void
    {
        $user = $this->createTestUser();
        $this->createTestImage($user);

        $query = $this->foundsImageRepository->findAllFiltered('name', 'asc', '', $user);
        $results = $query->getResult();

        $this->assertNotEmpty($results);
    }

    public function testFindByDateRange(): void
    {
        $user = $this->createTestUser();
        $this->createTestImage($user);

        $startDate = new \DateTime('2024-01-01 00:00:00');
        $endDate = new \DateTime('2024-12-31 23:59:59');

        $results = $this->foundsImageRepository->findByDateRange($startDate, $endDate);

        $this->assertNotEmpty($results);
        $this->assertInstanceOf(FoundsImage::class, $results[0]);
    }

    public function testFindByDateRangeWithNoResults(): void
    {
        $startDate = new \DateTime('2020-01-01 00:00:00');
        $endDate = new \DateTime('2020-12-31 23:59:59');

        $results = $this->foundsImageRepository->findByDateRange($startDate, $endDate);

        $this->assertEmpty($results);
    }

    public function testSaveAndRemove(): void
    {
        $user = $this->createTestUser();
        $image = $this->createTestImage($user, 'Test Image for Save/Remove');

        // Test save
        $this->foundsImageRepository->save($image, true);
        $this->assertNotNull($image->getId());

        // Test remove
        $this->foundsImageRepository->remove($image, true);
        
        $foundImage = $this->foundsImageRepository->find($image->getId());
        $this->assertNull($foundImage);
    }

    public function testFindAllSortedByField(): void
    {
        $user = $this->createTestUser();
        $this->createTestImage($user);

        $query = $this->foundsImageRepository->findAllSortedByField('name', 'ASC');
        $results = $query->getResult();

        $this->assertNotEmpty($results);
        $this->assertInstanceOf(FoundsImage::class, $results[0]);
    }

    public function testFindAllSortedByFieldWithInvalidField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->foundsImageRepository->findAllSortedByField('invalid_field', 'ASC');
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestImage(User $user, string $name = 'Test Image'): FoundsImage
    {
        $image = new FoundsImage();
        $image->setUser($user);
        $image->user_uuid = $user->getUuid();
        $image->username = $user->getEmail();
        $image->filePath = 'test-image.jpg';
        $image->name = $name;
        $image->createdAt = new \DateTime();
        $image->latitude = 52.5200;
        $image->longitude = 13.4050;
        $image->isPublic = false;

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        return $image;
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