<?php

namespace App\Tests\Service;

use App\Service\WordService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class WordServiceTest extends TestCase
{
    private WordService $wordService;

    protected function setUp(): void
    {
        $this->wordService = new WordService();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(WordService::class, $this->wordService);
    }

    public function testGenerateWordWithSimpleData(): void
    {
        $data = [
            'Field 1' => 'Value 1',
            'Field 2' => 'Value 2',
            'Field 3' => 'Value 3'
        ];
        $filename = 'test_document.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="test_document.docx"', $response->headers->get('Content-Disposition'));
        
        // Prüfe, dass der Response-Inhalt nicht leer ist
        $this->assertNotEmpty($response->getContent());
    }

    public function testGenerateWordWithComplexData(): void
    {
        $data = [
            'Name' => 'John Doe',
            'Email' => 'john.doe@example.com',
            'Phone' => '+49 123 456789',
            'Address' => '123 Main Street, Berlin, Germany',
            'Date' => '2025-07-29',
            'Notes' => 'This is a test document with complex data.'
        ];
        $filename = 'complex_document.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="complex_document.docx"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordWithDefaultFilename(): void
    {
        $data = [
            'Test Field' => 'Test Value',
            'Another Field' => 'Another Value'
        ];
        
        $response = $this->wordService->generateWord($data);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="document.docx"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordWithEmptyData(): void
    {
        $data = [];
        $filename = 'empty_document.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="empty_document.docx"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordWithLargeData(): void
    {
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data["Field {$i}"] = "Value {$i} - " . str_repeat('A', 50);
        }
        $filename = 'large_document.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="large_document.docx"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordWithSpecialCharacters(): void
    {
        $data = [
            'Special Characters' => 'äöüß € £ ¥ © ® ™',
            'Unicode' => '测试 テスト 테스트',
            'Symbols' => '!@#$%^&*()_+-=[]{}|;:,.<>?'
        ];
        $filename = 'special_chars_äöüß.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="special_chars_äöüß.docx"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordWithNumericData(): void
    {
        $data = [
            'Integer' => 42,
            'Float' => 3.14159,
            'Zero' => 0,
            'Negative' => -123,
            'Large Number' => 999999999
        ];
        $filename = 'numeric_data.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="numeric_data.docx"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordWithBooleanData(): void
    {
        $data = [
            'True Value' => true,
            'False Value' => false,
            'String True' => 'true',
            'String False' => 'false'
        ];
        $filename = 'boolean_data.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="boolean_data.docx"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordWithNullData(): void
    {
        $data = [
            'Null Value' => null,
            'Empty String' => '',
            'Zero' => 0,
            'Normal Value' => 'Test'
        ];
        $filename = 'null_data.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="null_data.docx"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordWithLongFieldNames(): void
    {
        $data = [
            str_repeat('Very Long Field Name ', 20) => 'Short Value',
            'Normal Field' => str_repeat('Very Long Value ', 50),
            'Mixed' => 'Mixed content with ' . str_repeat('long text ', 30)
        ];
        $filename = 'long_names.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="long_names.docx"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordWithUnicodeData(): void
    {
        $data = [
            'Chinese' => '测试数据',
            'Japanese' => 'テストデータ',
            'Korean' => '테스트 데이터',
            'Arabic' => 'بيانات الاختبار',
            'Hebrew' => 'נתוני בדיקה',
            'Thai' => 'ข้อมูลทดสอบ'
        ];
        $filename = 'unicode_data.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="unicode_data.docx"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordPerformance(): void
    {
        $data = [];
        for ($i = 1; $i <= 50; $i++) {
            $data["Field {$i}"] = "Value {$i}";
        }
        $filename = 'performance_test.docx';
        
        $startTime = microtime(true);
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(5.0, $executionTime, 'Word-Generierung sollte unter 5 Sekunden dauern');
    }

    public function testGenerateWordWithMemoryLimit(): void
    {
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data["Field {$i}"] = str_repeat("Value {$i} ", 100);
        }
        $filename = 'memory_test.docx';
        
        $initialMemory = memory_get_usage();
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(100 * 1024 * 1024, $memoryUsed, 'Memory-Verbrauch sollte unter 100MB bleiben');
    }

    public function testGenerateWordWithConcurrentAccess(): void
    {
        $data = [
            'Field 1' => 'Value 1',
            'Field 2' => 'Value 2',
            'Field 3' => 'Value 3'
        ];
        $filename = 'concurrent_test.docx';
        
        $responses = [];
        
        // Simuliere gleichzeitige Zugriffe
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->wordService->generateWord($data, $filename);
        }
        
        // Alle Responses sollten erfolgreich sein
        foreach ($responses as $response) {
            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        }
    }

    public function testGenerateWordWithDifferentDataTypes(): void
    {
        $data = [
            'String' => 'String value',
            'Integer' => 42,
            'Float' => 3.14159,
            'Boolean True' => true,
            'Boolean False' => false,
            'Null' => null,
            'Empty String' => '',
            'Zero' => 0,
            'Negative' => -123
        ];
        $filename = 'mixed_types.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="mixed_types.docx"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordWithErrorHandling(): void
    {
        // Test mit ungültigen Daten
        $data = [
            'Valid Field' => 'Valid Value',
            'Invalid Field' => new \stdClass(), // Objekt sollte zu String konvertiert werden
        ];
        $filename = 'error_test.docx';
        
        try {
            $response = $this->wordService->generateWord($data, $filename);
            
            // Falls keine Exception geworfen wird, sollte der Response trotzdem gültig sein
            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals(200, $response->getStatusCode());
        } catch (\Exception $e) {
            // Exception ist auch akzeptabel
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    public function testGenerateWordWithVeryLongFilename(): void
    {
        $data = [
            'Field 1' => 'Value 1',
            'Field 2' => 'Value 2'
        ];
        $filename = str_repeat('very_long_filename_', 20) . '.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="' . $filename . '"', $response->headers->get('Content-Disposition'));
    }

    public function testGenerateWordWithEmptyFilename(): void
    {
        $data = [
            'Field 1' => 'Value 1'
        ];
        $filename = '';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
    }

    public function testGenerateWordWithSpecialFilename(): void
    {
        $data = [
            'Field 1' => 'Value 1'
        ];
        $filename = 'test document with spaces.docx';
        
        $response = $this->wordService->generateWord($data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="test document with spaces.docx"', $response->headers->get('Content-Disposition'));
    }
} 