<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Accessibility;

final class ColourContrastEvaluator
{
    /**
     * @return array{ratio: float, level: string, pass: bool, message: string}
     */
    public function evaluatePair(string $foregroundCss, string $backgroundCss): array
    {
        $ratio = $this->contrastRatio($foregroundCss, $backgroundCss);
        $pass = $ratio >= 4.5;
        $level = $ratio >= 7.0 ? 'AAA' : ($ratio >= 4.5 ? 'AA' : 'fail');

        return [
            'ratio' => $ratio,
            'level' => $level,
            'pass' => $pass,
            'message' => sprintf('Contrast ratio %.2f:1 (%s).', $ratio, $level),
        ];
    }

    private function contrastRatio(string $fg, string $bg): float
    {
        $l1 = $this->relativeLuminance($fg);
        $l2 = $this->relativeLuminance($bg);
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function relativeLuminance(string $cssColor): float
    {
        [$r, $g, $b] = $this->parseRgb($cssColor);
        $channels = array_map(static function (float $c): float {
            $c /= 255;
            if ($c <= 0.03928) {
                return $c / 12.92;
            }

            return (($c + 0.055) / 1.055) ** 2.4;
        }, [$r, $g, $b]);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function parseRgb(string $cssColor): array
    {
        $cssColor = trim($cssColor);
        if (preg_match('/^#([0-9a-f]{6})$/i', $cssColor, $m) === 1) {
            return [
                hexdec(substr($m[1], 0, 2)),
                hexdec(substr($m[1], 2, 2)),
                hexdec(substr($m[1], 4, 2)),
            ];
        }

        if (preg_match('/^rgb\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*\)$/i', $cssColor, $m) === 1) {
            return [(float) $m[1], (float) $m[2], (float) $m[3]];
        }

        if (preg_match('/^oklch\(\s*([\d.]+)%?\s+([\d.]+)\s+([\d.]+)/i', $cssColor, $m) === 1) {
            $l = (float) $m[1] / 100;

            return [$l * 255, $l * 255, $l * 255];
        }

        return [128.0, 128.0, 128.0];
    }
}
