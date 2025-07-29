<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LoginControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testLoginPage(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }

    public function testLoginPageTitle(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleContains('Login');
    }

    public function testLoginFormExists(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[action="/login"]');
    }

    public function testLoginPageWithError(): void
    {
        // Simuliere einen fehlgeschlagenen Login
        $this->client->request('POST', '/login', [
            '_username' => 'invalid@example.com',
            '_password' => 'wrongpassword'
        ]);

        // Sollte zur Login-Seite zurückkehren
        $this->assertResponseRedirects('/');
    }

    public function testLogoutRoute(): void
    {
        $this->client->request('GET', '/logout');

        // Sollte zur Login-Seite weiterleiten
        $this->assertResponseRedirects('/');
    }

    public function testLoginPageWithLastUsername(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="_username"]');
    }

    public function testLoginPageWithCSRFToken(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testLoginPagePerformance(): void
    {
        $startTime = microtime(true);
        
        $this->client->request('GET', '/');
        
        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;
        
        $this->assertResponseIsSuccessful();
        $this->assertLessThan(1.0, $loadTime, 'Login-Seite sollte unter 1 Sekunde laden');
    }

    public function testLoginPageWithDifferentUserAgents(): void
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
        ];

        foreach ($userAgents as $userAgent) {
            $this->client->request('GET', '/', [], [], [
                'HTTP_USER_AGENT' => $userAgent
            ]);
            
            $this->assertResponseIsSuccessful();
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        }
    }

    public function testLoginPageWithHeaders(): void
    {
        $this->client->request('GET', '/', [], [], [
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.8,en-US;q=0.5,en;q=0.3',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_UPGRADE_INSECURE_REQUESTS' => '1'
        ]);
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testLoginPageWithQueryParameters(): void
    {
        $this->client->request('GET', '/?error=1&last_username=test@example.com');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testLoginPageWithInvalidMethod(): void
    {
        $this->client->request('PUT', '/');
        
        // Sollte 405 Method Not Allowed zurückgeben
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testLoginPageWithLongUrl(): void
    {
        $longUrl = '/?' . str_repeat('param=value&', 100);
        $this->client->request('GET', $longUrl);
        
        $this->assertResponseIsSuccessful();
    }

    public function testLoginPageWithSpecialCharacters(): void
    {
        $this->client->request('GET', '/?param=äöüß&test=€£¥');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testLoginPageWithConcurrentRequests(): void
    {
        // Simuliere gleichzeitige Anfragen
        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $this->client->request('GET', '/');
            $responses[] = $this->client->getResponse();
        }
        
        // Alle Responses sollten erfolgreich sein
        foreach ($responses as $response) {
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }
    }

    public function testLoginPageBootstrapLoaded(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('link[href*="bootstrap"]');
    }

    public function testLoginPageCustomCSSLoaded(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('link[href*="app.css"]');
    }

    public function testLoginPageJavaScriptLoaded(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('script[src*="app.js"]');
    }

    public function testLoginPageResponsiveDesign(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('meta[name="viewport"]');
    }

    public function testLoginPageNavigationExists(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('nav');
    }

    public function testLoginPageFooterExists(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('footer');
    }

    public function testLoginPageWithTrailingSlash(): void
    {
        $this->client->request('GET', '//');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testLoginPageWithMultipleSlashes(): void
    {
        $this->client->request('GET', '///');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testLoginPageWithInvalidUrl(): void
    {
        $this->client->request('GET', '/nonexistent-login');
        
        // Sollte 404 Not Found zurückgeben
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testLoginPageWithDifferentAcceptHeaders(): void
    {
        $acceptHeaders = [
            'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'application/json,text/html,application/xml;q=0.9,*/*;q=0.8',
            'text/plain,text/html,application/xml;q=0.9,*/*;q=0.8'
        ];

        foreach ($acceptHeaders as $acceptHeader) {
            $this->client->request('GET', '/', [], [], [
                'HTTP_ACCEPT' => $acceptHeader
            ]);
            
            $this->assertResponseIsSuccessful();
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        }
    }

    public function testLoginPageWithDifferentLanguages(): void
    {
        $languages = [
            'de-DE,de;q=0.8,en-US;q=0.5,en;q=0.3',
            'en-US,en;q=0.8,de-DE;q=0.5,de;q=0.3',
            'fr-FR,fr;q=0.8,en-US;q=0.5,en;q=0.3'
        ];

        foreach ($languages as $language) {
            $this->client->request('GET', '/', [], [], [
                'HTTP_ACCEPT_LANGUAGE' => $language
            ]);
            
            $this->assertResponseIsSuccessful();
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        }
    }
} 