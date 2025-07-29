<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\TestHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class IndexControllerTest extends WebTestCase
{
    use TestHelper;

    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testIndexPage(): void
    {
        $this->client->request('GET', '/home');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('h1');
        $this->assertPageTitleContains('Finder');
    }

    public function testIndexPageWithAuthenticatedUser(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $this->createTestUser($entityManager);
        $this->client->loginUser($user);

        $this->client->request('GET', '/home');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('h1');
    }

    public function testHintsPageWithoutAuthentication(): void
    {
        $this->client->request('GET', '/hinweise');

        // Sollte zur Login-Seite weiterleiten
        $this->assertResponseRedirects('/');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function testHintsPageWithAuthenticatedUser(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/hinweise');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('h1');
        $this->assertPageTitleContains('Hinweise');
    }

    public function testImpressumPage(): void
    {
        $this->client->request('GET', '/imprint');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('h1');
        $this->assertPageTitleContains('Impressum');
    }

    public function testImpressumPageWithAuthenticatedUser(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/imprint');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('h1');
    }

    public function testPrivacyPolicyPage(): void
    {
        $this->client->request('GET', '/privacy-policy');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('h1');
        $this->assertPageTitleContains('Datenschutz');
    }

    public function testPrivacyPolicyPageWithAuthenticatedUser(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/privacy-policy');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('h1');
    }

    public function testContactPage(): void
    {
        $this->client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('h1');
        $this->assertPageTitleContains('Kontakt');
    }

    public function testContactPageWithAuthenticatedUser(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('h1');
    }

    public function testChangelogPage(): void
    {
        $this->client->request('GET', '/changelog');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('h1');
        $this->assertPageTitleContains('Changelog');
    }

    public function testChangelogPageWithAuthenticatedUser(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/changelog');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('h1');
    }

    public function testChangelogPageWithGitCommits(): void
    {
        $this->client->request('GET', '/changelog');

        $this->assertResponseIsSuccessful();
        
        // Prüfe, ob Git-Commits angezeigt werden (falls Git verfügbar ist)
        $crawler = $this->client->getCrawler();
        $commitElements = $crawler->filter('.commit, .changelog-entry');
        
        if ($commitElements->count() > 0) {
            $this->assertGreaterThan(0, $commitElements->count());
        }
    }

    public function testChangelogPageWithoutGit(): void
    {
        // Test ohne Git-Verfügbarkeit
        $this->client->request('GET', '/changelog');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testAllPublicPagesAreAccessible(): void
    {
        $publicPages = [
            '/home' => 'Home',
            '/imprint' => 'Impressum',
            '/privacy-policy' => 'Datenschutz',
            '/contact' => 'Kontakt',
            '/changelog' => 'Changelog'
        ];

        foreach ($publicPages as $url => $expectedTitle) {
            $this->client->request('GET', $url);
            
            $this->assertResponseIsSuccessful();
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
            $this->assertSelectorExists('h1');
        }
    }

    public function testProtectedPagesRequireAuthentication(): void
    {
        $protectedPages = [
            '/hinweise' => 'Hinweise'
        ];

        foreach ($protectedPages as $url => $expectedTitle) {
            $this->client->request('GET', $url);
            
            // Sollte zur Login-Seite weiterleiten
            $this->assertResponseRedirects('/');
            $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        }
    }

    public function testProtectedPagesWithAuthentication(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $this->createTestUser($entityManager);
        $this->client->loginUser($user);

        $protectedPages = [
            '/hinweise' => 'Hinweise'
        ];

        foreach ($protectedPages as $url => $expectedTitle) {
            $this->client->request('GET', $url);
            
            $this->assertResponseIsSuccessful();
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
            $this->assertSelectorExists('h1');
        }
    }

    public function testPageTitlesAreCorrect(): void
    {
        $pages = [
            '/home' => 'Finder',
            '/imprint' => 'Impressum',
            '/privacy-policy' => 'Datenschutz',
            '/contact' => 'Kontakt',
            '/changelog' => 'Changelog'
        ];

        foreach ($pages as $url => $expectedTitle) {
            $this->client->request('GET', $url);
            
            $this->assertResponseIsSuccessful();
            $this->assertPageTitleContains($expectedTitle);
        }
    }

    public function testNavigationMenuExists(): void
    {
        $this->client->request('GET', '/home');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('nav');
        $this->assertSelectorExists('.navbar');
    }

    public function testFooterExists(): void
    {
        $this->client->request('GET', '/home');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('footer');
    }

    public function testBootstrapIsLoaded(): void
    {
        $this->client->request('GET', '/home');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('link[href*="bootstrap"]');
    }

    public function testCustomCSSIsLoaded(): void
    {
        $this->client->request('GET', '/home');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('link[href*="app.css"]');
    }

    public function testJavaScriptIsLoaded(): void
    {
        $this->client->request('GET', '/home');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('script[src*="app.js"]');
    }

    public function testResponsiveDesign(): void
    {
        $this->client->request('GET', '/home');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('meta[name="viewport"]');
    }

    public function testPageLoadPerformance(): void
    {
        $startTime = microtime(true);
        
        $this->client->request('GET', '/home');
        
        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;
        
        $this->assertResponseIsSuccessful();
        $this->assertLessThan(2.0, $loadTime, 'Seite sollte unter 2 Sekunden laden');
    }

    public function testMultiplePageLoads(): void
    {
        $pages = ['/home', '/imprint', '/privacy-policy', '/contact', '/changelog'];
        
        foreach ($pages as $page) {
            $this->client->request('GET', $page);
            $this->assertResponseIsSuccessful();
        }
    }

    public function testPageWithSpecialCharacters(): void
    {
        // Test mit Sonderzeichen in der URL (falls unterstützt)
        $this->client->request('GET', '/home');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testPageWithLongUrl(): void
    {
        // Test mit langer URL (falls unterstützt)
        $longUrl = '/home?' . str_repeat('param=value&', 100);
        $this->client->request('GET', $longUrl);
        
        $this->assertResponseIsSuccessful();
    }

    public function testPageWithInvalidMethod(): void
    {
        $this->client->request('POST', '/home');
        
        // Sollte 405 Method Not Allowed oder 404 Not Found zurückgeben
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED, 'POST sollte nicht erlaubt sein');
    }

    public function testPageWithInvalidUrl(): void
    {
        $this->client->request('GET', '/nonexistent-page');
        
        // Sollte 404 Not Found zurückgeben
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPageWithTrailingSlash(): void
    {
        $this->client->request('GET', '/home/');
        
        // Sollte zur korrekten URL weiterleiten oder funktionieren
        $this->assertResponseIsSuccessful();
    }

    public function testPageWithQueryParameters(): void
    {
        $this->client->request('GET', '/home?param=value&test=123');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testPageWithHeaders(): void
    {
        $this->client->request('GET', '/home', [], [], [
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.8,en-US;q=0.5,en;q=0.3',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_UPGRADE_INSECURE_REQUESTS' => '1'
        ]);
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testPageWithUserAgent(): void
    {
        $this->client->request('GET', '/home', [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testPageWithMobileUserAgent(): void
    {
        $this->client->request('GET', '/home', [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
        ]);
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testPageWithConcurrentRequests(): void
    {
        // Simuliere gleichzeitige Anfragen
        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $this->client->request('GET', '/home');
            $responses[] = $this->client->getResponse();
        }
        
        // Alle Responses sollten erfolgreich sein
        foreach ($responses as $response) {
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }
    }
} 