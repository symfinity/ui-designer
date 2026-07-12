<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfinity\UiDesigner\Tests\Integration\UiDesignerTestKernel;

final class StudioDrawerTest extends WebTestCase
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
    public function drawerOpensOnStartCustomizing(): void
    {
        $client = $this->loadSemanticPreset();
        $client->request('POST', '/ui-designer/drawer/start', ['return_url' => '/host']);
        self::assertResponseRedirects();
        $client->followRedirect();

        $client->request('GET', '/ui-designer/drawer/fragment');
        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('ui-designer-drawer', $html);
        self::assertStringContainsString('UI Designer', $html);
    }

    #[Test]
    public function flatTokenListPresent(): void
    {
        $client = $this->loadSemanticPreset();
        $client->request('GET', '/ui-designer/drawer/fragment');
        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('ui-designer-token-list', $html);
        self::assertStringContainsString('data-drawer-token-row', $html);
        self::assertStringContainsString('>Primary<', $html);
        self::assertStringContainsString('>Surface<', $html);
        preg_match_all('/<div[^>]*data-drawer-token-row[^>]*data-role="([^"]+)"/', $html, $roleMatches);
        self::assertNotEmpty($roleMatches[1]);
        $roles = $roleMatches[1];
        self::assertSame(
            [
                'primary',
                'secondary',
                'accent',
                'neutral',
                'surface',
                'surface_elevated',
                'text',
                'text_muted',
                'border',
                'danger',
                'success',
                'warning',
                'info',
                'focus',
                'overlay',
            ],
            array_slice($roles, 0, 15),
            'Colour tokens must follow ui-kernel SemanticColorMap order.',
        );
        self::assertStringNotContainsString('ui-designer-token-group', $html);
        self::assertStringNotContainsString('>Brand<', $html);
        self::assertStringNotContainsString('drawer-tab-ai', $html);
        self::assertStringNotContainsString('> AI<', $html);
    }

    #[Test]
    public function modeTogglePresent(): void
    {
        $client = $this->loadSemanticPreset();
        $client->request('GET', '/ui-designer/drawer/fragment');
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('drawer-mode-light', $html);
        self::assertStringContainsString('drawer-mode-dark', $html);
    }

    #[Test]
    public function oklchApplyUpdatesSwatch(): void
    {
        $client = $this->loadSemanticPreset();

        $client->request(
            'POST',
            '/ui-designer/drawer/api/map-colour',
            ['role' => 'primary', 'literal' => 'oklch(0.62 0.21 260)'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseIsSuccessful();
        $mapped = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($mapped['ref']);

        $client->request(
            'POST',
            '/ui-designer/drawer/api/apply-colour',
            ['role' => 'primary', 'ref' => $mapped['ref'], 'confirm' => '0'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok']);
        self::assertSame($mapped['ref'], $payload['semantics']['primary'] ?? null);
        self::assertNotEmpty($payload['resolved_colors']['primary']['color'] ?? null);
    }

    #[Test]
    public function accessibleTabListPresent(): void
    {
        $client = $this->loadSemanticPreset();
        $client->request('GET', '/ui-designer/drawer/fragment');
        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('role="tablist"', $html);
        self::assertStringContainsString('id="drawer-tab-colours"', $html);
        self::assertStringContainsString('aria-controls="drawer-panel-colours"', $html);
        self::assertMatchesRegularExpression('/role="tab"[^>]*>\s*Colors\s*<\/button>/', $html);
        self::assertMatchesRegularExpression('/role="tab"[^>]*>\s*Other\s*<\/button>/', $html);
        self::assertStringContainsString('role="tabpanel"', $html);
    }

    #[Test]
    public function typographyFontsApiUnavailableWithoutFontManager(): void
    {
        $client = $this->loadSemanticPreset();
        $client->request(
            'POST',
            '/ui-designer/drawer/api/fonts/search',
            ['query' => 'Inter'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseStatusCodeSame(503);
    }

    #[Test]
    public function typographyPanelRendersWithoutFontManager(): void
    {
        $client = $this->loadSemanticPreset();
        $client->request('GET', '/ui-designer/drawer/fragment');
        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('data-drawer-typography', $html);
        self::assertStringContainsString('data-fonts-available="0"', $html);
        self::assertStringContainsString('symfinity/font-manager', $html);
        self::assertStringContainsString('data-drawer-type-preview', $html);
        self::assertStringContainsString('ui-designer-drawer-type-preview-fonts', $html);
        self::assertStringContainsString('data-drawer-type-mono', $html);
    }

    #[Test]
    public function drawerChromeUsesThemeSurfaceTokens(): void
    {
        $client = $this->loadSemanticPreset();
        $client->request('GET', '/ui-designer/drawer/fragment');
        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('--ud-drawer-bg: #0a0a0a', $html);
        self::assertStringContainsString('background: var(--ud-drawer-bg)', $html);
        self::assertStringContainsString('overflow-x: hidden', $html);
        self::assertStringNotContainsString('--ud-drawer-bg: var(--ui-color-surface', $html);
    }

    #[Test]
    public function previewModeSwitchReturnsThemeId(): void
    {
        $client = $this->loadSemanticPreset();
        $client->request(
            'POST',
            '/ui-designer/drawer/api/preview-mode',
            ['mode' => 'dark'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok']);
        self::assertSame('dark', $payload['mode']);
        self::assertSame('semantic-dark', $payload['theme_id']);
        self::assertNotEmpty($payload['preview_css']);
        self::assertStringNotContainsString('.ui-designer-drawer', $payload['preview_css']);
        self::assertStringContainsString('background-color:', $payload['preview_css']);
        self::assertStringContainsString('!important', $payload['preview_css']);

        $client->request(
            'POST',
            '/ui-designer/drawer/api/preview-mode',
            ['mode' => 'light'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseIsSuccessful();
        $lightPayload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($lightPayload['ok']);
        self::assertSame('semantic', $lightPayload['theme_id']);
        self::assertMatchesRegularExpression(
            '/background-color:\s*#dedede\s*!important/',
            $lightPayload['preview_css'],
        );
        self::assertMatchesRegularExpression(
            '/background-color:\s*#171c20\s*!important/',
            $payload['preview_css'],
        );
    }

    #[Test]
    public function paletteCatalogMarkupPresent(): void
    {
        $client = $this->loadSemanticPreset();
        $client->request('GET', '/ui-designer/drawer/fragment');
        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('ui-designer-palette-catalog', $html);
        self::assertStringContainsString('data-drawer-open-catalog', $html);
        self::assertStringContainsString('data-palette-search', $html);
        self::assertStringContainsString('ui-designer-palette-data', $html);
        self::assertStringContainsString('UI Designer', $html);
        self::assertStringContainsString('data-drawer-action="copy"', $html);
        self::assertStringNotContainsString('ui-designer-tailwind', $html);
        self::assertStringNotContainsString('TailwindV4', $html);

        $client->request(
            'GET',
            '/ui-designer/drawer/api/palette',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok']);
        self::assertNotEmpty($payload['palette']['hues']['blue'] ?? null);
    }

    #[Test]
    public function unintegratedDrawerChromeHidden(): void
    {
        $client = $this->loadSemanticPreset();
        $client->request('GET', '/ui-designer/drawer/fragment');
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringNotContainsString('id="drawer-icon-library"', $html);
        self::assertStringNotContainsString('id="drawer-style-label"', $html);
        self::assertStringNotContainsString('id="drawer-menu-color-label"', $html);
        self::assertStringNotContainsString('data-drawer-action="reset"', $html);
        self::assertStringNotContainsString('aria-label="Redo"', $html);
        self::assertStringNotContainsString('Random preset', $html);
        self::assertStringNotContainsString('Hold to save theme', $html);
        self::assertStringContainsString('data-drawer-action="copy"', $html);
        self::assertStringContainsString('ui-designer-drawer__preset-select', $html);
    }

    #[Test]
    public function otherTabStructuralControlsAreInteractive(): void
    {
        $client = $this->loadSemanticPreset();
        $client->request('GET', '/ui-designer/drawer/fragment');
        $html = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('ui-designer-drawer__panel--other', $html);
        self::assertMatchesRegularExpression(
            '/id="drawer-radius-step"[^>]*data-drawer-structural="radius_step"/',
            $html,
        );
        self::assertMatchesRegularExpression(
            '/id="drawer-shadow-step"[^>]*data-drawer-structural="shadow_step"/',
            $html,
        );
        self::assertDoesNotMatchRegularExpression(
            '/id="drawer-radius-step"[^>]*disabled/',
            $html,
        );
        self::assertDoesNotMatchRegularExpression(
            '/id="drawer-shadow-step"[^>]*disabled/',
            $html,
        );
    }

    private function loadSemanticPreset(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->request('POST', '/ui-designer/studio/load', ['theme_id' => 'semantic']);
        $client->followRedirect();
        $client->request('POST', '/ui-designer/drawer/start', ['return_url' => '/host']);
        $client->followRedirect();

        return $client;
    }
}
