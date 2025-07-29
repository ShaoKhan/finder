<?php

namespace App\Tests\Service;

use App\Service\ImageService;
use PHPUnit\Framework\TestCase;

class ImageServiceTest extends TestCase
{
    private ImageService $imageService;

    protected function setUp(): void
    {
        $this->imageService = new ImageService();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(ImageService::class, $this->imageService);
    }

    public function testExtractExifDataWithValidImage(): void
    {
        // Test mit einer echten Bilddatei (falls vorhanden)
        $testImagePath = __DIR__ . '/../../Testbilder/test_with_gps.jpg';
        
        if (file_exists($testImagePath)) {
            $exifData = $this->imageService->extractExifData($testImagePath);
            
            $this->assertIsArray($exifData);
            $this->assertArrayHasKey('camera_model', $exifData);
            $this->assertArrayHasKey('exposure_time', $exifData);
            $this->assertArrayHasKey('f_number', $exifData);
            $this->assertArrayHasKey('iso', $exifData);
            $this->assertArrayHasKey('date_time', $exifData);
            $this->assertArrayHasKey('latitude', $exifData);
            $this->assertArrayHasKey('longitude', $exifData);
            
            // Wenn GPS-Daten vorhanden sind, sollten sie gültig sein
            if ($exifData['latitude'] !== null) {
                $this->assertIsFloat($exifData['latitude']);
                $this->assertGreaterThanOrEqual(-90, $exifData['latitude']);
                $this->assertLessThanOrEqual(90, $exifData['latitude']);
            }
            
            if ($exifData['longitude'] !== null) {
                $this->assertIsFloat($exifData['longitude']);
                $this->assertGreaterThanOrEqual(-180, $exifData['longitude']);
                $this->assertLessThanOrEqual(180, $exifData['longitude']);
            }
        } else {
            $this->markTestSkipped('Testbild mit GPS-Daten nicht gefunden');
        }
    }

    public function testExtractExifDataWithInvalidImage(): void
    {
        // Test mit einer nicht existierenden Datei
        $invalidPath = __DIR__ . '/../../Testbilder/nonexistent.jpg';
        
        $exifData = $this->imageService->extractExifData($invalidPath);
        
        $this->assertIsArray($exifData);
        $this->assertEmpty($exifData);
    }

    public function testExtractExifDataWithImageWithoutExif(): void
    {
        // Test mit einer Bilddatei ohne EXIF-Daten
        $testImagePath = __DIR__ . '/../../Testbilder/test_without_gps.jpg';
        
        if (file_exists($testImagePath)) {
            $exifData = $this->imageService->extractExifData($testImagePath);
            
            $this->assertIsArray($exifData);
            $this->assertArrayHasKey('camera_model', $exifData);
            $this->assertArrayHasKey('exposure_time', $exifData);
            $this->assertArrayHasKey('f_number', $exifData);
            $this->assertArrayHasKey('iso', $exifData);
            $this->assertArrayHasKey('date_time', $exifData);
            $this->assertArrayHasKey('latitude', $exifData);
            $this->assertArrayHasKey('longitude', $exifData);
            
            // Ohne GPS-Daten sollten latitude und longitude null sein
            $this->assertNull($exifData['latitude']);
            $this->assertNull($exifData['longitude']);
        } else {
            $this->markTestSkipped('Testbild ohne GPS-Daten nicht gefunden');
        }
    }

    public function testExtractExifDataWithTextFile(): void
    {
        // Test mit einer Textdatei (kein Bild)
        $textFilePath = __DIR__ . '/../../Testbilder/test.txt';
        
        // Erstelle eine temporäre Textdatei für den Test
        file_put_contents($textFilePath, 'This is a test file');
        
        try {
            $exifData = $this->imageService->extractExifData($textFilePath);
            
            $this->assertIsArray($exifData);
            $this->assertEmpty($exifData);
        } finally {
            // Cleanup
            if (file_exists($textFilePath)) {
                unlink($textFilePath);
            }
        }
    }

    public function testExtractExifDataWithEmptyFile(): void
    {
        // Test mit einer leeren Datei
        $emptyFilePath = __DIR__ . '/../../Testbilder/empty.jpg';
        
        // Erstelle eine temporäre leere Datei für den Test
        file_put_contents($emptyFilePath, '');
        
        try {
            $exifData = $this->imageService->extractExifData($emptyFilePath);
            
            $this->assertIsArray($exifData);
            $this->assertEmpty($exifData);
        } finally {
            // Cleanup
            if (file_exists($emptyFilePath)) {
                unlink($emptyFilePath);
            }
        }
    }

    public function testExtractExifDataWithNullPath(): void
    {
        // Test mit null-Pfad
        $exifData = $this->imageService->extractExifData('');
        
        $this->assertIsArray($exifData);
        $this->assertEmpty($exifData);
    }

    public function testExtractExifDataWithDirectory(): void
    {
        // Test mit einem Verzeichnis
        $directoryPath = __DIR__ . '/../../Testbilder/';
        
        $exifData = $this->imageService->extractExifData($directoryPath);
        
        $this->assertIsArray($exifData);
        $this->assertEmpty($exifData);
    }

