<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Studio;

use Symfinity\UiDesigner\Authoring\StructuralDraft;
use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfinity\UiDesigner\Model\DesignerPairSession;
use Symfinity\UiDesigner\Preview\DesignerPreviewPort;
use Symfinity\UiDesigner\Session\DesignerDraftSession;

final class StudioPreviewAugmenter
{
    public function __construct(
        private readonly DesignerPreviewPort $previewPort,
        private readonly DesignerDraftSession $draftSession,
    ) {
    }

    public function previewCss(?DesignerDraft $draft, ?StructuralDraft $structural = null): ?string
    {
        if ($draft === null) {
            return null;
        }

        try {
            $structural ??= $this->draftSession->structural();
            $variantKey = $this->activeVariantKey();

            return $this->previewPort->preview($draft, $structural, $variantKey)->css;
        } catch (\Throwable) {
            return null;
        }
    }

    public function activeMode(): string
    {
        $pair = $this->draftSession->pair();

        return $pair !== null && $pair->activeMode === DesignerPairSession::MODE_DARK ? 'dark' : 'light';
    }

    /**
     * @return array<string, array{ref: string, color: string}>
     */
    public function resolvedRoleColors(?DesignerDraft $draft): array
    {
        if ($draft === null) {
            return [];
        }

        try {
            return $this->previewPort->resolvedRoleColors($draft, $this->activeVariantKey());
        } catch (\Throwable) {
            return [];
        }
    }

    private function activeVariantKey(): string
    {
        $pair = $this->draftSession->pair();
        if ($pair !== null && $pair->activeMode === DesignerPairSession::MODE_DARK) {
            return 'dark';
        }

        return 'light';
    }
}
