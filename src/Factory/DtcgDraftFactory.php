<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Factory;

use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfony\Component\Yaml\Yaml;

final class DtcgDraftFactory
{
    public function __construct(
        private readonly string $exportRoot,
        private readonly bool $strictExport = false,
    ) {
    }

    public function exportRoot(): string
    {
        return $this->exportRoot;
    }

    public function isStrictExport(): bool
    {
        return $this->strictExport;
    }

    /**
     * Build theme.meta.yaml contents for the draft lineage.
     *
     * @return array<string, mixed>
     */
    public function buildThemeMeta(DesignerDraft $draft): array
    {
        $variantRefs = [];
        foreach ($draft->variants as $variantId => $ref) {
            $variantRefs[] = [
                'id' => $variantId,
                'file' => $ref !== '' ? $ref : $variantId . '.dtcg.yaml',
            ];
        }

        if ($variantRefs === [] && $draft->variantPayloads !== []) {
            foreach (array_keys($draft->variantPayloads) as $variantId) {
                $variantRefs[] = [
                    'id' => $variantId,
                    'file' => $variantId . '.dtcg.yaml',
                ];
            }
        }

        $meta = [
            'schema_version' => '1.0',
            'id' => $draft->id,
            'lineage' => $draft->lineage,
            'design_system_id' => $draft->designSystemId,
            'variants' => $variantRefs,
        ];

        if ($this->strictExport) {
            $meta['export'] = ['strict' => true];
        }

        return $meta;
    }

    /**
     * @return array<string, string> variant id => YAML body
     */
    public function buildVariantFiles(DesignerDraft $draft): array
    {
        $files = [];

        foreach ($draft->variantPayloads as $variantId => $payload) {
            $files[(string) $variantId] = Yaml::dump($payload, 6, 2);
        }

        return $files;
    }

    /**
     * @return array{'theme.meta.yaml': string, variants: array<string, string>}
     */
    public function buildExportPack(DesignerDraft $draft): array
    {
        return [
            'theme.meta.yaml' => Yaml::dump($this->buildThemeMeta($draft), 4, 2),
            'variants' => $this->buildVariantFiles($draft),
        ];
    }

    public function lineageDirectory(DesignerDraft $draft): string
    {
        return rtrim($this->exportRoot, '/') . '/' . $draft->lineage;
    }
}
