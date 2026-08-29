<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Kernel;

use Symfinity\UiDesigner\Model\AuthorGraphNode;
use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfinity\UiDesigner\Model\DesignerPairSession;
use Symfinity\UiDesigner\Session\DesignerDraftSession;
use Symfinity\UiDesigner\Authoring\StructuralDraft;
use Symfinity\UiKernel\Dtcg\BuiltinDtcgThemeCatalog;
use Symfinity\UiKernel\Dtcg\BuiltinThemeVariant;
use Symfony\Component\Yaml\Yaml;

final class BuiltinDraftLoader
{
    /** @var array<string, string|null> */
    private const DARK_COMPANIONS = [
        'semantic' => 'semantic-dark',
        'default' => 'default-dark',
    ];

    public function __construct(
        private readonly BuiltinDtcgThemeCatalog $catalog,
        private readonly DesignerDraftSession $draftSession,
    ) {
    }

    public function loadVariant(string $themeId): DesignerDraft
    {
        $draft = $this->buildDraft($themeId);
        $this->draftSession->clearPair();
        $this->draftSession->store($draft, dirty: false);
        $this->draftSession->storeStructural(StructuralDraft::empty());

        return $draft;
    }

    public function loadVariantForStudio(string $themeId): DesignerDraft
    {
        $darkId = self::DARK_COMPANIONS[$themeId] ?? null;
        if ($darkId !== null) {
            try {
                $this->catalog->get($darkId);
            } catch (\InvalidArgumentException) {
                $darkId = null;
            }
        }

        if ($darkId !== null) {
            $lightDraft = $this->buildDraft($themeId);
            $darkDraft = $this->buildDraft($darkId);
            $pair = new DesignerPairSession(
                lightId: $themeId,
                darkId: $darkId,
                activeMode: DesignerPairSession::MODE_LIGHT,
                lightDraft: $lightDraft,
                darkDraft: $darkDraft,
            );
            $this->draftSession->storePair($pair, dirty: false);
            $this->draftSession->storeStructural(StructuralDraft::empty());

            return $lightDraft;
        }

        return $this->loadVariant($themeId);
    }

    /**
     * @return list<array{id: string, label: string, lineage: string}>
     */
    public function presetEntries(): array
    {
        $entries = [];
        foreach ($this->catalog->all() as $variant) {
            if ($variant->isDarkVariant()) {
                continue;
            }
            $entries[] = [
                'id' => $variant->id(),
                'label' => $variant->label(),
                'lineage' => $variant->lineage(),
            ];
        }

        return $entries;
    }

    private function buildDraft(string $themeId): DesignerDraft
    {
        $variant = $this->catalog->get($themeId);
        $payload = $this->loadLayerPayload($variant);

        $variantKey = $variant->isDarkVariant() ? 'dark' : 'light';

        return new DesignerDraft(
            id: $variant->id(),
            lineage: $variant->lineage(),
            designSystemId: $variant->designSystemId(),
            graph: [
                new AuthorGraphNode(
                    nodeId: $variant->id(),
                    source: AuthorGraphNode::SOURCE_FILE,
                    payload: $variant->layerPath(),
                ),
            ],
            variants: [$variantKey => $variant->id()],
            variantPayloads: [$variantKey => $payload],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLayerPayload(BuiltinThemeVariant $variant): array
    {
        /** @var array<string, mixed> $payload */
        $payload = Yaml::parseFile($variant->layerPath());
        if ($payload === []) {
            throw new \RuntimeException(sprintf('Empty DTCG layer at "%s".', $variant->layerPath()));
        }

        return $payload;
    }
}
