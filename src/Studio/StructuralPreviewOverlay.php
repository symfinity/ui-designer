<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Studio;

use Symfinity\UiKernel\Theme\LayoutProfile;
use Symfinity\UiKernel\Token\ThemeTokenSchema;

final class StructuralPreviewOverlay
{
    public function overlayCss(
        string $themeId,
        string $baseCss,
        ?string $radiusStep,
        ?string $shadowStep,
        string $schemaVersion = '2.0',
    ): string {
        if ($radiusStep === null && $shadowStep === null) {
            return $baseCss;
        }

        $tokens = LayoutProfile::Semantic->layout($schemaVersion);
        $selector = sprintf('[data-theme="%s"]', addcslashes($themeId, '"\\'));
        $rules = [];

        if ($radiusStep !== null) {
            $key = '--ui-radius-' . $radiusStep;
            if (isset($tokens[$key])) {
                $rules[] = sprintf('--ui-radius-md: %s;', $tokens[$key]);
                $rules[] = sprintf('--ui-radius: %s;', $tokens[$key]);
            }
        }

        if ($shadowStep !== null) {
            $key = '--ui-shadow-' . $shadowStep;
            if (isset($tokens[$key])) {
                $rules[] = sprintf('--ui-shadow-md: %s;', $tokens[$key]);
                $rules[] = sprintf('--ui-shadow: %s;', $tokens[$key]);
            }
        }

        if ($rules === []) {
            return $baseCss;
        }

        ThemeTokenSchema::requiredKeys($schemaVersion);

        return $baseCss . "\n" . $selector . ' {' . implode(' ', $rules) . '}' . "\n";
    }
}
