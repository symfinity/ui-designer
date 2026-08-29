<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Model;

use Symfinity\UiDesigner\Util\PayloadUtil;

final class AuthorGraphNode
{
    public const SOURCE_INLINE_DTCG = 'inline_dtcg';
    public const SOURCE_FILE = 'file';
    public const SOURCE_REGISTRY_REF = 'registry_ref';

    /**
     * @param array<string, mixed>|string|null $payload DTCG fragment, filesystem path, or registry ref
     */
    public function __construct(
        public readonly string $nodeId,
        public readonly string $source,
        public readonly array|string|null $payload = null,
        public readonly ?string $extends = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = [
            'node_id' => $this->nodeId,
            'source' => $this->source,
            'payload' => $this->payload,
        ];

        if ($this->extends !== null) {
            $payload['extends'] = $this->extends;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $extends = $payload['extends'] ?? null;
        $nodePayload = $payload['payload'] ?? null;
        if (is_array($nodePayload)) {
            $nodePayload = PayloadUtil::stringKeyMap($nodePayload);
        } elseif (!is_string($nodePayload)) {
            $nodePayload = null;
        }

        return new self(
            nodeId: PayloadUtil::string($payload['node_id'] ?? null),
            source: PayloadUtil::string($payload['source'] ?? null, self::SOURCE_INLINE_DTCG),
            payload: $nodePayload,
            extends: is_string($extends) && $extends !== '' ? $extends : null,
        );
    }
}
