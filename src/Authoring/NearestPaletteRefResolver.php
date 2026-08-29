<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Authoring;

use InvalidArgumentException;
use Symfinity\UiKernel\Palette\OklchColorSpace;
use Symfinity\UiKernel\Palette\OklchTuple;
use Symfinity\UiKernel\Palette\PaletteRampSampler;
use Symfinity\UiKernel\Palette\PaletteRampSamplerInterface;
use Symfinity\UiKernel\Token\PaletteCatalog;
use Symfinity\UiKernel\Token\PaletteRefGrammar;
use Symfinity\UiKernel\Token\ThemePaletteRecipe;

/**
 * Maps OKLCH literals to nearest palette ref — ports ui-themer behaviour without dependency.
 */
final class NearestPaletteRefResolver
{
    public function __construct(
        private readonly PaletteRampSamplerInterface $sampler = new PaletteRampSampler(),
        private readonly OklchColorSpace $colorSpace = new OklchColorSpace(),
    ) {
    }

    /**
     * @return array{ref: string, distance: float}
     */
    public function resolve(OklchTuple $source, ?string $preferredMonoTone = null, ?ThemePaletteRecipe $recipe = null): array
    {
        $candidates = $this->filterCandidates($source, $preferredMonoTone, $recipe);

        $bestRef = null;
        $bestDistance = INF;
        $bestLevel = PHP_INT_MAX;

        foreach ($candidates as $ref => $candidate) {
            PaletteRefGrammar::assertValid($ref);
            $distance = $this->colorSpace->deltaE($source, $candidate);
            $level = $this->extractLevel($ref);

            if ($distance < $bestDistance
                || ($distance === $bestDistance && $level < $bestLevel)
                || ($distance === $bestDistance && $level === $bestLevel && ($bestRef === null || $ref < $bestRef))) {
                $bestRef = $ref;
                $bestDistance = $distance;
                $bestLevel = $level;
            }
        }

        if ($bestRef === null) {
            throw new InvalidArgumentException('No palette ref candidates available.');
        }

        $ref = $bestRef;
        if ($source->alpha !== null && $source->alpha < 1.0) {
            $ref .= '@' . $this->nearestAlphaPercent($source->alpha);
        }

        return ['ref' => $ref, 'distance' => $bestDistance];
    }

    /**
     * @return array<string, OklchTuple>
     */
    private function filterCandidates(OklchTuple $source, ?string $preferredMonoTone, ?ThemePaletteRecipe $recipe): array
    {
        $samples = $recipe !== null
            ? $this->sampler->sampleFor($recipe)
            : $this->sampler->sampleAll();
        $all = iterator_to_array($samples, true);

        if ($source->c >= 0.12) {
            $filtered = array_filter(
                $all,
                static fn (string $ref): bool => !str_starts_with($ref, 'mono.'),
                ARRAY_FILTER_USE_KEY,
            );

            return $filtered !== [] ? $filtered : $all;
        }

        if ($preferredMonoTone === null || $source->c >= 0.05) {
            return $all;
        }

        $prefix = 'mono.' . $preferredMonoTone . '.';
        $filtered = array_filter(
            $all,
            static fn (string $ref): bool => str_starts_with($ref, $prefix),
            ARRAY_FILTER_USE_KEY,
        );

        return $filtered !== [] ? $filtered : $all;
    }

    private function extractLevel(string $ref): int
    {
        $base = str_contains($ref, '@') ? explode('@', $ref, 2)[0] : $ref;
        if (preg_match('/\.(\d+)$/', $base, $matches) !== 1) {
            return PHP_INT_MAX;
        }

        return (int) $matches[1];
    }

    private function nearestAlphaPercent(float $alpha): int
    {
        $target = (int) round($alpha * 100);
        $allowed = PaletteCatalog::alphaPercent();
        $nearest = $allowed[0];
        $bestDelta = abs($target - $nearest);

        foreach ($allowed as $percent) {
            $delta = abs($target - $percent);
            if ($delta < $bestDelta) {
                $bestDelta = $delta;
                $nearest = $percent;
            }
        }

        return $nearest;
    }
}
