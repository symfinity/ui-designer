<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Model;

final readonly class DesignerPairSession
{
    public const MODE_LIGHT = 'light';
    public const MODE_DARK = 'dark';

    public function __construct(
        public string $lightId,
        public string $darkId,
        public string $activeMode,
        public DesignerDraft $lightDraft,
        public DesignerDraft $darkDraft,
    ) {
    }

    public function activeDraft(): DesignerDraft
    {
        return $this->activeMode === self::MODE_DARK ? $this->darkDraft : $this->lightDraft;
    }

    public function withActiveMode(string $mode): self
    {
        if (!in_array($mode, [self::MODE_LIGHT, self::MODE_DARK], true)) {
            throw new \InvalidArgumentException(sprintf('Unknown mode "%s".', $mode));
        }

        return new self($this->lightId, $this->darkId, $mode, $this->lightDraft, $this->darkDraft);
    }

    public function withActiveDraft(DesignerDraft $draft): self
    {
        if ($this->activeMode === self::MODE_DARK) {
            return new self($this->lightId, $this->darkId, $this->activeMode, $this->lightDraft, $draft);
        }

        return new self($this->lightId, $this->darkId, $this->activeMode, $draft, $this->darkDraft);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'light_id' => $this->lightId,
            'dark_id' => $this->darkId,
            'active_mode' => $this->activeMode,
            'light_draft' => $this->lightDraft->toPayload(),
            'dark_draft' => $this->darkDraft->toPayload(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        /** @var array<string, mixed> $lightPayload */
        $lightPayload = isset($payload['light_draft']) && is_array($payload['light_draft']) ? $payload['light_draft'] : [];
        /** @var array<string, mixed> $darkPayload */
        $darkPayload = isset($payload['dark_draft']) && is_array($payload['dark_draft']) ? $payload['dark_draft'] : [];

        return new self(
            lightId: (string) ($payload['light_id'] ?? ''),
            darkId: (string) ($payload['dark_id'] ?? ''),
            activeMode: (string) ($payload['active_mode'] ?? self::MODE_LIGHT),
            lightDraft: DesignerDraft::fromPayload($lightPayload),
            darkDraft: DesignerDraft::fromPayload($darkPayload),
        );
    }
}
