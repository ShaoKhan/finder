<?php

namespace App\Tests\Service;

use App\Service\GeoService;
use PHPUnit\Framework\TestCase;

class GeoServiceBearingTest extends TestCase
{
    private GeoService $geoService;

    protected function setUp(): void
    {
        $this->geoService = new GeoService([]);
    }

    public function testCalculateBearingReturnsValidDirection(): void
    {
        // Test: Prüfe, ob eine gültige Himmelsrichtung zurückgegeben wird
        $berlinLat = 52.5200;
        $berlinLon = 13.4050;
        $hamburgLat = 53.5511;
        $hamburgLon = 9.9937;

        $direction = $this->geoService->calculateBearing($berlinLat, $berlinLon, $hamburgLat, $hamburgLon);
        
        $validDirections = ['N', 'NO', 'O', 'SO', 'S', 'SW', 'W', 'NW'];
        $this->assertContains($direction, $validDirections);
    }

    public function testCalculateBearingDifferentLocations(): void
    {
        // Test: Verschiedene Standorte sollten verschiedene Richtungen ergeben
        $berlinLat = 52.5200;
        $berlinLon = 13.4050;
        
        $hamburgLat = 53.5511;
        $hamburgLon = 9.9937;
        
        $muenchenLat = 48.1351;
        $muenchenLon = 11.5820;

        $direction1 = $this->geoService->calculateBearing($berlinLat, $berlinLon, $hamburgLat, $hamburgLon);
        $direction2 = $this->geoService->calculateBearing($berlinLat, $berlinLon, $muenchenLat, $muenchenLon);
        
        // Die Richtungen sollten unterschiedlich sein
        $this->assertNotEquals($direction1, $direction2);
        
        // Beide sollten gültige Himmelsrichtungen sein
        $validDirections = ['N', 'NO', 'O', 'SO', 'S', 'SW', 'W', 'NW'];
        $this->assertContains($direction1, $validDirections);
        $this->assertContains($direction2, $validDirections);
    }

    public function testCalculateBearingSameLocation(): void
    {
        // Test: Gleicher Standort sollte Fallback zurückgeben
        $lat = 52.5200;
        $lon = 13.4050;

        $direction = $this->geoService->calculateBearing($lat, $lon, $lat, $lon);
        $this->assertEquals('N', $direction); // Fallback für gleiche Koordinaten
    }

    public function testCalculateBearingWithZeroCoordinates(): void
    {
        // Test: Mit Null-Island Koordinaten
        $lat1 = 0.0;
        $lon1 = 0.0;
        $lat2 = 1.0;
        $lon2 = 1.0;

        $direction = $this->geoService->calculateBearing($lat1, $lon1, $lat2, $lon2);
        
        $validDirections = ['N', 'NO', 'O', 'SO', 'S', 'SW', 'W', 'NW'];
        $this->assertContains($direction, $validDirections);
    }
}
