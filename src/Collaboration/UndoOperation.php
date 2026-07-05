<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Collaboration;

final readonly class UndoOperation
{
    public function __construct(
        public string $role,
        public string $mode,
        public string $previousRef,
        public string $nextRef,
        public int $timestamp,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'role' => $this->role,
            'mode' => $this->mode,
            'previous_ref' => $this->previousRef,
            'next_ref' => $this->nextRef,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            role: (string) ($payload['role'] ?? ''),
            mode: (string) ($payload['mode'] ?? 'single'),
            previousRef: (string) ($payload['previous_ref'] ?? ''),
            nextRef: (string) ($payload['next_ref'] ?? ''),
            timestamp: (int) ($payload['timestamp'] ?? time()),
        );
    }
}
