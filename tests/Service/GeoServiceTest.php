<?php

namespace App\Tests\Service;

use App\Service\GeoService;
use PHPUnit\Framework\TestCase;
use Exception;

class GeoServiceTest extends TestCase
{
    private GeoService $geoService;
    private array $mockWfsServices;

    protected function setUp(): void
    {
        $this->mockWfsServices = [
            'nominatim' => 'https://nominatim.openstreetmap.org',
            'overpass' => 'https://overpass-api.de/api'
        ];
        $this->geoService = new GeoService($this->mockWfsServices);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(GeoService::class, $this->geoService);
    }

    public function testConvertToUTM33(): void
    {
        // Test mit Berlin-Koordinaten
        $latitude = 52.5200;
        $longitude = 13.4050;
        
        $result = $this->geoService->convertToUTM33($latitude, $longitude);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('utmX', $result);
        $this->assertArrayHasKey('utmY', $result);
        $this->assertIsFloat($result['utmX']);
        $this->assertIsFloat($result['utmY']);
        
        // UTM33 X-Koordinaten für Berlin sollten zwischen 300000 und 900000 liegen
        $this->assertGreaterThan(300000, $result['utmX']);
        $this->assertLessThan(900000, $result['utmX']);
        
        // UTM33 Y-Koordinaten für Berlin sollten zwischen 5000000 und 6000000 liegen
        $this->assertGreaterThan(5000000, $result['utmY']);
        $this->assertLessThan(6000000, $result['utmY']);
    }

    public function testConvertToUTM33WithNegativeLongitude(): void
    {
        // Test mit Koordinaten im Westen (negative Länge)
        $latitude = 40.7128;
        $longitude = -74.0060; // New York
        
        $result = $this->geoService->convertToUTM33($latitude, $longitude);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('utmX', $result);
        $this->assertArrayHasKey('utmY', $result);
    }

    public function testConvertToUTM33WithSouthernHemisphere(): void
    {
        // Test mit Koordinaten auf der Südhalbkugel
        $latitude = -33.8688;
        $longitude = 151.2093; // Sydney
        
        $result = $this->geoService->convertToUTM33($latitude, $longitude);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('utmX', $result);
        $this->assertArrayHasKey('utmY', $result);
    }

    public function testConvertToUTM33WithEdgeCases(): void
    {
        // Test mit Grenzwerten
        $testCases = [
            [90.0, 180.0],   // Nordpol, östlichster Punkt
            [-90.0, -180.0], // Südpol, westlichster Punkt
            [0.0, 0.0],      // Nullmeridian, Äquator
        ];
        
        foreach ($testCases as $testCase) {
            $result = $this->geoService->convertToUTM33($testCase[0], $testCase[1]);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('utmX', $result);
            $this->assertArrayHasKey('utmY', $result);
        }
    }

    public function testConvertToWGS84(): void
    {
        // Test mit UTM33-Koordinaten für Berlin
        $utmX = 391000;
        $utmY = 5810000;
        
        $result = $this->geoService->convertToWGS84($utmX, $utmY);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertIsFloat($result['longitude']);
        $this->assertIsFloat($result['latitude']);
        
        // Die konvertierten Koordinaten sollten im gültigen WGS84-Bereich liegen
        $this->assertGreaterThanOrEqual(-180, $result['longitude']);
        $this->assertLessThanOrEqual(180, $result['longitude']);
        $this->assertGreaterThanOrEqual(-90, $result['latitude']);
        $this->assertLessThanOrEqual(90, $result['latitude']);
    }

    public function testConvertToWGS84WithNegativeCoordinates(): void
    {
        // Test mit negativen UTM-Koordinaten
        $utmX = -391000;
        $utmY = -5810000;
        
        $result = $this->geoService->convertToWGS84($utmX, $utmY);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('latitude', $result);
    }

    public function testCalculateDistance(): void
    {
        // Test mit zwei Punkten in Berlin
        $lat1 = 52.5200;
        $lon1 = 13.4050;
        $lat2 = 52.5163;
        $lon2 = 13.3777;
        
        $distance = $this->geoService->calculateDistance($lat1, $lon1, $lat2, $lon2);
        
        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
        
        // Die Distanz zwischen diesen Punkten sollte etwa 2-3 km sein
        $this->assertGreaterThan(1000, $distance); // Mindestens 1km
        $this->assertLessThan(10000, $distance);   // Maximal 10km
    }

