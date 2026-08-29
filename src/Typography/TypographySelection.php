<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Typography;

use Symfinity\UiDesigner\Util\PayloadUtil;

final readonly class TypographySelection
{
    public function __construct(
        public ?string $headingFamily = null,
        public ?string $bodyFamily = null,
        public ?string $monoFamily = null,
        public ?string $pairingId = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->headingFamily === null
            && $this->bodyFamily === null
            && $this->monoFamily === null;
    }

    /**
     * @return array<string, string|null>
     */
    public function toPayload(): array
    {
        return [
            'heading_family' => $this->headingFamily,
            'body_family' => $this->bodyFamily,
            'mono_family' => $this->monoFamily,
            'pairing_id' => $this->pairingId,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $map = PayloadUtil::stringKeyMap($payload);

        return new self(
            headingFamily: self::optionalString($map['heading_family'] ?? null),
            bodyFamily: self::optionalString($map['body_family'] ?? null),
            monoFamily: self::optionalString($map['mono_family'] ?? null),
            pairingId: self::optionalString($map['pairing_id'] ?? null),
        );
    }

    public function withRoleFamily(string $role, string $family): self
    {
        return match ($role) {
            'heading' => new self($family, $this->bodyFamily, $this->monoFamily, null),
            'body' => new self($this->headingFamily, $family, $this->monoFamily, null),
            'mono' => new self($this->headingFamily, $this->bodyFamily, $family, null),
            default => $this,
        };
    }

    public function withPairing(
        string $pairingId,
        string $headingFamily,
        string $bodyFamily,
        string $monoFamily,
    ): self {
        return new self($headingFamily, $bodyFamily, $monoFamily, $pairingId);
    }

    private static function optionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
