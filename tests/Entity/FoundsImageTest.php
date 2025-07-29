<?php

namespace App\Tests\Entity;

use App\Entity\FoundsImage;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class FoundsImageTest extends TestCase
{
    private FoundsImage $foundsImage;
    private User $user;

    protected function setUp(): void
    {
        $this->foundsImage = new FoundsImage();
        $this->user = new User();
        $this->user->setEmail('test@example.com');
    }

    public function testGettersAndSetters(): void
    {
        // Test name
        $this->foundsImage->setName('Test Image');
        $this->assertEquals('Test Image', $this->foundsImage->getName());

        // Test filePath
        $this->foundsImage->filePath = 'test-image.jpg';
        $this->assertEquals('test-image.jpg', $this->foundsImage->filePath);

        // Test note
        $this->foundsImage->note = 'Test note';
        $this->assertEquals('Test note', $this->foundsImage->note);

        // Test username
        $this->foundsImage->username = 'testuser';
        $this->assertEquals('testuser', $this->foundsImage->username);

        // Test user_uuid
        $this->foundsImage->user_uuid = 'test-uuid';
        $this->assertEquals('test-uuid', $this->foundsImage->user_uuid);

        // Test createdAt
        $date = new \DateTime();
        $this->foundsImage->createdAt = $date;
        $this->assertEquals($date, $this->foundsImage->createdAt);

        // Test UTM coordinates
        $this->foundsImage->utmX = 123.456;
        $this->foundsImage->utmY = 789.012;
        $this->assertEquals(123.456, $this->foundsImage->utmX);
        $this->assertEquals(789.012, $this->foundsImage->utmY);

        // Test GPS coordinates
        $this->foundsImage->latitude = 52.5200;
        $this->foundsImage->longitude = 13.4050;
        $this->assertEquals(52.5200, $this->foundsImage->latitude);
        $this->assertEquals(13.4050, $this->foundsImage->longitude);

        // Test location data
        $this->foundsImage->parcel = 'Test Parcel';
        $this->foundsImage->district = 'Test District';
        $this->foundsImage->county = 'Test County';
        $this->foundsImage->state = 'Test State';
        $this->foundsImage->nearestStreet = 'Test Street';
        $this->foundsImage->nearestTown = 'Test Town';

        $this->assertEquals('Test Parcel', $this->foundsImage->parcel);
        $this->assertEquals('Test District', $this->foundsImage->district);
        $this->assertEquals('Test County', $this->foundsImage->county);
        $this->assertEquals('Test State', $this->foundsImage->state);
        $this->assertEquals('Test Street', $this->foundsImage->nearestStreet);
        $this->assertEquals('Test Town', $this->foundsImage->nearestTown);

        // Test distance and church data
        $this->foundsImage->distanceToChurchOrCenter = 1.5;
        $this->foundsImage->churchOrCenterName = 'Test Church';
        $this->assertEquals(1.5, $this->foundsImage->distanceToChurchOrCenter);
        $this->assertEquals('Test Church', $this->foundsImage->churchOrCenterName);

        // Test camera data
        $this->foundsImage->cameraModel = 'Test Camera';
        $this->foundsImage->exposureTime = '1/100';
        $this->foundsImage->fNumber = 'f/2.8';
        $this->foundsImage->iso = 100;
        $this->foundsImage->dateTime = new \DateTime();

        $this->assertEquals('Test Camera', $this->foundsImage->cameraModel);
        $this->assertEquals('1/100', $this->foundsImage->exposureTime);
        $this->assertEquals('f/2.8', $this->foundsImage->fNumber);
        $this->assertEquals(100, $this->foundsImage->iso);
        $this->assertInstanceOf(\DateTime::class, $this->foundsImage->dateTime);

        // Test public flag
        $this->foundsImage->isPublic = true;
        $this->assertTrue($this->foundsImage->isPublic);

        // Test gemarkung data
        $this->foundsImage->gemarkungName = 'Test Gemarkung';
        $this->foundsImage->gemarkungNummer = '123';
        $this->foundsImage->flurstueckName = 'Test Flurstück';
        $this->foundsImage->flurstueckNummer = '456';

        $this->assertEquals('Test Gemarkung', $this->foundsImage->gemarkungName);
        $this->assertEquals('123', $this->foundsImage->gemarkungNummer);
        $this->assertEquals('Test Flurstück', $this->foundsImage->flurstueckName);
        $this->assertEquals('456', $this->foundsImage->flurstueckNummer);
    }

    public function testUserRelationship(): void
    {
        $this->foundsImage->setUser($this->user);
        $this->assertEquals($this->user, $this->foundsImage->getUser());

        $this->foundsImage->setUser(null);
        $this->assertNull($this->foundsImage->getUser());
    }

    public function testIdManagement(): void
    {
        $this->foundsImage->setId(1);
        $this->assertEquals(1, $this->foundsImage->getId());

        $this->foundsImage->setId(null);
        $this->assertNull($this->foundsImage->getId());
    }

    public function testSetNearestStreet(): void
    {
        $this->foundsImage->setNearestStreet('Test Street');
        // Diese Methode ist aktuell leer, aber wir testen, dass sie aufgerufen werden kann
        $this->assertTrue(true);
    }

    public function testDefaultValues(): void
    {
        $newImage = new FoundsImage();
        
        $this->assertNull($newImage->getName());
        $this->assertNull($newImage->getId());
        $this->assertNull($newImage->getUser());
        $this->assertFalse($newImage->isPublic);
    }
} 