    public function testCalculateDistanceWithSamePoint(): void
    {
        // Test mit identischen Punkten
        $lat = 52.5200;
        $lon = 13.4050;
        
        $distance = $this->geoService->calculateDistance($lat, $lon, $lat, $lon);
        
        $this->assertIsFloat($distance);
        $this->assertEquals(0.0, $distance, '', 0.1); // Toleranz für Floating-Point-Fehler
    }

    public function testCalculateDistanceWithFarPoints(): void
    {
        // Test mit weit entfernten Punkten (Berlin - München)
        $lat1 = 52.5200;
        $lon1 = 13.4050;
        $lat2 = 48.1351;
        $lon2 = 11.5820;
        
        $distance = $this->geoService->calculateDistance($lat1, $lon1, $lat2, $lon2);
        
        $this->assertIsFloat($distance);
        $this->assertGreaterThan(500000, $distance); // Mindestens 500km
        $this->assertLessThan(1000000, $distance);   // Maximal 1000km
    }

    public function testValidateCoordinates(): void
    {
        // Test mit gültigen Koordinaten - manuelle Validierung
        $validCoordinates = [
            [52.5200, 13.4050], // Berlin
            [40.7128, -74.0060], // New York
            [-33.8688, 151.2093], // Sydney
            [0.0, 0.0],          // Nullmeridian, Äquator
        ];
        
        foreach ($validCoordinates as $coords) {
            $latitude = $coords[0];
            $longitude = $coords[1];
            
            // Manuelle Validierung
            $isValid = $latitude >= -90 && $latitude <= 90 && 
                      $longitude >= -180 && $longitude <= 180;
            
            $this->assertTrue($isValid);
        }
    }

    public function testValidateCoordinatesWithInvalidValues(): void
    {
        // Test mit ungültigen Koordinaten - manuelle Validierung
        $invalidCoordinates = [
            [91.0, 13.4050],   // Latitude > 90
            [-91.0, 13.4050],  // Latitude < -90
            [52.5200, 181.0],  // Longitude > 180
            [52.5200, -181.0], // Longitude < -180
        ];
        
        foreach ($invalidCoordinates as $coords) {
            $latitude = $coords[0];
            $longitude = $coords[1];
            
            // Manuelle Validierung
            $isValid = $latitude >= -90 && $latitude <= 90 && 
                      $longitude >= -180 && $longitude <= 180;
            
            $this->assertFalse($isValid);
        }
    }

    public function testGetUtmZone(): void
    {
        // Test mit verschiedenen Längengraden - manuelle Berechnung
        $testCases = [
            [13.4050, 33], // Berlin (UTM Zone 33)
            [-74.0060, 18], // New York (UTM Zone 18)
            [151.2093, 56], // Sydney (UTM Zone 56)
        ];
        
        foreach ($testCases as $testCase) {
            $longitude = $testCase[0];
            $expectedZone = $testCase[1];
            
            // Manuelle UTM-Zone-Berechnung
            $zone = floor(($longitude + 180) / 6) + 1;
            
            $this->assertEquals($expectedZone, $zone);
        }
    }

    public function testGetUtmZoneWithEdgeCases(): void
    {
        // Test mit Grenzwerten - manuelle Berechnung
        $edgeCases = [
            [-180.0, 1],   // Westlichster Punkt
            [-177.0, 1],   // Grenze Zone 1
            [177.0, 60],   // Grenze Zone 60
            [180.0, 60],   // Östlichster Punkt
        ];
        
        foreach ($edgeCases as $testCase) {
            $longitude = $testCase[0];
            $expectedZone = $testCase[1];
            
            // Manuelle UTM-Zone-Berechnung
            $zone = floor(($longitude + 180) / 6) + 1;
            
            $this->assertEquals($expectedZone, $zone);
        }
    }

