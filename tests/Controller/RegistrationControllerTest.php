<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RegistrationControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testRegistrationPage(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="registration_form[email]"]');
        $this->assertSelectorExists('input[name="registration_form[plainPassword]"]');
    }

    public function testRegistrationPageTitle(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleContains('Registrierung');
    }

    public function testRegistrationFormExists(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="registration_form"]');
    }

    public function testRegistrationWithValidData(): void
    {
        $this->client->request('POST', '/register', [
            'registration_form' => [
                'email' => 'test@example.com',
                'plainPassword' => 'TestPassword123!',
                '_token' => $this->getCSRFToken('/register')
            ]
        ]);

        // Sollte zur Login-Seite weiterleiten
        $this->assertResponseRedirects('/');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function testRegistrationWithInvalidEmail(): void
    {
        $this->client->request('POST', '/register', [
            'registration_form' => [
                'email' => 'invalid-email',
                'plainPassword' => 'TestPassword123!',
                '_token' => $this->getCSRFToken('/register')
            ]
        ]);

        // Sollte zur Registrierungsseite zurückkehren
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRegistrationWithWeakPassword(): void
    {
        $this->client->request('POST', '/register', [
            'registration_form' => [
                'email' => 'test@example.com',
                'plainPassword' => '123',
                '_token' => $this->getCSRFToken('/register')
            ]
        ]);

        // Sollte zur Registrierungsseite zurückkehren
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRegistrationWithEmptyData(): void
    {
        $this->client->request('POST', '/register', [
            'registration_form' => [
                'email' => '',
                'plainPassword' => '',
                '_token' => $this->getCSRFToken('/register')
            ]
        ]);

        // Sollte zur Registrierungsseite zurückkehren
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRegistrationWithDuplicateEmail(): void
    {
        // Erste Registrierung
        $this->client->request('POST', '/register', [
            'registration_form' => [
                'email' => 'duplicate@example.com',
                'plainPassword' => 'TestPassword123!',
                '_token' => $this->getCSRFToken('/register')
            ]
        ]);

        // Zweite Registrierung mit gleicher E-Mail
        $this->client->request('POST', '/register', [
            'registration_form' => [
                'email' => 'duplicate@example.com',
                'plainPassword' => 'TestPassword123!',
                '_token' => $this->getCSRFToken('/register')
            ]
        ]);

        // Sollte zur Registrierungsseite zurückkehren
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testEmailVerificationPage(): void
    {
        $this->client->request('GET', '/verify/email');

        // Sollte zur Registrierungsseite weiterleiten (ohne ID)
        $this->assertResponseRedirects('/register');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function testEmailVerificationWithInvalidId(): void
    {
        $this->client->request('GET', '/verify/email?id=999999');

        // Sollte zur Registrierungsseite weiterleiten
        $this->assertResponseRedirects('/register');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function testRegistrationPagePerformance(): void
    {
        $startTime = microtime(true);
        
        $this->client->request('GET', '/register');
        
        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;
        
        $this->assertResponseIsSuccessful();
        $this->assertLessThan(2.0, $loadTime, 'Registrierungsseite sollte unter 2 Sekunden laden');
    }

    public function testRegistrationPageWithDifferentUserAgents(): void
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
        ];

        foreach ($userAgents as $userAgent) {
            $this->client->request('GET', '/register', [], [], [
                'HTTP_USER_AGENT' => $userAgent
            ]);
            
            $this->assertResponseIsSuccessful();
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        }
    }

    public function testRegistrationPageWithHeaders(): void
    {
        $this->client->request('GET', '/register', [], [], [
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.8,en-US;q=0.5,en;q=0.3',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_UPGRADE_INSECURE_REQUESTS' => '1'
        ]);
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRegistrationPageWithQueryParameters(): void
    {
        $this->client->request('GET', '/register?error=1&email=test@example.com');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRegistrationPageWithInvalidMethod(): void
    {
        $this->client->request('PUT', '/register');
        
        // Sollte 405 Method Not Allowed zurückgeben
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testRegistrationPageWithLongUrl(): void
    {
        $longUrl = '/register?' . str_repeat('param=value&', 100);
        $this->client->request('GET', $longUrl);
        
        $this->assertResponseIsSuccessful();
    }

    public function testRegistrationPageWithSpecialCharacters(): void
    {
        $this->client->request('GET', '/register?param=äöüß&test=€£¥');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRegistrationPageWithConcurrentRequests(): void
    {
        // Simuliere gleichzeitige Anfragen
        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $this->client->request('GET', '/register');
            $responses[] = $this->client->getResponse();
        }
        
        // Alle Responses sollten erfolgreich sein
        foreach ($responses as $response) {
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }
    }

    public function testRegistrationPageBootstrapLoaded(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('link[href*="bootstrap"]');
    }

    public function testRegistrationPageCustomCSSLoaded(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('link[href*="app.css"]');
    }

    public function testRegistrationPageJavaScriptLoaded(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('script[src*="app.js"]');
    }

    public function testRegistrationPageResponsiveDesign(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('meta[name="viewport"]');
    }

    public function testRegistrationPageNavigationExists(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('nav');
    }

    public function testRegistrationPageFooterExists(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('footer');
    }

    public function testRegistrationPageWithTrailingSlash(): void
    {
        $this->client->request('GET', '/register/');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRegistrationPageWithInvalidUrl(): void
    {
        $this->client->request('GET', '/nonexistent-register');
        
        // Sollte 404 Not Found zurückgeben
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testRegistrationPageWithDifferentAcceptHeaders(): void
    {
        $acceptHeaders = [
            'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'application/json,text/html,application/xml;q=0.9,*/*;q=0.8',
            'text/plain,text/html,application/xml;q=0.9,*/*;q=0.8'
        ];

        foreach ($acceptHeaders as $acceptHeader) {
            $this->client->request('GET', '/register', [], [], [
                'HTTP_ACCEPT' => $acceptHeader
            ]);
            
            $this->assertResponseIsSuccessful();
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        }
    }

    public function testRegistrationPageWithDifferentLanguages(): void
    {
        $languages = [
            'de-DE,de;q=0.8,en-US;q=0.5,en;q=0.3',
            'en-US,en;q=0.8,de-DE;q=0.5,de;q=0.3',
            'fr-FR,fr;q=0.8,en-US;q=0.5,en;q=0.3'
        ];

        foreach ($languages as $language) {
            $this->client->request('GET', '/register', [], [], [
                'HTTP_ACCEPT_LANGUAGE' => $language
            ]);
            
            $this->assertResponseIsSuccessful();
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        }
    }

    private function getCSRFToken(string $url): string
    {
        $this->client->request('GET', $url);
        $crawler = $this->client->getCrawler();
        $token = $crawler->filter('input[name="_token"]')->attr('value');
        
        return $token ?? '';
    }
} 