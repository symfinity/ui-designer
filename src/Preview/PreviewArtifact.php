<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Preview;

final readonly class PreviewArtifact
{
    /**
     * @param array<string, string> $resolvedTokens `--ui-*` => CSS value
     */
    public function __construct(
        public string $themeId,
        public string $css,
        public array $resolvedTokens = [],
    ) {
    }
}
