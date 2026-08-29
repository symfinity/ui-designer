<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Preview;

use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfinity\UiDesigner\Model\DesignerPairSession;

final class PreviewSessionStore
{
    private const MAX_SERIALIZED_BYTES = 262_144;

    /** @var array<string, PreviewSession> */
    private array $sessions = [];

    public function __construct(
        private readonly int $ttlSeconds = 14_400,
    ) {
    }

    /**
     * @param array<string, mixed>|null $extensions
     */
    public function mint(
        DesignerDraft $draft,
        ?DesignerDraft $darkDraft = null,
        string $activePolarity = PreviewSession::POLARITY_LIGHT,
        ?string $packPath = null,
        ?array $extensions = null,
    ): PreviewSession {
        $this->assertDraftSize($draft);
        if ($darkDraft !== null) {
            $this->assertDraftSize($darkDraft);
        }

        $token = bin2hex(random_bytes(32));
        $now = new \DateTimeImmutable();
        $session = new PreviewSession(
            token: $token,
            draftLight: $draft,
            draftDark: $darkDraft,
            activePolarity: $activePolarity,
            createdAt: $now,
            expiresAt: $now->modify(sprintf('+%d seconds', $this->ttlSeconds)),
            packPath: $packPath,
            extensions: $extensions,
        );
        $this->sessions[$token] = $session;

        return $session;
    }

    public function mintFromPair(DesignerPairSession $pair): PreviewSession
    {
        return $this->mint(
            draft: $pair->lightDraft,
            darkDraft: $pair->darkDraft,
            activePolarity: $pair->activeMode === DesignerPairSession::MODE_DARK
                ? PreviewSession::POLARITY_DARK
                : PreviewSession::POLARITY_LIGHT,
        );
    }

    public function get(string $token): ?PreviewSession
    {
        return $this->sessions[$token] ?? null;
    }

    public function revoke(string $token): void
    {
        unset($this->sessions[$token]);
    }

    public function purgeExpired(): void
    {
        $now = new \DateTimeImmutable();
        foreach ($this->sessions as $token => $session) {
            if ($session->isExpired($now)) {
                unset($this->sessions[$token]);
            }
        }
    }

    private function assertDraftSize(DesignerDraft $draft): void
    {
        $serialized = serialize($draft->toPayload());
        if (strlen($serialized) > self::MAX_SERIALIZED_BYTES) {
            throw new \InvalidArgumentException('Draft exceeds maximum preview session size.');
        }
    }
}
