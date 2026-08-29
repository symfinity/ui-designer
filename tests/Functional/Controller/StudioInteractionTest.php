<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfinity\UiDesigner\Tests\Integration\UiDesignerTestKernel;

final class StudioInteractionTest extends WebTestCase
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
    public function selectRole(): void
    {
        $client = $this->loadSemanticPreset();

        $client->request(
            'POST',
            '/ui-designer/studio/api/select-role',
            ['role' => 'danger'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok']);
        self::assertSame('danger', $payload['role']);
    }

    #[Test]
    public function previewCssSingleSource(): void
    {
        $client = $this->loadSemanticPreset();

        $client->request('GET', '/ui-designer/studio');
        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent() ?: '';

        self::assertSame(1, substr_count($html, 'id="theme-live-css"'));
        $previewBlock = $this->extractPreviewBlock($html);
        self::assertStringNotContainsString('<style', $previewBlock);
    }

    #[Test]
    public function applyPrimary(): void
    {
        $client = $this->loadSemanticPreset();

        $client->request(
            'POST',
            '/ui-designer/studio/api/apply-colour',
            ['role' => 'primary', 'ref' => 'red.500', 'confirm' => '0'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok']);
        self::assertNotEmpty($payload['preview_css']);
        self::assertSame('red.500', $payload['semantics']['primary'] ?? null);
    }

    #[Test]
    public function rampPick(): void
    {
        $client = $this->loadSemanticPreset();

        $client->request(
            'POST',
            '/ui-designer/studio/api/apply-colour',
            ['role' => 'primary', 'ref' => 'blue.600', 'confirm' => '0'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok']);
        self::assertSame('blue.600', $payload['semantics']['primary'] ?? null);
    }

    #[Test]
    public function structuralRadiusAndShadow(): void
    {
        $client = $this->loadSemanticPreset();

        $client->request(
            'POST',
            '/ui-designer/studio/api/structural',
            ['radius_step' => 'full', 'shadow_step' => 'lg'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok']);
        self::assertSame('full', $payload['structural']['radius_step'] ?? null);
        self::assertSame('lg', $payload['structural']['shadow_step'] ?? null);
        self::assertNotEmpty($payload['preview_css']);
    }

    #[Test]
    public function apiErrorSurfaces(): void
    {
        $client = $this->loadSemanticPreset();

        $client->request(
            'POST',
            '/ui-designer/studio/api/apply-colour',
            ['role' => 'primary', 'ref' => '#ff0000', 'confirm' => '0'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseStatusCodeSame(400);
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('error', $payload);
    }

    #[Test]
    public function previewModeToggle(): void
    {
        $client = $this->loadSemanticPreset();

        $client->request('GET', '/ui-designer/studio');
        self::assertStringContainsString('id="studio-mode-light"', $client->getResponse()->getContent() ?: '');

        $client->request(
            'POST',
            '/ui-designer/studio/api/preview-mode',
            ['mode' => 'dark'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok']);
        self::assertSame('dark', $payload['mode']);
        self::assertNotEmpty($payload['preview_css']);
    }

    #[Test]
    public function homeRedirectsToImportHub(): void
    {
        $client = static::createClient();
        $client->request('GET', '/ui-designer');
        self::assertResponseRedirects('/ui-designer/import');
    }

    #[Test]
    public function importHubShowsStartCustomizingAfterPresetLoad(): void
    {
        $client = $this->loadSemanticPreset();
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('Start customizing', $html);
        self::assertStringContainsString('/ui-designer/drawer/start', $html);
    }

    private function loadSemanticPreset(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->request('POST', '/ui-designer/studio/load', ['theme_id' => 'semantic']);
        $client->followRedirect();

        return $client;
    }

    private function extractPreviewBlock(string $html): string
    {
        if (!preg_match('/id="studio-preview"[^>]*>(.*?)<\/aside>/s', $html, $matches)) {
            return $html;
        }

        return $matches[1];
    }
}
