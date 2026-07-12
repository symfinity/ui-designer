<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Authoring;

use Symfinity\UiKernel\Palette\OklchTuple;
use Symfinity\UiKernel\Token\ThemePaletteRecipe;

final class NearestRefMapper
{
    public const MAX_DELTA_E = 1.0;
    public const CONFIDENCE_MAPPED = 0.75;
    public const CONFIDENCE_LOW = 0.5;

    public const STATUS_MAPPED = 'mapped';
    public const STATUS_LOW_CONFIDENCE = 'low_confidence';
    public const STATUS_UNMAPPED = 'unmapped';

    public function __construct(
        private readonly NearestPaletteRefResolver $resolver = new NearestPaletteRefResolver(),
        private readonly float $maxDeltaE = self::MAX_DELTA_E,
        private readonly float $mappedThreshold = self::CONFIDENCE_MAPPED,
        private readonly float $lowConfidenceThreshold = self::CONFIDENCE_LOW,
    ) {
    }

    /**
     * @return array{ref: ?string, confidence: float, deltaE: float, status: string}
     */
    public function map(OklchTuple $literal, ?string $preferredMonoTone = null, ?ThemePaletteRecipe $recipe = null): array
    {
        try {
            $resolved = $this->resolver->resolve($literal, $preferredMonoTone, $recipe);
        } catch (\Throwable) {
            return [
                'ref' => null,
                'confidence' => 0.0,
                'deltaE' => INF,
                'status' => self::STATUS_UNMAPPED,
            ];
        }

        $deltaE = $resolved['distance'];
        $confidence = 1.0 - min(1.0, $deltaE / $this->maxDeltaE);

        if ($confidence >= $this->mappedThreshold) {
            $status = self::STATUS_MAPPED;
        } elseif ($confidence >= $this->lowConfidenceThreshold) {
            $status = self::STATUS_LOW_CONFIDENCE;
        } else {
            $status = self::STATUS_UNMAPPED;
        }

        return [
            'ref' => $resolved['ref'],
            'confidence' => $confidence,
            'deltaE' => $deltaE,
            'status' => $status,
        ];
    }
}
