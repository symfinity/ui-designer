<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Unit\Preview;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfinity\UiDesigner\Preview\HostPreviewCssScoper;

final class HostPreviewCssScoperTest extends TestCase
{
    #[Test]
    public function expandsThemeSelectorToHostRoots(): void
    {
        $css = <<<'CSS'
@layer kernel.tokens {
[data-theme="semantic"] {
  --ui-color-primary: #0060b7;
}
}
CSS;

        $scoped = (new HostPreviewCssScoper())->scopeToHostDocument($css, 'semantic', '#dedede', '#111111');

        self::assertStringContainsString(':root, html, body, [data-ui-root], [data-theme="semantic"]', $scoped);
        self::assertStringContainsString('background-color: #dedede !important', $scoped);
        self::assertStringContainsString('color: #111111 !important', $scoped);
        self::assertStringNotContainsString('.ui-designer-drawer', $scoped);
        self::assertStringContainsString('--ui-color-primary: #0060b7', $scoped);
    }

    #[Test]
    public function lightAndDarkHostPaintDiffersAfterModeToggle(): void
    {
        $scoper = new HostPreviewCssScoper();
        $css = '@layer kernel.tokens { [data-theme="semantic"] { --ui-color-surface: #dedede; } }';

        $light = $scoper->scopeToHostDocument($css, 'semantic', '#dedede', '#111111');
        $dark = $scoper->scopeToHostDocument(
            str_replace('semantic', 'semantic-dark', $css),
            'semantic-dark',
            '#171c20',
            '#f5f5f5',
        );

        self::assertStringContainsString('background-color: #dedede !important', $light);
        self::assertStringContainsString('background-color: #171c20 !important', $dark);
        self::assertMatchesRegularExpression('/background-color:\s*#dedede\s*!important/', $light);
        self::assertMatchesRegularExpression('/background-color:\s*#171c20\s*!important/', $dark);
    }

    #[Test]
    public function leavesDefaultThemeUntouched(): void
    {
        $css = '[data-theme="default"] { --ui-color-primary: red; }';
        $scoped = (new HostPreviewCssScoper())->scopeToHostDocument($css, 'default');

        self::assertSame($css, $scoped);
    }
}
