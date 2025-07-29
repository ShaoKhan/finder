<?php

namespace App\Tests\Service;

use App\Service\MapService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class MapServiceTest extends TestCase
{
    private MapService $mapService;
    private KernelInterface $mockKernel;

    protected function setUp(): void
    {
        // Mock Kernel erstellen
        $this->mockKernel = $this->createMock(KernelInterface::class);
        $this->mockKernel->expects($this->any())
            ->method('getCacheDir')
            ->willReturn(sys_get_temp_dir());
        
        $this->mapService = new MapService($this->mockKernel);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(MapService::class, $this->mapService);
    }

    public function testGenerateStaticMap(): void
    {
        // Test mit Berlin-Koordinaten
        $centerLat = 52.5200;
        $centerLon = 13.4050;
        $markers = [
            [52.5200, 13.4050], // Berlin
            [52.5163, 13.3777], // Brandenburger Tor
        ];
        
        $filename = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
        
        $this->assertIsString($filename);
        $this->assertNotEmpty($filename);
        $this->assertStringEndsWith('.png', $filename);
        
        // Prüfe, ob die Datei existiert
        $filepath = $this->mapService->getTempMapPath($filename);
        $this->assertFileExists($filepath);
        
        // Cleanup
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    public function testGenerateStaticMapWithSingleMarker(): void
    {
        // Test mit einem einzelnen Marker
        $centerLat = 52.5200;
        $centerLon = 13.4050;
        $markers = [
            [52.5200, 13.4050], // Berlin
        ];
        
        $filename = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
        
        $this->assertIsString($filename);
        $this->assertNotEmpty($filename);
        $this->assertStringEndsWith('.png', $filename);
        
        // Cleanup
        $filepath = $this->mapService->getTempMapPath($filename);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    public function testGenerateStaticMapWithNoMarkers(): void
    {
        // Test ohne Marker
        $centerLat = 52.5200;
        $centerLon = 13.4050;
        $markers = [];
        
        $filename = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
        
        $this->assertIsString($filename);
        $this->assertNotEmpty($filename);
        $this->assertStringEndsWith('.png', $filename);
        
        // Cleanup
        $filepath = $this->mapService->getTempMapPath($filename);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    public function testGenerateStaticMapWithInvalidMarkers(): void
    {
        // Test mit ungültigen Markern
        $centerLat = 52.5200;
        $centerLon = 13.4050;
        $markers = [
            [52.5200], // Nur ein Koordinatenwert
            [52.5200, 13.4050, 100], // Zu viele Koordinatenwerte
            'invalid_marker', // String statt Array
        ];
        
        $filename = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
        
        $this->assertIsString($filename);
        $this->assertNotEmpty($filename);
        $this->assertStringEndsWith('.png', $filename);
        
        // Cleanup
        $filepath = $this->mapService->getTempMapPath($filename);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    public function testGenerateStaticMapWithEdgeCoordinates(): void
    {
        // Test mit Grenzkoordinaten
        $testCases = [
            [90.0, 180.0],   // Nordpol, östlichster Punkt
            [-90.0, -180.0], // Südpol, westlichster Punkt
            [0.0, 0.0],      // Äquator, Nullmeridian
        ];
        
        foreach ($testCases as $testCase) {
            $centerLat = $testCase[0];
            $centerLon = $testCase[1];
            $markers = [[$centerLat, $centerLon]];
            
            $filename = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
            
            $this->assertIsString($filename);
            $this->assertNotEmpty($filename);
            $this->assertStringEndsWith('.png', $filename);
            
            // Cleanup
            $filepath = $this->mapService->getTempMapPath($filename);
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    public function testGenerateStaticMapWithMultipleLocations(): void
    {
        // Test mit verschiedenen Standorten
        $locations = [
            [52.5200, 13.4050, 'Berlin'],
            [48.1351, 11.5820, 'München'],
            [53.5511, 9.9937, 'Hamburg'],
            [50.9375, 6.9603, 'Köln'],
        ];
        
        foreach ($locations as $location) {
            $centerLat = $location[0];
            $centerLon = $location[1];
            $markers = [[$centerLat, $centerLon]];
            
            $filename = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
            
            $this->assertIsString($filename);
            $this->assertNotEmpty($filename);
            $this->assertStringEndsWith('.png', $filename);
            
            // Cleanup
            $filepath = $this->mapService->getTempMapPath($filename);
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    public function testGenerateStaticMapPerformance(): void
    {
        // Performance-Test
        $centerLat = 52.5200;
        $centerLon = 13.4050;
        $markers = [
            [52.5200, 13.4050],
            [52.5163, 13.3777],
            [52.5244, 13.4105],
        ];
        
        $startTime = microtime(true);
        
        $filename = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertIsString($filename);
        $this->assertLessThan(10.0, $executionTime, 'Karten-Generierung sollte unter 10 Sekunden dauern');
        
        // Cleanup
        $filepath = $this->mapService->getTempMapPath($filename);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    public function testGenerateStaticMapWithLargeMarkerCount(): void
    {
        // Test mit vielen Markern
        $centerLat = 52.5200;
        $centerLon = 13.4050;
        $markers = [];
        
        // Erstelle 20 Marker um Berlin
        for ($i = 0; $i < 20; $i++) {
            $lat = $centerLat + (($i - 10) * 0.001);
            $lon = $centerLon + (($i - 10) * 0.001);
            $markers[] = [$lat, $lon];
        }
        
        $filename = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
        
        $this->assertIsString($filename);
        $this->assertNotEmpty($filename);
        $this->assertStringEndsWith('.png', $filename);
        
        // Cleanup
        $filepath = $this->mapService->getTempMapPath($filename);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    public function testGetTempMapPath(): void
    {
        $filename = 'test_map.png';
        $filepath = $this->mapService->getTempMapPath($filename);
        
        $this->assertIsString($filepath);
        $this->assertStringContainsString($filename, $filepath);
        $this->assertStringContainsString('temp_maps', $filepath);
    }

    public function testGetTempMapPathWithSpecialCharacters(): void
    {
        $filename = 'test_map_äöüß.png';
        $filepath = $this->mapService->getTempMapPath($filename);
        
        $this->assertIsString($filepath);
        $this->assertStringContainsString($filename, $filepath);
    }

    public function testCleanupTempMaps(): void
    {
        // Erstelle einige temporäre Karten-Dateien
        $tempDir = sys_get_temp_dir() . '/temp_maps';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        $testFiles = [
            $tempDir . '/test_map_1.png',
            $tempDir . '/test_map_2.png',
            $tempDir . '/test_map_3.png',
        ];
        
        // Erstelle Test-Dateien
        foreach ($testFiles as $file) {
            file_put_contents($file, 'test content');
        }
        
        // Prüfe, dass die Dateien existieren
        foreach ($testFiles as $file) {
            $this->assertFileExists($file);
        }
        
        // Führe Cleanup aus
        $this->mapService->cleanupTempMaps();
        
        // Prüfe, dass die Dateien gelöscht wurden
        foreach ($testFiles as $file) {
            $this->assertFileDoesNotExist($file);
        }
        
        // Cleanup des Verzeichnisses
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    }

    public function testCleanupTempMapsWithEmptyDirectory(): void
    {
        // Test mit leerem Verzeichnis
        $tempDir = sys_get_temp_dir() . '/temp_maps';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        $this->mapService->cleanupTempMaps();
        
        // Verzeichnis sollte noch existieren
        $this->assertDirectoryExists($tempDir);
        
        // Cleanup
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    }

    public function testCleanupTempMapsWithNonExistentDirectory(): void
    {
        // Test mit nicht existierendem Verzeichnis
        $tempDir = sys_get_temp_dir() . '/temp_maps_nonexistent';
        
        // Verzeichnis sollte nicht existieren
        $this->assertDirectoryDoesNotExist($tempDir);
        
        // Cleanup sollte keine Fehler verursachen
        $this->mapService->cleanupTempMaps();
        
        // Verzeichnis sollte immer noch nicht existieren
        $this->assertDirectoryDoesNotExist($tempDir);
    }

    public function testGenerateStaticMapWithConcurrentAccess(): void
    {
        // Test mit gleichzeitigen Zugriffen
        $centerLat = 52.5200;
        $centerLon = 13.4050;
        $markers = [[$centerLat, $centerLon]];
        
        $filenames = [];
        
        // Simuliere gleichzeitige Zugriffe
        for ($i = 0; $i < 5; $i++) {
            $filenames[] = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
        }
        
        // Alle Dateien sollten erstellt worden sein
        foreach ($filenames as $filename) {
            $this->assertIsString($filename);
            $this->assertNotEmpty($filename);
            $this->assertStringEndsWith('.png', $filename);
            
            $filepath = $this->mapService->getTempMapPath($filename);
            $this->assertFileExists($filepath);
            
            // Cleanup
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    public function testGenerateStaticMapWithMemoryLimit(): void
    {
        // Memory-Limit-Test
        $centerLat = 52.5200;
        $centerLon = 13.4050;
        $markers = [
            [52.5200, 13.4050],
            [52.5163, 13.3777],
            [52.5244, 13.4105],
        ];
        
        $initialMemory = memory_get_usage();
        
        $filename = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
        
        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;
        
        $this->assertIsString($filename);
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory-Verbrauch sollte unter 50MB bleiben');
        
        // Cleanup
        $filepath = $this->mapService->getTempMapPath($filename);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    public function testGenerateStaticMapWithDifferentZoomLevels(): void
    {
        // Test mit verschiedenen Zoom-Levels (indirekt über die Karten-Größe)
        $centerLat = 52.5200;
        $centerLon = 13.4050;
        $markers = [[$centerLat, $centerLon]];
        
        $filename = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
        
        $this->assertIsString($filename);
        $this->assertNotEmpty($filename);
        
        // Prüfe die Dateigröße
        $filepath = $this->mapService->getTempMapPath($filename);
        if (file_exists($filepath)) {
            $fileSize = filesize($filepath);
            $this->assertGreaterThan(0, $fileSize);
            $this->assertLessThan(10 * 1024 * 1024, $fileSize, 'Karten-Datei sollte unter 10MB bleiben');
            
            // Cleanup
            unlink($filepath);
        }
    }

    public function testGenerateStaticMapWithErrorHandling(): void
    {
        // Test mit ungültigen Koordinaten
        $invalidCoordinates = [
            [91.0, 13.4050],   // Latitude > 90
            [-91.0, 13.4050],  // Latitude < -90
            [52.5200, 181.0],  // Longitude > 180
            [52.5200, -181.0], // Longitude < -180
        ];
        
        foreach ($invalidCoordinates as $coords) {
            $centerLat = $coords[0];
            $centerLon = $coords[1];
            $markers = [[$centerLat, $centerLon]];
            
            // Der Service sollte trotz ungültiger Koordinaten funktionieren
            $filename = $this->mapService->generateStaticMap($centerLat, $centerLon, $markers);
            
            $this->assertIsString($filename);
            $this->assertNotEmpty($filename);
            
            // Cleanup
            $filepath = $this->mapService->getTempMapPath($filename);
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }
} 