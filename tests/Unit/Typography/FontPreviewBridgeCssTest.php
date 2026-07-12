<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Unit\Typography;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfinity\FontManager\Provider\ProviderRegistry;
use Symfinity\UiDesigner\Typography\FontManagerFontPreviewAdapter;
use Symfinity\UiDesigner\Typography\TypographySelection;

final class FontPreviewBridgeCssTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(ProviderRegistry::class)) {
            self::markTestSkipped('symfinity/font-manager is not installed.');
        }
    }

    #[Test]
    public function bridgeCssMapsBodyToSansAndHeadingToDisplay(): void
    {
        $selection = (new TypographySelection())
            ->withPairing('editorial', 'Playfair Display', 'Source Serif 4', 'JetBrains Mono');

        $css = $this->bridgeCss($selection);

        self::assertStringContainsString('--ui-font-family-sans:\'Source Serif 4\', sans-serif', $css);
        self::assertStringContainsString('--font-heading:\'Playfair Display\', serif', $css);
        self::assertStringContainsString('--ui-font-family-mono:\'JetBrains Mono\', monospace', $css);
        self::assertStringContainsString('[data-drawer-type-preview]', $css);
        self::assertStringContainsString('ui-designer-host-typography-css', $css);
    }

    private function bridgeCss(TypographySelection $selection): string
    {
        $catalogPath = dirname(__DIR__, 3) . '/config/font_pairing_catalog.yaml';
        $adapter = new FontManagerFontPreviewAdapter(
            new \Symfinity\FontManager\Provider\ProviderRegistry(),
            $this->createMock(\Symfinity\FontManager\Import\FontPairingImportPort::class),
            new \Symfinity\UiDesigner\Typography\FonttrioPairingCatalog($catalogPath),
        );

        $method = new \ReflectionMethod(FontManagerFontPreviewAdapter::class, 'renderBridgeCss');
        $method->setAccessible(true);

        /** @var string $css */
        $css = $method->invoke($adapter, $selection);

        return $css;
    }
}
