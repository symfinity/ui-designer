<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Model;

use Symfinity\UiDesigner\Util\PayloadUtil;

final class DesignerDraft
{
    /**
     * @param list<AuthorGraphNode>          $graph
     * @param array<string, string>        $variants        variant id => ref or label (e.g. light, dark)
     * @param array<string, array<string, mixed>> $variantPayloads variant id => DTCG document
     */
    public function __construct(
        public readonly string $id,
        public readonly string $lineage,
        public readonly string $designSystemId = 'symfinity',
        public readonly array $graph = [],
        public readonly array $variants = [],
        public readonly array $variantPayloads = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'lineage' => $this->lineage,
            'design_system_id' => $this->designSystemId,
            'graph' => array_map(static fn (AuthorGraphNode $node): array => $node->toPayload(), $this->graph),
            'variants' => $this->variants,
            'variant_payloads' => $this->variantPayloads,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        /** @var list<AuthorGraphNode> $graph */
        $graph = [];
        if (isset($payload['graph']) && is_array($payload['graph'])) {
            foreach ($payload['graph'] as $nodePayload) {
                if (is_array($nodePayload)) {
                    $graph[] = AuthorGraphNode::fromPayload(PayloadUtil::stringKeyMap($nodePayload));
                }
            }
        }

        $variants = PayloadUtil::stringMap($payload['variants'] ?? null);
        $variantPayloads = PayloadUtil::nestedStringKeyMaps($payload['variant_payloads'] ?? null);

        return new self(
            id: PayloadUtil::string($payload['id'] ?? null),
            lineage: PayloadUtil::string($payload['lineage'] ?? null),
            designSystemId: PayloadUtil::string($payload['design_system_id'] ?? null, 'symfinity'),
            graph: $graph,
            variants: $variants,
            variantPayloads: $variantPayloads,
        );
    }
}
