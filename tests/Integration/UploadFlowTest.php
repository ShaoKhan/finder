<?php

namespace App\Tests\Integration;

use App\Entity\FoundsImage;
use App\Entity\User;
use App\Repository\FoundsImageRepository;
use App\Repository\UserRepository;
use App\Service\ImageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadFlowTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $userRepository;
    private $foundsImageRepository;
    private $imageService;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->foundsImageRepository = static::getContainer()->get(FoundsImageRepository::class);
        $this->imageService = static::getContainer()->get(ImageService::class);
    }

    public function testCompleteUploadFlow(): void
    {
        // 1. Erstelle Test-User
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        // 2. Test Upload-Seite
        $this->client->request('GET', '/photo/upload');
        $this->assertResponseIsSuccessful();

        // 3. Test Upload-Form
        $uploadDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/fundbilder/';
        $testImagePath = $this->createTestImageFile($uploadDir);

        $uploadedFile = new UploadedFile(
            $testImagePath,
            'test-image.jpg',
            'image/jpeg',
            null,
            true
        );

        $this->client->request('POST', '/photo/upload', [], [
            'founds_image_upload[images][]' => $uploadedFile,
            'founds_image_upload[name]' => 'Test Image',
            'founds_image_upload[note]' => 'Test note',
            'founds_image_upload[isPublic]' => true,
        ]);

        $this->assertResponseRedirects('/images');

        // 4. Test, dass Bild in Datenbank gespeichert wurde
        $savedImage = $this->foundsImageRepository->findOneBy(['name' => 'Test Image']);
        $this->assertNotNull($savedImage);
        $this->assertEquals($user->getUuid(), $savedImage->user_uuid);
        $this->assertEquals('Test note', $savedImage->note);
        $this->assertTrue($savedImage->isPublic);

        // Cleanup
        unlink($testImagePath);
        if (file_exists($savedImage->filePath)) {
            unlink($savedImage->filePath);
        }
    }

    public function testUploadWithInvalidFile(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $uploadDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/fundbilder/';
        $testFile = $this->createTestTextFile($uploadDir);

        $uploadedFile = new UploadedFile(
            $testFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $this->client->request('POST', '/photo/upload', [], [
            'founds_image_upload[images][]' => $uploadedFile,
            'founds_image_upload[name]' => 'Test Image',
        ]);

        $this->assertResponseStatusCodeSame(400);

        // Cleanup
        unlink($testFile);
    }

    public function testUploadWithoutAuthentication(): void
    {
        $this->client->request('POST', '/photo/upload');
        $this->assertResponseRedirects('/login');
    }

    public function testUploadWithEmptyForm(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('POST', '/photo/upload', [
            'founds_image_upload[name]' => '',
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testImageListAfterUpload(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        // Erstelle Test-Bild
        $image = $this->createTestImage($user);

        // Test Liste
        $this->client->request('GET', '/images');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $image->getName());
    }

    public function testBulkDeleteFlow(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        // Erstelle mehrere Test-Bilder
        $image1 = $this->createTestImage($user, 'Test Image 1');
        $image2 = $this->createTestImage($user, 'Test Image 2');

        // Test Bulk-Delete
        $token = static::getContainer()->get('security.csrf.token_manager')->getToken('bulk_delete');
        
        $this->client->request('POST', '/found/bulk-delete', [
            '_token' => $token,
            'ids' => [$image1->getId(), $image2->getId()]
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals(2, $response['deletedCount']);

        // Test, dass Bilder aus Datenbank entfernt wurden
        $this->assertNull($this->foundsImageRepository->find($image1->getId()));
        $this->assertNull($this->foundsImageRepository->find($image2->getId()));
    }

    public function testSingleDeleteFlow(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        // Erstelle Test-Bild
        $image = $this->createTestImage($user);

        // Test Single-Delete
        $token = static::getContainer()->get('security.csrf.token_manager')->getToken('delete' . $image->getId());
        
        $this->client->request('POST', '/found/' . $image->getId() . '/delete', [
            '_token' => $token
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);

        // Test, dass Bild aus Datenbank entfernt wurde
        $this->assertNull($this->foundsImageRepository->find($image->getId()));
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
        $image->setName($name);
        $image->createdAt = new \DateTime();
        $image->latitude = 52.5200;
        $image->longitude = 13.4050;
        $image->isPublic = false;

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        return $image;
    }

    private function createTestImageFile(string $uploadDir): string
    {
        $imageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $tempFile = tempnam($uploadDir, 'test_image_');
        file_put_contents($tempFile, $imageData);
        
        return $tempFile;
    }

    private function createTestTextFile(string $uploadDir): string
    {
        $tempFile = tempnam($uploadDir, 'test_text_');
        file_put_contents($tempFile, 'This is not an image file');
        
        return $tempFile;
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