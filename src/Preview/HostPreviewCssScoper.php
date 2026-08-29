<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Preview;

/**
 * Expands theme-scoped preview CSS so tokens apply on any host document (hub, kernel, …).
 *
 * Generated kernel CSS targets [data-theme="{id}"] only; consumer pages often omit that
 * attribute on their root. Duplicate selectors onto :root/html/body for live drawer sync.
 */
final class HostPreviewCssScoper
{
    public function scopeToHostDocument(
        string $css,
        string $themeId,
        ?string $surfaceColor = null,
        ?string $textColor = null,
    ): string {
        if ($themeId === '' || $themeId === 'default') {
            return $css;
        }

        $themeSelector = sprintf('[data-theme="%s"]', $themeId);
        $hostSelector = sprintf(':root, html, body, [data-ui-root], %s', $themeSelector);

        $scoped = str_replace($themeSelector, $hostSelector, $css);

        return $scoped . $this->hostDocumentPaintRules($surfaceColor, $textColor);
    }

    private function hostDocumentPaintRules(?string $surfaceColor, ?string $textColor): string
    {
        if ($surfaceColor !== null && $surfaceColor !== '' && $textColor !== null && $textColor !== '') {
            return sprintf(
                <<<'CSS'

/* Live preview paint — host page only; drawer uses fixed --ud-drawer-* chrome. */
:root, html, body {
    background-color: %s !important;
    color: %s !important;
}

CSS,
                $surfaceColor,
                $textColor,
            );
        }

        return <<<'CSS'

/* Live preview paint — host page only; drawer uses fixed --ud-drawer-* chrome. */
:root, html, body {
    background-color: var(--ui-color-surface) !important;
    color: var(--ui-color-text) !important;
}

CSS;
    }
}
