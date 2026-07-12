<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Typography;

use Symfony\Component\Yaml\Yaml;

/**
 * Bundled Fonttrio pairing ids shipped with ui-designer (merged with font-manager catalog).
 */
final class FonttrioPairingCatalog
{
    /** @var list<array{id: string, label: string, source: string}>|null */
    private ?array $entries = null;

    public function __construct(
        private readonly string $catalogPath,
    ) {
    }

    /**
     * @return list<array{id: string, label: string, source: string}>
     */
    public function list(): array
    {
        return $this->loadEntries();
    }

    /**
     * @return list<array{id: string, label: string, source: string}>
     */
    private function loadEntries(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        if (!is_readable($this->catalogPath)) {
            $this->entries = [];

            return $this->entries;
        }

        /** @var mixed $parsed */
        $parsed = Yaml::parseFile($this->catalogPath);
        if (!is_array($parsed)) {
            $this->entries = [];

            return $this->entries;
        }

        /** @var mixed $pairings */
        $pairings = $parsed['pairings'] ?? [];
        if (!is_array($pairings)) {
            $this->entries = [];

            return $this->entries;
        }

        $entries = [];
        foreach ($pairings as $id => $config) {
            if (!is_string($id) || !is_array($config)) {
                continue;
            }
            if (!preg_match('/^[a-z0-9-]+$/', $id)) {
                continue;
            }

            $label = isset($config['label']) && is_string($config['label']) ? $config['label'] : $id;
            $source = isset($config['source']) && is_string($config['source']) ? $config['source'] : '@fonttrio/' . $id;

            $entries[] = [
                'id' => $id,
                'label' => $label,
                'source' => $source,
            ];
        }

        $this->entries = $entries;

        return $this->entries;
    }
}
