<?php

namespace App\Tests;

use App\Entity\FoundsImage;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

trait TestHelper
{
    protected function createTestUser(EntityManagerInterface $entityManager): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    protected function createTestImage(EntityManagerInterface $entityManager, User $user, string $name = 'Test Image'): FoundsImage
    {
        $image = new FoundsImage();
        $image->setUser($user);
        $image->user_uuid = $user->getUuid();
        $image->username = $user->getEmail();
        $image->filePath = 'test-image.jpg';
        $image->setName($name);
        $image->createdAt = new \DateTime();
        $image->latitude = 52.5200;
        $image->longitude = 13.4050;
        $image->isPublic = false;

        $entityManager->persist($image);
        $entityManager->flush();

        return $image;
    }

    protected function cleanupTestData(EntityManagerInterface $entityManager): void
    {
        $entityManager->createQuery('DELETE FROM App\Entity\FoundsImage')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $entityManager->flush();
    }

    protected function createTestImageFile(string $directory): string
    {
        $imageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $tempFile = tempnam($directory, 'test_image_');
        file_put_contents($tempFile, $imageData);
        
        return $tempFile;
    }

    protected function createTestTextFile(string $directory): string
    {
        $tempFile = tempnam($directory, 'test_text_');
        file_put_contents($tempFile, 'This is not an image file');
        
        return $tempFile;
    }
} 