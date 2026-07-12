<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfinity\UiDesigner\Tests\Integration\UiDesignerTestKernel;

final class AppPreviewHostTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return UiDesignerTestKernel::class;
    }

    protected static function createKernel(array $options = []): UiDesignerTestKernel
    {
        return new UiDesignerTestKernel('dev', true);
    }

    #[Test]
    public function previewSurvivesNavigation(): void
    {
        $client = $this->startPreviewSession();
        $query = $this->previewQueryFromClient($client);
        $client->request('GET', '/host', $query);
        self::assertResponseIsSuccessful();
        $homeHtml = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('id="ui-designer-preview-css"', $homeHtml);

        $crawler = $client->getCrawler();
        $kernelLink = $crawler->filter('main#host-home a[href*="kernel"]')->first();
        self::assertGreaterThan(0, $kernelLink->count());

        $client->click($kernelLink->link());
        self::assertResponseIsSuccessful();
        $kernelHtml = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('id="ui-designer-preview-css"', $kernelHtml);
        self::assertStringContainsString('id="host-kernel"', $kernelHtml);
    }

    #[Test]
    public function exitRevokesToken(): void
    {
        $client = $this->startPreviewSession();
        $query = $this->previewQueryFromClient($client);
        $client->request('GET', '/host', $query);
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('ui-designer-preview-css', $html);

        if (preg_match('/preview=([a-f0-9]+)/', $html, $matches) !== 1) {
            self::fail('Expected preview token in host HTML.');
        }

        $client->request('GET', '/ui-designer/preview/exit?preview=' . $matches[1]);
        $client->followRedirect();

        $client->request('GET', '/host');
        self::assertResponseIsSuccessful();
        $afterExit = $client->getResponse()->getContent() ?: '';
        self::assertStringNotContainsString('Preview:', $afterExit);
    }

    #[Test]
    public function fontsRouteKeepsPreviewBanner(): void
    {
        $client = $this->startPreviewSession();
        $query = $this->previewQueryFromClient($client);
        $client->request('GET', '/host/fonts', $query);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('id="host-fonts"', $client->getResponse()->getContent() ?: '');
        self::assertStringContainsString('Preview:', $client->getResponse()->getContent() ?: '');
    }

    #[Test]
    public function previewSurvivesSeparateHttpRequests(): void
    {
        $client = static::createClient();
        $client->request('POST', '/ui-designer/studio/load', ['theme_id' => 'semantic']);
        $client->followRedirect();
        $client->request('POST', '/ui-designer/drawer/start', ['return_url' => '/host']);
        $client->followRedirect();

        parse_str(parse_url($client->getRequest()->getUri(), PHP_URL_QUERY) ?: '', $query);
        self::assertNotEmpty($query['preview'] ?? '');

        // Same client, new kernel boot per request — session cookie must carry draft + preview binding.
        $client->request('GET', '/host', $query);
        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('id="ui-designer-preview-css"', $html);
        self::assertStringContainsString('Preview:', $html);
        self::assertStringNotContainsString('Preview session expired', $html);
    }

    #[Test]
    public function drawerCloseAndReopen(): void
    {
        $client = $this->startPreviewSession();
        $query = $this->previewQueryFromClient($client);
        $client->request('GET', '/host', $query);
        self::assertResponseIsSuccessful();
        $openHtml = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('id="ui-designer-drawer"', $openHtml);
        self::assertStringContainsString('has-ui-designer-drawer', $openHtml);

        $client->request('POST', '/ui-designer/drawer/close', ['return_url' => '/host']);
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        $closedHtml = $client->getResponse()->getContent() ?: '';
        self::assertStringNotContainsString('id="ui-designer-drawer"', $closedHtml);
        self::assertStringContainsString('Preview:', $closedHtml);
        self::assertStringContainsString('Open UI Designer', $closedHtml);
        self::assertStringNotContainsString('has-ui-designer-drawer', $closedHtml);

        $client->request('GET', '/ui-designer/drawer/fragment');
        self::assertResponseStatusCodeSame(204);

        $client->request('POST', '/ui-designer/drawer/reopen', ['return_url' => '/host']);
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        $reopenedHtml = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('id="ui-designer-drawer"', $reopenedHtml);
        self::assertStringContainsString('has-ui-designer-drawer', $reopenedHtml);
        self::assertStringNotContainsString('Open UI Designer', $reopenedHtml);

        $client->request('POST', '/ui-designer/drawer/close', ['return_url' => '/host']);
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('id="ui-designer-drawer"', $client->getResponse()->getContent() ?: '');
        self::assertStringContainsString('Open UI Designer', $client->getResponse()->getContent() ?: '');

        $client->request('POST', '/ui-designer/drawer/reopen', ['return_url' => '/host']);
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('id="ui-designer-drawer"', $client->getResponse()->getContent() ?: '');
    }

    #[Test]
    public function closeFormUsesHostReturnUrlWhenEmbeddedInHost(): void
    {
        $client = $this->startPreviewSession();
        $query = $this->previewQueryFromClient($client);
        $client->request('GET', '/host', $query);
        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('name="return_url" value="/host"', $html);
        self::assertStringContainsString('data-ui-designer-route-label>/host<', $html);
        self::assertStringNotContainsString('value="/ui-designer/drawer/fragment"', $html);
    }

    #[Test]
    public function closeRedirectsBackToHostNotDrawerFragment(): void
    {
        $client = $this->startPreviewSession();
        $query = $this->previewQueryFromClient($client);
        $client->request('GET', '/host', $query);
        $client->request('POST', '/ui-designer/drawer/close', ['return_url' => '/ui-designer/drawer/fragment']);
        self::assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location') ?? '';
        self::assertStringStartsWith('/host', $location);
        self::assertStringNotContainsString('/ui-designer/drawer/fragment', $location);
    }

    #[Test]
    public function closeFormIncludesCsrfAndDisablesTurbo(): void
    {
        $client = $this->startPreviewSession();
        $client->request('GET', '/ui-designer/drawer/fragment');
        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('data-turbo="false"', $html);
        self::assertStringContainsString('data-controller="csrf-protection"', $html);
        self::assertStringContainsString('name="_csrf_token"', $html);
        self::assertStringContainsString('/ui-designer/drawer/close', $html);
    }

    private function startPreviewSession(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->request('POST', '/ui-designer/studio/load', ['theme_id' => 'semantic']);
        $client->followRedirect();
        $client->request('POST', '/ui-designer/drawer/start', ['return_url' => '/host']);
        $client->followRedirect();

        return $client;
    }

    /**
     * @return array{preview: string, polarity: string}
     */
    private function previewQueryFromClient(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): array
    {
        $uri = $client->getRequest()->getUri();
        parse_str(parse_url($uri, PHP_URL_QUERY) ?: '', $params);
        self::assertNotEmpty($params['preview'] ?? '');

        return [
            'preview' => (string) $params['preview'],
            'polarity' => (string) ($params['polarity'] ?? 'light'),
        ];
    }
}
