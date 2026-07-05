<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Authoring;

final readonly class SelectedRole
{
    public function __construct(
        public string $activeRole,
        public int $updatedAt,
    ) {
    }

    /**
     * @return array{active_role: string, updated_at: int}
     */
    public function toPayload(): array
    {
        return [
            'active_role' => $this->activeRole,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            activeRole: (string) ($payload['active_role'] ?? 'primary'),
            updatedAt: (int) ($payload['updated_at'] ?? time()),
        );
    }
}
