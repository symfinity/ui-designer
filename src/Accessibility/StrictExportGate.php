<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Accessibility;

use Symfinity\UiDesigner\Model\DesignerDraft;

final class StrictExportGate
{
    public function __construct(
        private readonly ContrastPanelPresenter $contrastPanel,
        private readonly bool $strictExportEnabled,
    ) {
    }

    public function assertExportAllowed(DesignerDraft $draft, bool $skipAccessibility = false, bool $cliStrict = false): void
    {
        if ($skipAccessibility || (!$this->strictExportEnabled && !$cliStrict)) {
            return;
        }

        $panel = $this->contrastPanel->present($draft);
        if (!$panel['available']) {
            return;
        }

        $failureCount = 0;
        foreach ($panel['rows'] as $row) {
            if ($row['pass'] === false) {
                ++$failureCount;
            }
        }

        if ($failureCount === 0) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'Strict accessibility export blocked: %d contrast warning(s). Fix refs or use --skip-accessibility.',
            $failureCount,
        ));
    }

    public function isStrictEnabled(): bool
    {
        return $this->strictExportEnabled;
    }
}
