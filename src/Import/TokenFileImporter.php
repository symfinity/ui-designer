<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Import;

use Symfinity\UiDesigner\Model\AuthorGraphNode;
use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfony\Component\Yaml\Yaml;

final class TokenFileImporter implements DesignTokenImportPort
{
    public function import(ImportRequest $request): ImportResult
    {
        if (!in_array($request->type, ['w3c', 'tokens-studio', 'figma', 'dtcg-yaml'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported token file type "%s".', $request->type));
        }

        if ($request->file === null || !is_file($request->file)) {
            throw new \InvalidArgumentException('Token file import requires --file path.');
        }

        $contents = (string) file_get_contents($request->file);
        $ext = pathinfo($request->file, PATHINFO_EXTENSION);
        /** @var array<string, mixed> $parsed */
        $parsed = $ext === 'json'
            ? json_decode($contents, true, 512, JSON_THROW_ON_ERROR)
            : Yaml::parse($contents);

        $payload = $this->normalizeToDtcg($parsed, $request->type);
        $name = $request->name ?? pathinfo($request->file, PATHINFO_FILENAME);

        $draft = new DesignerDraft(
            id: $name,
            lineage: 'imported-' . preg_replace('/[^a-z0-9-]+/i', '-', $name),
            graph: [new AuthorGraphNode($name, AuthorGraphNode::SOURCE_INLINE_DTCG, $payload)],
            variants: ['light' => $name],
            variantPayloads: ['light' => $payload],
        );

        return new ImportResult($draft);
    }

    /**
     * @param array<string, mixed> $parsed
     *
     * @return array<string, mixed>
     */
    private function normalizeToDtcg(array $parsed, string $type): array
    {
        if ($type === 'dtcg-yaml') {
            return $parsed;
        }

        if (isset($parsed['color']) && is_array($parsed['color'])) {
            return $parsed;
        }

        $color = [];
        foreach ($parsed as $key => $value) {
            if (!is_string($key) || !is_array($value)) {
                continue;
            }
            if (isset($value['$value'])) {
                $color[$key] = $value;

                continue;
            }
            if (isset($value['value']) && is_string($value['value'])) {
                $color[$key] = ['$type' => 'color', '$value' => $value['value']];
            }
        }

        return ['color' => $color !== [] ? $color : $parsed];
    }
}
