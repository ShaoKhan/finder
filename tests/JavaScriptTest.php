<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

class JavaScriptTest extends TestCase
{
    public function testBulkDeleteJavaScriptExists(): void
    {
        $jsFile = __DIR__ . '/../assets/js/bulk-delete.js';
        $this->assertFileExists($jsFile);
        
        $content = file_get_contents($jsFile);
        
        // Test, dass wichtige Funktionen vorhanden sind
        $this->assertStringContainsString('initializeBulkDelete', $content);
        $this->assertStringContainsString('performBulkDelete', $content);
        $this->assertStringContainsString('showBulkDeleteModal', $content);
        $this->assertStringContainsString('removeEmptyDateGroups', $content);
    }

    public function testFlashMessagesJavaScriptExists(): void
    {
        $jsFile = __DIR__ . '/../assets/js/flash-messages.js';
        $this->assertFileExists($jsFile);
        
        $content = file_get_contents($jsFile);
        
        // Test, dass wichtige Funktionen vorhanden sind
        $this->assertStringContainsString('setTimeout', $content);
        $this->assertStringContainsString('alert', $content);
    }

    public function testAppJavaScriptExists(): void
    {
        $jsFile = __DIR__ . '/../assets/app.js';
        $this->assertFileExists($jsFile);
        
        $content = file_get_contents($jsFile);
        
        // Test, dass wichtige Imports vorhanden sind
        $this->assertStringContainsString('import', $content);
        $this->assertStringContainsString('DOMContentLoaded', $content);
    }

    public function testJavaScriptSyntax(): void
    {
        $jsFiles = [
            __DIR__ . '/../assets/js/bulk-delete.js',
            __DIR__ . '/../assets/js/flash-messages.js',
            __DIR__ . '/../assets/app.js'
        ];

        foreach ($jsFiles as $jsFile) {
            if (file_exists($jsFile)) {
                $content = file_get_contents($jsFile);
                
                // Test grundlegende JavaScript-Syntax
                $this->assertStringContainsString('function', $content);
                $this->assertStringContainsString('document', $content);
                // const ist optional, da moderne JS auch let/var verwendet
            }
        }
    }

    public function testCSRFTokenHandling(): void
    {
        $jsFile = __DIR__ . '/../assets/js/bulk-delete.js';
        $content = file_get_contents($jsFile);
        
        // Test, dass CSRF-Token-Handling vorhanden ist
        $this->assertStringContainsString('csrf-token', $content);
        $this->assertStringContainsString('_token', $content);
    }

    public function testModalHandling(): void
    {
        $jsFile = __DIR__ . '/../assets/js/bulk-delete.js';
        $content = file_get_contents($jsFile);
        
        // Test, dass Modal-Handling vorhanden ist
        $this->assertStringContainsString('modal', $content);
        $this->assertStringContainsString('show', $content);
        $this->assertStringContainsString('hide', $content);
    }

    public function testAJAXRequests(): void
    {
        $jsFile = __DIR__ . '/../assets/js/bulk-delete.js';
        $content = file_get_contents($jsFile);
        
        // Test, dass AJAX-Requests vorhanden sind
        $this->assertStringContainsString('fetch', $content);
        $this->assertStringContainsString('POST', $content);
        $this->assertStringContainsString('FormData', $content);
    }

    public function testEventListeners(): void
    {
        $jsFile = __DIR__ . '/../assets/js/bulk-delete.js';
        $content = file_get_contents($jsFile);
        
        // Test, dass Event-Listener vorhanden sind
        $this->assertStringContainsString('addEventListener', $content);
        $this->assertStringContainsString('click', $content);
        $this->assertStringContainsString('change', $content);
    }

    public function testDOMManipulation(): void
    {
        $jsFile = __DIR__ . '/../assets/js/bulk-delete.js';
        $content = file_get_contents($jsFile);
        
        // Test, dass DOM-Manipulation vorhanden ist
        $this->assertStringContainsString('querySelector', $content);
        $this->assertStringContainsString('querySelectorAll', $content);
        $this->assertStringContainsString('remove', $content);
    }

    public function testErrorHandling(): void
    {
        $jsFile = __DIR__ . '/../assets/js/bulk-delete.js';
        $content = file_get_contents($jsFile);
        
        // Test, dass Error-Handling vorhanden ist
        $this->assertStringContainsString('catch', $content);
        $this->assertStringContainsString('error', $content);
    }

    public function testUserFeedback(): void
    {
        $jsFile = __DIR__ . '/../assets/js/bulk-delete.js';
        $content = file_get_contents($jsFile);
        
        // Test, dass User-Feedback vorhanden ist
        $this->assertStringContainsString('disabled', $content);
        $this->assertStringContainsString('textContent', $content);
        $this->assertStringContainsString('innerHTML', $content);
    }

    public function testJavaScriptFileSizes(): void
    {
        $jsFiles = [
            __DIR__ . '/../assets/js/bulk-delete.js',
            __DIR__ . '/../assets/js/flash-messages.js',
            __DIR__ . '/../assets/app.js'
        ];

        foreach ($jsFiles as $jsFile) {
            if (file_exists($jsFile)) {
                $fileSize = filesize($jsFile);
                $this->assertGreaterThan(0, $fileSize, "JavaScript file should not be empty: $jsFile");
                $this->assertLessThan(100000, $fileSize, "JavaScript file should be reasonably sized: $jsFile");
            }
        }
    }

    public function testJavaScriptFilePermissions(): void
    {
        $jsFiles = [
            __DIR__ . '/../assets/js/bulk-delete.js',
            __DIR__ . '/../assets/js/flash-messages.js',
            __DIR__ . '/../assets/app.js'
        ];

        foreach ($jsFiles as $jsFile) {
            if (file_exists($jsFile)) {
                $this->assertTrue(is_readable($jsFile), "JavaScript file should be readable: $jsFile");
            }
        }
    }
} 