<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Model;

use Symfinity\UiDesigner\Util\PayloadUtil;

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
        return new self(
            lightId: PayloadUtil::string($payload['light_id'] ?? null),
            darkId: PayloadUtil::string($payload['dark_id'] ?? null),
            activeMode: PayloadUtil::string($payload['active_mode'] ?? null, self::MODE_LIGHT),
            lightDraft: DesignerDraft::fromPayload(PayloadUtil::stringKeyMap($payload['light_draft'] ?? null)),
            darkDraft: DesignerDraft::fromPayload(PayloadUtil::stringKeyMap($payload['dark_draft'] ?? null)),
        );
    }
}
