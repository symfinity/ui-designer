<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Session;

use Symfinity\UiDesigner\Util\PayloadUtil;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class StudioDrawerSession
{
    public const SESSION_KEY = 'ui_designer.drawer';

    public const TAB_COLOURS = 'colours';

    public const TAB_TYPOGRAPHY = 'typography';

    public const TAB_STRUCTURE = 'structure';

    /** @var array<string, mixed>|null */
    private ?array $fallback = null;

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function open(string $returnUrl, ?string $previewToken = null, string $activeTab = self::TAB_COLOURS): void
    {
        $this->write([
            'open' => true,
            'active_tab' => $activeTab,
            'return_url' => $returnUrl,
            'preview_token' => $previewToken,
        ]);
    }

    public function close(): void
    {
        $current = $this->current();
        if ($current === null) {
            return;
        }

        $this->write([
            ...$current,
            'open' => false,
        ]);
    }

    public function isOpen(): bool
    {
        $current = $this->current();

        return $current !== null && $current['open'] === true;
    }

    /**
     * @return array{open: bool, active_tab: string, return_url: string, preview_token: ?string}|null
     */
    public function current(): ?array
    {
        $session = $this->session();
        $payload = $session !== null ? $session->get(self::SESSION_KEY) : $this->fallback;
        if (!is_array($payload)) {
            return null;
        }

        $map = PayloadUtil::stringKeyMap($payload);

        return [
            'open' => (bool) $map['open'],
            'active_tab' => is_string($map['active_tab'] ?? null) ? $map['active_tab'] : self::TAB_COLOURS,
            'return_url' => is_string($map['return_url'] ?? null) ? $map['return_url'] : '/',
            'preview_token' => is_string($map['preview_token'] ?? null) && $map['preview_token'] !== ''
                ? $map['preview_token']
                : null,
        ];
    }

    public function setActiveTab(string $tab): void
    {
        $current = $this->current();
        if ($current === null) {
            return;
        }

        $this->write([
            ...$current,
            'active_tab' => $tab,
        ]);
    }

    public function touchReturnUrl(string $returnUrl): void
    {
        if (!str_starts_with($returnUrl, '/') || str_starts_with($returnUrl, '/ui-designer')) {
            return;
        }

        $current = $this->current();
        if ($current === null || !$current['open'] || $current['return_url'] === $returnUrl) {
            return;
        }

        $this->write([
            ...$current,
            'return_url' => $returnUrl,
        ]);
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
     * @param array<string, mixed> $payload
     */
    private function write(array $payload): void
    {
        $session = $this->session();
        if ($session === null) {
            $this->fallback = $payload;

            return;
        }

        $session->set(self::SESSION_KEY, $payload);
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