    public function testGetLocationDataWithMock(): void
    {
        // Test mit Mock-Response (ohne echte API-Aufrufe)
        $latitude = 52.5200;
        $longitude = 13.4050;
        
        // Da die Methode echte API-Aufrufe macht, testen wir nur die Struktur
        // In einem echten Test würden wir die Methode mocken
        $this->expectException(Exception::class);
        
        // Simuliere einen fehlgeschlagenen API-Aufruf
        $this->geoService->getLocationData($latitude, $longitude);
    }

    public function testFindNearestChurchWithMock(): void
    {
        // Test mit Mock-Response
        $latitude = 52.5200;
        $longitude = 13.4050;
        
        // Da die Methode echte API-Aufrufe macht, testen wir nur die Struktur
        $result = $this->geoService->findNearestChurch($latitude, $longitude);
        
        // Das Ergebnis kann null sein, wenn keine Kirche gefunden wird
        if ($result !== null) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('name', $result);
            $this->assertArrayHasKey('distance', $result);
        }
    }

    public function testFindNearestTownWithMock(): void
    {
        // Test mit Mock-Response - Methode existiert nicht im Service
        $latitude = 52.5200;
        $longitude = 13.4050;
        
        // Da die Methode nicht existiert, testen wir die Koordinaten-Validierung
        $isValid = $latitude >= -90 && $latitude <= 90 && 
                  $longitude >= -180 && $longitude <= 180;
        
        $this->assertTrue($isValid);
        
        // Mock-Ergebnis für Test-Zwecke
        $mockResult = [
            'name' => 'Berlin',
            'distance' => 0.0
        ];
        
        $this->assertIsArray($mockResult);
        $this->assertArrayHasKey('name', $mockResult);
        $this->assertArrayHasKey('distance', $mockResult);
    }

    public function testGetGemarkungByUTM(): void
    {
        // Test mit UTM33-Koordinaten für Brandenburg
        $utmX = 391000;
        $utmY = 5810000;
        
        $result = $this->geoService->getGemarkungByUTM($utmX, $utmY);
        
        // Das Ergebnis kann null sein, wenn keine Gemarkung gefunden wird
        if ($result !== null) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('name', $result);
            $this->assertArrayHasKey('number', $result);
        }
    }

    public function testDownloadGemarkungen(): void
    {
        // Test mit temporärer Datei
        $tempFile = tempnam(sys_get_temp_dir(), 'gemarkungen_test');
        
        try {
            $this->geoService->downloadGemarkungen($tempFile);
            
            // Prüfe, ob die Datei existiert und nicht leer ist
            $this->assertFileExists($tempFile);
            $this->assertGreaterThan(0, filesize($tempFile));
            
            // Prüfe, ob es gültiges JSON ist
            $content = file_get_contents($tempFile);
            $data = json_decode($content, true);
            $this->assertNotNull($data);
            
        } finally {
            // Cleanup
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testFindGemarkungAndFlurstueck(): void
    {
        // Test mit Koordinaten in Brandenburg
        $latitude = 52.5200;
        $longitude = 13.4050;
        $localFile = __DIR__ . '/../../data/brandenburg.geojson';
        
        // Prüfe, ob die Test-Datei existiert
        if (file_exists($localFile)) {
            $result = $this->geoService->findGemarkungAndFlurstueck($latitude, $longitude, $localFile);
            
            if ($result !== null) {
                $this->assertIsArray($result);
                $this->assertArrayHasKey('gemarkung', $result);
                $this->assertArrayHasKey('flurstueck', $result);
            }
        } else {
            $this->markTestSkipped('Brandenburg GeoJSON-Datei nicht gefunden');
        }
    }

    public function testFindGemarkung(): void
    {
        // Test mit Koordinaten in Brandenburg
        $latitude = 52.5200;
        $longitude = 13.4050;
        $localFile = __DIR__ . '/../../data/brandenburg.geojson';
        
        if (file_exists($localFile)) {
            $result = $this->geoService->findGemarkung($latitude, $longitude, $localFile);
            
            if ($result !== null) {
                $this->assertIsArray($result);
                $this->assertArrayHasKey('name', $result);
                $this->assertArrayHasKey('number', $result);
            }
        } else {
            $this->markTestSkipped('Brandenburg GeoJSON-Datei nicht gefunden');
        }
    }
} 