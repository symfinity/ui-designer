<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Collaboration;

use Symfinity\UiDesigner\Util\PayloadUtil;

final readonly class DraftSnapshot
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $draftId,
        public \DateTimeImmutable $createdAt,
        public array $payload,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'draft_id' => $this->draftId,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'payload' => $this->payload,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $createdAt = isset($data['created_at']) && is_string($data['created_at'])
            ? new \DateTimeImmutable($data['created_at'])
            : new \DateTimeImmutable();

        /** @var array<string, mixed> $payload */
        $payload = PayloadUtil::stringKeyMap($data['payload'] ?? null);

        return new self(
            id: PayloadUtil::string($data['id'] ?? null),
            label: PayloadUtil::string($data['label'] ?? null),
            draftId: PayloadUtil::string($data['draft_id'] ?? null),
            createdAt: $createdAt,
            payload: $payload,
        );
    }
}
