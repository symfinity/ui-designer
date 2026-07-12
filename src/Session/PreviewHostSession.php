<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Session;

use Symfinity\UiDesigner\Util\PayloadUtil;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Persists active preview token across navigation when query param is absent.
 */
final class PreviewHostSession
{
    public const SESSION_KEY = 'ui_designer_preview';

    /** @var array{token: string, polarity: ?string}|null */
    private ?array $fallback = null;

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function store(string $token, ?string $polarity = null): void
    {
        $payload = ['token' => $token, 'polarity' => $polarity];
        $session = $this->session();
        if ($session === null) {
            $this->fallback = $payload;

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
     * @return array{token: string, polarity: ?string}|null
     */
    public function binding(): ?array
    {
        $session = $this->session();
        if ($session === null) {
            return $this->fallback;
        }

        $payload = $session->get(self::SESSION_KEY);
        if (!is_array($payload)) {
            return null;
        }

        $map = PayloadUtil::stringKeyMap($payload);
        $token = $map['token'] ?? '';
        if (!is_string($token) || $token === '') {
            return null;
        }

        $polarity = $map['polarity'] ?? null;

        return [
            'token' => $token,
            'polarity' => is_string($polarity) && $polarity !== '' ? $polarity : null,
        ];
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