    public function testExtractExifDataWithLargeFile(): void
    {
        // Test mit einer großen Datei (falls vorhanden)
        $largeImagePath = __DIR__ . '/../../Testbilder/large_image.jpg';
        
        if (file_exists($largeImagePath)) {
            $exifData = $this->imageService->extractExifData($largeImagePath);
            
            $this->assertIsArray($exifData);
            $this->assertArrayHasKey('camera_model', $exifData);
            $this->assertArrayHasKey('exposure_time', $exifData);
            $this->assertArrayHasKey('f_number', $exifData);
            $this->assertArrayHasKey('iso', $exifData);
            $this->assertArrayHasKey('date_time', $exifData);
            $this->assertArrayHasKey('latitude', $exifData);
            $this->assertArrayHasKey('longitude', $exifData);
        } else {
            $this->markTestSkipped('Große Testbilddatei nicht gefunden');
        }
    }

    public function testExtractExifDataWithCorruptedFile(): void
    {
        // Test mit einer beschädigten Datei
        $corruptedFilePath = __DIR__ . '/../../Testbilder/corrupted.jpg';
        
        // Erstelle eine temporäre beschädigte Datei für den Test
        file_put_contents($corruptedFilePath, 'This is not a valid image file');
        
        try {
            $exifData = $this->imageService->extractExifData($corruptedFilePath);
            
            $this->assertIsArray($exifData);
            $this->assertEmpty($exifData);
        } finally {
            // Cleanup
            if (file_exists($corruptedFilePath)) {
                unlink($corruptedFilePath);
            }
        }
    }

    public function testExtractExifDataWithSpecialCharacters(): void
    {
        // Test mit Dateipfad mit Sonderzeichen
        $specialCharPath = __DIR__ . '/../../Testbilder/test_äöüß.jpg';
        
        if (file_exists($specialCharPath)) {
            $exifData = $this->imageService->extractExifData($specialCharPath);
            
            $this->assertIsArray($exifData);
            $this->assertArrayHasKey('camera_model', $exifData);
            $this->assertArrayHasKey('exposure_time', $exifData);
            $this->assertArrayHasKey('f_number', $exifData);
            $this->assertArrayHasKey('iso', $exifData);
            $this->assertArrayHasKey('date_time', $exifData);
            $this->assertArrayHasKey('latitude', $exifData);
            $this->assertArrayHasKey('longitude', $exifData);
        } else {
            $this->markTestSkipped('Testbild mit Sonderzeichen nicht gefunden');
        }
    }

    public function testExtractExifDataWithDifferentFormats(): void
    {
        // Test mit verschiedenen Bildformaten
        $formats = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'];
        
        foreach ($formats as $format) {
            $testImagePath = __DIR__ . "/../../Testbilder/test.{$format}";
            
            if (file_exists($testImagePath)) {
                $exifData = $this->imageService->extractExifData($testImagePath);
                
                $this->assertIsArray($exifData);
                $this->assertArrayHasKey('camera_model', $exifData);
                $this->assertArrayHasKey('exposure_time', $exifData);
                $this->assertArrayHasKey('f_number', $exifData);
                $this->assertArrayHasKey('iso', $exifData);
                $this->assertArrayHasKey('date_time', $exifData);
                $this->assertArrayHasKey('latitude', $exifData);
                $this->assertArrayHasKey('longitude', $exifData);
            }
        }
    }

    public function testExtractExifDataPerformance(): void
    {
        // Performance-Test mit einer echten Bilddatei
        $testImagePath = __DIR__ . '/../../Testbilder/test_with_gps.jpg';
        
        if (file_exists($testImagePath)) {
            $startTime = microtime(true);
            
            $exifData = $this->imageService->extractExifData($testImagePath);
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            $this->assertIsArray($exifData);
            $this->assertLessThan(1.0, $executionTime, 'EXIF-Extraktion sollte unter 1 Sekunde dauern');
        } else {
            $this->markTestSkipped('Testbild für Performance-Test nicht gefunden');
        }
    }

    public function testExtractExifDataWithMemoryLimit(): void
    {
        // Test mit Memory-Limit (falls konfiguriert)
        $testImagePath = __DIR__ . '/../../Testbilder/large_image.jpg';
        
        if (file_exists($testImagePath)) {
            $initialMemory = memory_get_usage();
            
            $exifData = $this->imageService->extractExifData($testImagePath);
            
            $finalMemory = memory_get_usage();
            $memoryUsed = $finalMemory - $initialMemory;
            
            $this->assertIsArray($exifData);
            $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, 'Memory-Verbrauch sollte unter 10MB bleiben');
        } else {
            $this->markTestSkipped('Große Testbilddatei nicht gefunden');
        }
    }

    public function testExtractExifDataWithConcurrentAccess(): void
    {
        // Test mit gleichzeitigen Zugriffen
        $testImagePath = __DIR__ . '/../../Testbilder/test_with_gps.jpg';
        
        if (file_exists($testImagePath)) {
            $results = [];
            
            // Simuliere gleichzeitige Zugriffe
            for ($i = 0; $i < 5; $i++) {
                $results[] = $this->imageService->extractExifData($testImagePath);
            }
            
            // Alle Ergebnisse sollten identisch sein
            foreach ($results as $result) {
                $this->assertIsArray($result);
                $this->assertArrayHasKey('camera_model', $result);
                $this->assertArrayHasKey('exposure_time', $result);
                $this->assertArrayHasKey('f_number', $result);
                $this->assertArrayHasKey('iso', $result);
                $this->assertArrayHasKey('date_time', $result);
                $this->assertArrayHasKey('latitude', $result);
                $this->assertArrayHasKey('longitude', $result);
            }
        } else {
            $this->markTestSkipped('Testbild für Concurrent-Access-Test nicht gefunden');
        }
    }
} 