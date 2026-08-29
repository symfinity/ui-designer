<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Authoring;

use Symfinity\UiDesigner\Util\PayloadUtil;

final readonly class StructuralDraft
{
    public const RADIUS_STEPS = ['xs', 'sm', 'md', 'lg', 'full'];

    public const SHADOW_STEPS = ['sm', 'md', 'lg'];

    public function __construct(
        public ?string $radiusStep = null,
        public ?string $shadowStep = null,
    ) {
    }

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            radiusStep: PayloadUtil::optionalString($payload['radius_step'] ?? null),
            shadowStep: PayloadUtil::optionalString($payload['shadow_step'] ?? null),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toPayload(): array
    {
        $payload = [];
        if ($this->radiusStep !== null) {
            $payload['radius_step'] = $this->radiusStep;
        }
        if ($this->shadowStep !== null) {
            $payload['shadow_step'] = $this->shadowStep;
        }

        return $payload;
    }

    public function withRadiusStep(?string $step): self
    {
        return new self($step, $this->shadowStep);
    }

    public function withShadowStep(?string $step): self
    {
        return new self($this->radiusStep, $step);
    }

    public function assertValid(): void
    {
        if ($this->radiusStep !== null && !in_array($this->radiusStep, self::RADIUS_STEPS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown radius_step "%s"; expected one of: %s.',
                $this->radiusStep,
                implode(', ', self::RADIUS_STEPS),
            ));
        }

        if ($this->shadowStep !== null && !in_array($this->shadowStep, self::SHADOW_STEPS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown shadow_step "%s"; expected one of: %s.',
                $this->shadowStep,
                implode(', ', self::SHADOW_STEPS),
            ));
        }
    }
}
