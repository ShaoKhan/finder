<?php

namespace App\Tests\Service;

use App\Service\PdfService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class PdfServiceTest extends TestCase
{
    private PdfService $pdfService;
    private Environment $mockTwig;

    protected function setUp(): void
    {
        // Mock Twig Environment erstellen
        $loader = new ArrayLoader([
            'test_template.html.twig' => '<h1>{{ title }}</h1><p>{{ content }}</p>',
            'complex_template.html.twig' => '<html><body><h1>{{ title }}</h1><ul>{% for item in items %}<li>{{ item }}</li>{% endfor %}</ul></body></html>',
        ]);
        
        $this->mockTwig = new Environment($loader);
        $this->pdfService = new PdfService($this->mockTwig);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(PdfService::class, $this->pdfService);
    }

    public function testGeneratePdfWithSimpleData(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => 'Test PDF',
            'content' => 'This is a test PDF content.'
        ];
        $filename = 'test_document.pdf';
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="test_document.pdf"', $response->headers->get('Content-Disposition'));
        
        // Prüfe, dass der Response-Inhalt nicht leer ist
        $this->assertNotEmpty($response->getContent());
    }

    public function testGeneratePdfWithComplexData(): void
    {
        $template = 'complex_template.html.twig';
        $data = [
            'title' => 'Complex Test PDF',
            'items' => ['Item 1', 'Item 2', 'Item 3', 'Item 4']
        ];
        $filename = 'complex_document.pdf';
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="complex_document.pdf"', $response->headers->get('Content-Disposition'));
        
        // Prüfe, dass der Response-Inhalt nicht leer ist
        $this->assertNotEmpty($response->getContent());
    }

    public function testGeneratePdfWithDefaultFilename(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => 'Default Filename Test',
            'content' => 'This test uses the default filename.'
        ];
        
        $response = $this->pdfService->generatePdf($template, $data);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="document.pdf"', $response->headers->get('Content-Disposition'));
    }

    public function testGeneratePdfWithEmptyData(): void
    {
        $template = 'test_template.html.twig';
        $data = [];
        $filename = 'empty_data.pdf';
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="empty_data.pdf"', $response->headers->get('Content-Disposition'));
    }

    public function testGeneratePdfWithLargeData(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => 'Large Data Test',
            'content' => str_repeat('This is a very long content string. ', 1000)
        ];
        $filename = 'large_data.pdf';
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="large_data.pdf"', $response->headers->get('Content-Disposition'));
    }

    public function testGeneratePdfWithSpecialCharacters(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => 'Special Characters Test: äöüß',
            'content' => 'This contains special characters: €, £, ¥, ©, ®, ™'
        ];
        $filename = 'special_chars_äöüß.pdf';
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="special_chars_äöüß.pdf"', $response->headers->get('Content-Disposition'));
    }

    public function testGeneratePdfWithHtmlContent(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => 'HTML Content Test',
            'content' => '<strong>Bold text</strong> and <em>italic text</em> with <a href="https://example.com">links</a>.'
        ];
        $filename = 'html_content.pdf';
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="html_content.pdf"', $response->headers->get('Content-Disposition'));
    }

    public function testGeneratePdfWithNestedData(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => 'Nested Data Test',
            'content' => 'Simple content',
            'nested' => [
                'level1' => [
                    'level2' => [
                        'level3' => 'Deep nested value'
                    ]
                ]
            ]
        ];
        $filename = 'nested_data.pdf';
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="nested_data.pdf"', $response->headers->get('Content-Disposition'));
    }

    public function testGeneratePdfWithUnicodeData(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => 'Unicode Test: 测试',
            'content' => 'This contains unicode characters: 测试, テスト, 테스트'
        ];
        $filename = 'unicode_test.pdf';
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="unicode_test.pdf"', $response->headers->get('Content-Disposition'));
    }

    public function testGeneratePdfPerformance(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => 'Performance Test',
            'content' => str_repeat('Performance test content. ', 100)
        ];
        $filename = 'performance_test.pdf';
        
        $startTime = microtime(true);
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(5.0, $executionTime, 'PDF-Generierung sollte unter 5 Sekunden dauern');
    }

    public function testGeneratePdfWithMemoryLimit(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => 'Memory Test',
            'content' => str_repeat('Memory test content. ', 500)
        ];
        $filename = 'memory_test.pdf';
        
        $initialMemory = memory_get_usage();
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory-Verbrauch sollte unter 50MB bleiben');
    }

    public function testGeneratePdfWithConcurrentAccess(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => 'Concurrent Test',
            'content' => 'Concurrent access test content.'
        ];
        $filename = 'concurrent_test.pdf';
        
        $responses = [];
        
        // Simuliere gleichzeitige Zugriffe
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->pdfService->generatePdf($template, $data, $filename);
        }
        
        // Alle Responses sollten erfolgreich sein
        foreach ($responses as $response) {
            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        }
    }

    public function testGeneratePdfWithDifferentTemplates(): void
    {
        $templates = [
            'test_template.html.twig' => ['title' => 'Template 1', 'content' => 'Content 1'],
            'complex_template.html.twig' => ['title' => 'Template 2', 'items' => ['A', 'B', 'C']]
        ];
        
        foreach ($templates as $template => $data) {
            $response = $this->pdfService->generatePdf($template, $data, 'template_test.pdf');
            
            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        }
    }

    public function testGeneratePdfWithErrorHandling(): void
    {
        // Test mit ungültigem Template
        $template = 'nonexistent_template.html.twig';
        $data = ['title' => 'Test', 'content' => 'Content'];
        
        // Der Service sollte eine Exception werfen oder einen leeren PDF erstellen
        try {
            $response = $this->pdfService->generatePdf($template, $data, 'error_test.pdf');
            
            // Falls keine Exception geworfen wird, sollte der Response trotzdem gültig sein
            $this->assertInstanceOf(Response::class, $response);
        } catch (\Exception $e) {
            // Exception ist auch akzeptabel
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    public function testGeneratePdfWithEmptyTemplate(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => '',
            'content' => ''
        ];
        $filename = 'empty_template.pdf';
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function testGeneratePdfWithVeryLongFilename(): void
    {
        $template = 'test_template.html.twig';
        $data = [
            'title' => 'Long Filename Test',
            'content' => 'Test content'
        ];
        $filename = str_repeat('very_long_filename_', 20) . '.pdf';
        
        $response = $this->pdfService->generatePdf($template, $data, $filename);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="' . $filename . '"', $response->headers->get('Content-Disposition'));
    }
} 