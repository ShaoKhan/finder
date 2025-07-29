<?php

namespace App\Tests\Controller;

use App\Entity\FoundsImage;
use App\Entity\User;
use App\Repository\FoundsImageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class FoundsControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $userRepository;
    private $foundsImageRepository;
    private $csrfTokenManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->foundsImageRepository = static::getContainer()->get(FoundsImageRepository::class);
        $this->csrfTokenManager = static::getContainer()->get(CsrfTokenManagerInterface::class);
    }

    public function testIndexPage(): void
    {
        $this->client->request('GET', '/founds/index');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testUploadPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/photo/upload');
        $this->assertResponseRedirects('/login');
    }

    public function testUploadPageWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/photo/upload');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[type="file"]');
    }

    public function testImageListPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/images');
        $this->assertResponseRedirects('/login');
    }

    public function testImageListPageWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/images');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.container');
    }

    public function testGalleryPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/founds/gallery');
        $this->assertResponseRedirects('/login');
    }

    public function testGalleryPageWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/founds/gallery');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testDeleteImageRequiresAuthentication(): void
    {
        $this->client->request('POST', '/found/1/delete');
        $this->assertResponseRedirects('/login');
    }

    public function testDeleteImageWithInvalidId(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $token = $this->csrfTokenManager->getToken('delete999');
        
        $this->client->request('POST', '/found/999/delete', [
            '_token' => $token
        ]);

        $this->assertResponseStatusCodeSame(404);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
    }

    public function testDeleteImageWithInvalidToken(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('POST', '/found/1/delete', [
            '_token' => 'invalid_token'
        ]);

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
    }

    public function testBulkDeleteRequiresAuthentication(): void
    {
        $this->client->request('POST', '/found/bulk-delete');
        $this->assertResponseRedirects('/login');
    }

    public function testBulkDeleteWithNoIds(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $token = $this->csrfTokenManager->getToken('bulk_delete');
        
        $this->client->request('POST', '/found/bulk-delete', [
            '_token' => $token,
            'ids' => []
        ]);

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
    }

    public function testBulkDeleteWithInvalidToken(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('POST', '/found/bulk-delete', [
            '_token' => 'invalid_token',
            'ids' => [1, 2, 3]
        ]);

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
    }

    public function testGeneratePdfRequiresAuthentication(): void
    {
        $this->client->request('GET', '/generate-pdf/2024-01-01');
        $this->assertResponseRedirects('/login');
    }

    public function testGenerateWordRequiresAuthentication(): void
    {
        $this->client->request('GET', '/generate-word/2024-01-01');
        $this->assertResponseRedirects('/login');
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

    private function createTestImage(User $user): FoundsImage
    {
        $image = new FoundsImage();
        $image->setUser($user);
        $image->user_uuid = $user->getUuid();
        $image->username = $user->getEmail();
        $image->filePath = 'test-image.jpg';
        $image->name = 'Test Image';
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