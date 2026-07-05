<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Accessibility;

use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfinity\UiDesigner\Preview\DesignerPreviewPort;

final class ContrastPanelPresenter
{
    /** @var list<array{0: string, 1: string}> */
    private const MINIMUM_PAIRS = [
        ['text', 'surface'],
        ['primary', 'surface'],
        ['danger', 'surface'],
    ];

    public function __construct(
        private readonly DesignerPreviewPort $previewPort,
        private readonly ColourContrastEvaluator $evaluator,
    ) {
    }

    /**
     * @return array{available: bool, notice: string|null, rows: list<array{foreground: string, background: string, level: string, ratio: float|null, pass: bool, message: string}>}
     */
    public function present(DesignerDraft $draft): array
    {
        try {
            $resolved = $this->previewPort->resolvedRoleColors($draft);
        } catch (\Throwable) {
            return [
                'available' => false,
                'notice' => 'Contrast panel unavailable — could not resolve draft colours.',
                'rows' => [],
            ];
        }

        $rows = [];
        foreach (self::MINIMUM_PAIRS as [$foreground, $background]) {
            $fg = $resolved[$foreground]['color'] ?? null;
            $bg = $resolved[$background]['color'] ?? null;
            if (!is_string($fg) || !is_string($bg)) {
                $rows[] = [
                    'foreground' => $foreground,
                    'background' => $background,
                    'level' => 'unknown',
                    'ratio' => null,
                    'pass' => true,
                    'message' => sprintf('%s on %s could not be evaluated.', $foreground, $background),
                ];

                continue;
            }

            $result = $this->evaluator->evaluatePair($fg, $bg);
            $rows[] = [
                'foreground' => $foreground,
                'background' => $background,
                'level' => $result['level'],
                'ratio' => $result['ratio'],
                'pass' => $result['pass'],
                'message' => $result['message'],
            ];
        }

        return [
            'available' => true,
            'notice' => null,
            'rows' => $rows,
        ];
    }
}
