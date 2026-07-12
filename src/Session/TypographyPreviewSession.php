<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Session;

use Symfinity\UiDesigner\Typography\TypographySelection;
use Symfinity\UiDesigner\Util\PayloadUtil;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class TypographyPreviewSession
{
    public const SESSION_KEY = 'ui_designer.typography_preview';

    private ?TypographySelection $fallback = null;

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function current(): ?TypographySelection
    {
        $session = $this->session();
        $payload = $session !== null ? $session->get(self::SESSION_KEY) : $this->fallbackPayload();

        if (!is_array($payload)) {
            return null;
        }

        $selection = TypographySelection::fromPayload(PayloadUtil::stringKeyMap($payload));

        return $selection->isEmpty() ? null : $selection;
    }

    public function store(TypographySelection $selection): void
    {
        $payload = $selection->toPayload();
        $session = $this->session();
        if ($session === null) {
            $this->fallback = $selection;

            return;
        }

        $session->set(self::SESSION_KEY, $payload);
    }

    public function clear(): void
    {
        $session = $this->session();
        if ($session === null) {
            $this->fallback = null;

            return;
        }

        $session->remove(self::SESSION_KEY);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fallbackPayload(): ?array
    {
        if ($this->fallback === null) {
            return null;
        }

        return $this->fallback->toPayload();
    }

    private function session(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null || !$request->hasSession()) {
            return null;
        }

        return $request->getSession();
    }
}
