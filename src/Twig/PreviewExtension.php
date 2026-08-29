<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Twig;

use Symfinity\UiDesigner\EventSubscriber\PreviewHostSubscriber;
use Symfinity\UiDesigner\Session\DesignerDraftSession;
use Symfinity\UiDesigner\Session\PreviewHostSession;
use Symfinity\UiDesigner\Session\TypographyPreviewSession;
use Symfinity\UiDesigner\Studio\StudioPreviewAugmenter;
use Symfinity\UiDesigner\Typography\FontPreviewPort;
use Symfinity\UiKernel\Preview\PreviewHostContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PreviewExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly PreviewHostSession $previewHostSession,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TypographyPreviewSession $typographyPreviewSession,
        private readonly FontPreviewPort $fontPreview,
        private readonly StudioPreviewAugmenter $previewAugmenter,
        private readonly DesignerDraftSession $draftSession,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ui_designer_preview_url', $this->previewUrl(...)),
            new TwigFunction('ui_designer_preview_context', $this->previewContext(...)),
            new TwigFunction('ui_designer_drawer_session', $this->drawerSession(...)),
            new TwigFunction('ui_designer_typography_preview_html', $this->typographyPreviewHtml(...)),
            new TwigFunction('ui_designer_drawer_preview_css', $this->drawerPreviewCss(...)),
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function previewUrl(string $route, array $parameters = []): string
    {
        $this->appendPreviewParams($parameters);

        return $this->urlGenerator->generate($route, $parameters);
    }

    public function previewContext(): ?PreviewHostContext
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }

        $context = $request->attributes->get(PreviewHostSubscriber::ATTR_CONTEXT);

        return $context instanceof PreviewHostContext ? $context : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function drawerSession(): ?array
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }

        $drawer = $request->attributes->get('_ui_designer_drawer');

        if (!is_array($drawer)) {
            return null;
        }

        /** @var array<string, mixed> $drawer */

        return $drawer;
    }

    public function typographyPreviewHtml(): string
    {
        $selection = $this->typographyPreviewSession->current()
            ?? $this->fontPreview->selectionFromActiveConfig();

        if ($selection === null || $selection->isEmpty()) {
            return '';
        }

        return $this->fontPreview->renderPreviewHtml($selection);
    }

    public function drawerPreviewCss(): string
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            return '';
        }

        return $this->previewAugmenter->previewCss($draft, $this->draftSession->structural()) ?? '';
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function appendPreviewParams(array &$parameters): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $token = $request?->attributes->get(PreviewHostSubscriber::ATTR_TOKEN);
        $polarity = $request?->attributes->get(PreviewHostSubscriber::ATTR_POLARITY);

        if (!is_string($token) || $token === '') {
            $binding = $this->previewHostSession->binding();
            $token = $binding['token'] ?? '';
            $polarity = $binding['polarity'] ?? null;
        }

        if ($token !== '') {
            $parameters['preview'] = $token;
            if (is_string($polarity) && $polarity !== '') {
                $parameters['polarity'] = $polarity;
            }
        }
    }
}
