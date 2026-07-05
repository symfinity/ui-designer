<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Preview;

use Symfinity\UiDesigner\Model\DesignerDraft;

final readonly class PreviewSession
{
    public const POLARITY_LIGHT = 'light';
    public const POLARITY_DARK = 'dark';

    /**
     * @param array<string, mixed>|null $extensions
     */
    public function __construct(
        public string $token,
        public ?DesignerDraft $draftLight,
        public ?DesignerDraft $draftDark,
        public string $activePolarity,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $expiresAt,
        public ?string $packPath = null,
        public ?array $extensions = null,
    ) {
    }

    public function isExpired(\DateTimeImmutable $now = new \DateTimeImmutable()): bool
    {
        return $now >= $this->expiresAt;
    }

    public function activeDraft(?string $polarityOverride = null): ?DesignerDraft
    {
        $polarity = $polarityOverride ?? $this->activePolarity;
        if ($polarity === self::POLARITY_DARK && $this->draftDark !== null) {
            return $this->draftDark;
        }

        return $this->draftLight;
    }

    public function withDraft(DesignerDraft $draft, ?string $polarity = null): self
    {
        $mode = $polarity ?? $this->activePolarity;
        if ($mode === self::POLARITY_DARK) {
            return new self(
                token: $this->token,
                draftLight: $this->draftLight,
                draftDark: $draft,
                activePolarity: $mode,
                createdAt: $this->createdAt,
                expiresAt: $this->expiresAt,
                packPath: $this->packPath,
                extensions: $this->extensions,
            );
        }

        return new self(
            token: $this->token,
            draftLight: $draft,
            draftDark: $this->draftDark,
            activePolarity: $mode,
            createdAt: $this->createdAt,
            expiresAt: $this->expiresAt,
            packPath: $this->packPath,
            extensions: $this->extensions,
        );
    }

    public function withExtendedExpiry(\DateTimeImmutable $expiresAt): self
    {
        return new self(
            token: $this->token,
            draftLight: $this->draftLight,
            draftDark: $this->draftDark,
            activePolarity: $this->activePolarity,
            createdAt: $this->createdAt,
            expiresAt: $expiresAt,
            packPath: $this->packPath,
            extensions: $this->extensions,
        );
    }
}
