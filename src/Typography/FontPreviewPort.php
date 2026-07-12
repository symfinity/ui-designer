<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Typography;

interface FontPreviewPort
{
    public function isAvailable(): bool;

    public function searchAvailable(): bool;

    /**
     * @return list<array{id: string, label: string, source: string}>
     */
    public function listPairings(): array;

    /**
     * @return list<array{family: string, category: string}>
     */
    public function search(string $query, int $limit = 20): array;

    /**
     * @return list<string>
     */
    public function popularFamilies(): array;

    public function selectionFromPairing(string $pairingId): TypographySelection;

    public function selectionFromActiveConfig(): ?TypographySelection;

    public function renderPreviewHtml(TypographySelection $selection): string;
}
