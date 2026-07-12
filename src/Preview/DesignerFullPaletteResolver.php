<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Preview;

use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfinity\UiKernel\Palette\PaletteGenerator;
use Symfinity\UiKernel\Token\MonoTone;
use Symfinity\UiKernel\Token\PaletteCatalog;
use Symfinity\UiKernel\Token\ThemeConfig;
use Symfinity\UiKernel\Token\ThemePaletteRecipe;

/**
 * Resolves every ui-kernel palette ref (hue + mono ramps) for drawer ramp picker.
 */
final class DesignerFullPaletteResolver
{
    public function __construct(
        private readonly PaletteGenerator $generator = new PaletteGenerator(),
    ) {
    }

    /**
     * @return array{
     *     hues: array<string, list<array{ref: string, color: string, level: int}>>,
     *     mono: array<string, list<array{ref: string, color: string, level: int}>>,
     *     levels: list<int>,
     *     activeTone: string,
     *     preset: string
     * }
     */
    public function resolve(DesignerDraft $draft): array
    {
        $config = ThemeConfig::get($draft->id);
        $recipe = $config->paletteRecipe();
        $levels = PaletteCatalog::levels();

        $hues = [];
        foreach (PaletteCatalog::hues() as $hue) {
            $steps = [];
            foreach ($levels as $level) {
                $ref = sprintf('%s.%d', $hue, $level);
                $steps[] = [
                    'ref' => $ref,
                    'color' => $this->generator->resolve($ref, $recipe),
                    'level' => $level,
                ];
            }
            $hues[$hue] = $steps;
        }

        $mono = [];
        foreach (PaletteCatalog::monoTones() as $toneName) {
            $mono[$toneName] = $this->monoRampSteps($toneName, $levels, $recipe);
        }

        $mono['pure'] = $this->monoRampSteps(MonoTone::Neutral->value, $levels, $recipe);

        $activeTone = $config->tone()->value;
        $activeKey = $activeTone === MonoTone::Neutral->value ? 'pure' : $activeTone;
        if (isset($mono[$activeKey])) {
            $active = [$activeKey => $mono[$activeKey]];
            unset($mono[$activeKey]);
            $mono = $active + $mono;
        }

        return [
            'hues' => $hues,
            'mono' => $mono,
            'levels' => $levels,
            'activeTone' => $activeTone,
            'preset' => $draft->id,
        ];
    }

    /**
     * @param list<int> $levels
     *
     * @return list<array{ref: string, color: string, level: int}>
     */
    private function monoRampSteps(string $toneName, array $levels, ThemePaletteRecipe $recipe): array
    {
        $steps = [];
        foreach ($levels as $level) {
            $ref = sprintf('mono.%s.%d', $toneName, $level);
            $steps[] = [
                'ref' => $ref,
                'color' => $this->generator->resolve($ref, $recipe),
                'level' => $level,
            ];
        }

        return $steps;
    }
}
