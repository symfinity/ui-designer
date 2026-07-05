<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Preview;

use Symfinity\UiKernel\Preview\PreviewHostContext;
use Symfinity\UiKernel\Preview\SessionThemeInjectionPort;

final class DesignerPreviewTokenResolver implements SessionThemeInjectionPort
{
    public function __construct(
        private readonly PreviewSessionStore $store,
        private readonly DesignerPreviewPort $previewPort,
    ) {
    }

    public function resolve(string $token, ?string $polarity = null): ?PreviewHostContext
    {
        $this->store->purgeExpired();
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

        try {
            $artifact = $this->previewPort->preview($draft);
        } catch (\Throwable) {
            return null;
        }

        return new PreviewHostContext(
            token: $token,
            resolvedThemeId: $draft->id,
            css: $artifact->css,
            isPreviewActive: true,
            activePolarity: $polarity ?? $session->activePolarity,
            packPath: $session->packPath,
            expired: false,
        );
    }

    public function revoke(string $token): void
    {
        $this->store->revoke($token);
    }
}
