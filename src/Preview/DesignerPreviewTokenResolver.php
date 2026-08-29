<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Preview;

use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfinity\UiDesigner\Model\DesignerPairSession;
use Symfinity\UiDesigner\Session\DesignerDraftSession;
use Symfinity\UiDesigner\Session\PreviewHostSession;
use Symfinity\UiKernel\Preview\PreviewHostContext;
use Symfinity\UiKernel\Preview\SessionThemeInjectionPort;

final class DesignerPreviewTokenResolver implements SessionThemeInjectionPort
{
    public function __construct(
        private readonly PreviewSessionStore $store,
        private readonly DesignerPreviewPort $previewPort,
        private readonly PreviewHostSession $previewHostSession,
        private readonly DesignerDraftSession $draftSession,
    ) {
    }

    public function resolve(string $token, ?string $polarity = null): ?PreviewHostContext
    {
        $this->store->purgeExpired();

        $binding = $this->previewHostSession->binding();
        if ($binding !== null && hash_equals($binding['token'], $token)) {
            $context = $this->resolveFromAuthoringSession(
                token: $token,
                polarity: $polarity ?? $binding['polarity'],
            );
            if ($context !== null) {
                return $context;
            }
        }

        $session = $this->store->get($token);
        if ($session === null) {
            return null;
        }

        if ($session->isExpired()) {
            $this->store->revoke($token);

            return new PreviewHostContext(
                token: $token,
                resolvedThemeId: 'default',
                css: '',
                isPreviewActive: false,
                activePolarity: $polarity,
                packPath: $session->packPath,
                expired: true,
            );
        }

        $draft = $session->activeDraft($polarity);
        if ($draft === null) {
            return null;
        }

        return $this->buildContext($token, $draft, $polarity ?? $session->activePolarity, $session->packPath);
    }

    public function revoke(string $token): void
    {
        $this->store->revoke($token);
    }

    private function resolveFromAuthoringSession(string $token, ?string $polarity): ?PreviewHostContext
    {
        $draft = $this->activeAuthoringDraft($polarity);
        if ($draft === null) {
            return null;
        }

        $resolvedPolarity = $polarity ?? $this->draftSession->pair()?->activeMode ?? PreviewSession::POLARITY_LIGHT;

        return $this->buildContext($token, $draft, $resolvedPolarity, packPath: null);
    }

    private function activeAuthoringDraft(?string $polarity): ?DesignerDraft
    {
        $pair = $this->draftSession->pair();
        if ($pair !== null) {
            if ($polarity === PreviewSession::POLARITY_DARK || $polarity === DesignerPairSession::MODE_DARK) {
                return $pair->darkDraft;
            }

            if ($polarity === PreviewSession::POLARITY_LIGHT || $polarity === DesignerPairSession::MODE_LIGHT) {
                return $pair->lightDraft;
            }

            return $pair->activeDraft();
        }

        return $this->draftSession->current();
    }

    private function buildContext(
        string $token,
        DesignerDraft $draft,
        ?string $polarity,
        ?string $packPath,
    ): ?PreviewHostContext {
        try {
            $variantKey = $this->resolveVariantKey($polarity);
            $artifact = $this->previewPort->preview($draft, $this->draftSession->structural(), $variantKey);
        } catch (\Throwable) {
            return null;
        }

        $resolvedPolarity = $polarity ?? $this->draftSession->pair()?->activeMode ?? PreviewSession::POLARITY_LIGHT;

        return new PreviewHostContext(
            token: $token,
            resolvedThemeId: $draft->id,
            css: $artifact->css,
            isPreviewActive: true,
            activePolarity: $resolvedPolarity,
            packPath: $packPath,
            expired: false,
        );
    }

    private function resolveVariantKey(?string $polarity): string
    {
        if ($polarity === PreviewSession::POLARITY_DARK || $polarity === DesignerPairSession::MODE_DARK) {
            return 'dark';
        }

        return 'light';
    }
}
