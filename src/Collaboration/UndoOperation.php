<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Collaboration;

use Symfinity\UiDesigner\Util\PayloadUtil;

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
            role: PayloadUtil::string($payload['role'] ?? null),
            mode: PayloadUtil::string($payload['mode'] ?? null, 'single'),
            previousRef: PayloadUtil::string($payload['previous_ref'] ?? null),
            nextRef: PayloadUtil::string($payload['next_ref'] ?? null),
            timestamp: PayloadUtil::int($payload['timestamp'] ?? null, time()),
        );
    }
}
