<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Unit\Preview;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfinity\UiDesigner\Authoring\DtcgRolePathMapper;
use Symfinity\UiDesigner\Authoring\DtcgSemanticEditor;
use Symfinity\UiDesigner\Kernel\BuiltinDraftLoader;
use Symfinity\UiDesigner\Preview\DesignerPreviewPort;
use Symfinity\UiDesigner\Session\DesignerDraftSession;
use Symfinity\UiDesigner\Studio\StructuralPreviewOverlay;
use Symfinity\UiKernel\Css\CssGenerator;
use Symfinity\UiKernel\Dtcg\BuiltinDtcgThemeCatalog;
use Symfinity\UiKernel\Dtcg\DesignSystemLayerRegistry;
use Symfinity\UiKernel\Dtcg\LayerStackBuilder;
use Symfinity\UiKernel\Dtcg\LayeredTokenResolver;
use Symfinity\UiKernel\Dtcg\ThemeDtcgResolver;
use Symfinity\UiKernel\Palette\PaletteRampSampler;
use Symfinity\UiKernel\Theme\ThemeInheritanceGraph;
use Symfinity\UiKernel\Theme\ThemeInheritanceMerger;
use Symfony\Component\HttpFoundation\RequestStack;

final class DesignerPreviewPortTest extends TestCase
{
    #[Test]
    public function previewSemanticBuiltin(): void
    {
        $catalog = new BuiltinDtcgThemeCatalog(BuiltinDtcgThemeCatalog::defaultDirectory());
        $session = new DesignerDraftSession(new RequestStack());
        $builtinLoader = new BuiltinDraftLoader($catalog, $session);
        $draft = $builtinLoader->loadVariant('semantic');

        $artifact = $this->previewPort($catalog)->preview($draft);
        self::assertNotSame('', trim($artifact->css));
        self::assertStringContainsString('[data-theme="semantic"]', $artifact->css);
        self::assertStringContainsString('--ui-overlay-surface:', $artifact->css);
    }

    #[Test]
    public function previewAfterRoleEdit(): void
    {
        $catalog = new BuiltinDtcgThemeCatalog(BuiltinDtcgThemeCatalog::defaultDirectory());
        $session = new DesignerDraftSession(new RequestStack());
        $builtinLoader = new BuiltinDraftLoader($catalog, $session);
        $draft = $builtinLoader->loadVariant('semantic');
        $editor = new DtcgSemanticEditor(new DtcgRolePathMapper());
        $updated = $editor->assignRoleRef($draft, 'primary', 'red.500');

        $artifact = $this->previewPort($catalog)->preview($updated);
        self::assertStringContainsString('[data-theme="semantic"]', $artifact->css);
    }

    private function previewPort(BuiltinDtcgThemeCatalog $catalog): DesignerPreviewPort
    {
        $graph = ThemeInheritanceGraph::fromCatalog($catalog);
        $stackBuilder = new LayerStackBuilder(
            DesignSystemLayerRegistry::fromDefaultDirectory(),
            inheritanceMerger: new ThemeInheritanceMerger($graph),
        );

        return new DesignerPreviewPort(
            $catalog,
            new ThemeDtcgResolver($stackBuilder, new LayeredTokenResolver()),
            new CssGenerator(),
            new StructuralPreviewOverlay(),
            new DtcgSemanticEditor(new DtcgRolePathMapper()),
            new PaletteRampSampler(),
        );
    }
}
