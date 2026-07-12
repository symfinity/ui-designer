<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\EventSubscriber;

use Symfinity\UiDesigner\Session\PreviewHostSession;
use Symfinity\UiDesigner\Session\StudioDrawerSession;
use Symfony\Component\HttpFoundation\Request;
use Symfinity\UiKernel\Preview\PreviewHostContext;
use Symfinity\UiKernel\Preview\SessionThemeInjectionPort;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Resolves preview token from query or session and exposes host context to Twig/controllers.
 */
final class PreviewHostSubscriber implements EventSubscriberInterface
{
    public const ATTR_CONTEXT = '_ui_designer_preview_context';
    public const ATTR_TOKEN = '_ui_designer_preview_token';
    public const ATTR_POLARITY = '_ui_designer_preview_polarity';
    public const ATTR_EXPIRED = '_ui_designer_preview_expired';

    public function __construct(
        private readonly PreviewHostSession $previewHostSession,
        private readonly StudioDrawerSession $drawerSession,
        private readonly ?SessionThemeInjectionPort $previewInjection = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Designer routes manage their own preview chrome — avoid double injection.
        if (str_starts_with($path, '/ui-designer')) {
            $request->attributes->set('_ui_designer_drawer', $this->drawerSession->current());

            return;
        }

        $queryToken = $request->query->getString('preview');
        $queryPolarity = $request->query->getString('polarity');

        if ($queryToken !== '') {
            $this->previewHostSession->store(
                $queryToken,
                $queryPolarity !== '' ? $queryPolarity : null,
            );
        }

        $binding = $this->previewHostSession->binding();
        $token = $queryToken !== '' ? $queryToken : ($binding['token'] ?? '');
        $polarity = $queryPolarity !== '' ? $queryPolarity : ($binding['polarity'] ?? null);

        $context = null;
        $expired = false;

        if ($token !== '' && $this->previewInjection !== null) {
            $context = $this->previewInjection->resolve($token, $polarity);
            $expired = $context !== null && $context->expired;
        }

        $request->attributes->set(self::ATTR_CONTEXT, $context);
        $request->attributes->set(self::ATTR_TOKEN, $token !== '' ? $token : null);
        $request->attributes->set(self::ATTR_POLARITY, $polarity);
        $request->attributes->set(self::ATTR_EXPIRED, $expired);
        $drawer = $this->drawerSession->current();
        if ($drawer !== null && $drawer['open']) {
            $this->drawerSession->touchReturnUrl($path);
            $drawer = $this->drawerSession->current();
        }
        $request->attributes->set('_ui_designer_drawer', $drawer);
    }

    public static function contextFromRequest(Request $request): ?PreviewHostContext
    {
        $context = $request->attributes->get(self::ATTR_CONTEXT);

        return $context instanceof PreviewHostContext ? $context : null;
    }
}
