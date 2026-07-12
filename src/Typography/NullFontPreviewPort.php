<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Typography;

final class NullFontPreviewPort implements FontPreviewPort
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function searchAvailable(): bool
    {
        return false;
    }

    public function listPairings(): array
    {
        return [];
    }

    public function search(string $query, int $limit = 20): array
    {
        return [];
    }

    public function popularFamilies(): array
    {
        return [];
    }

    public function selectionFromPairing(string $pairingId): TypographySelection
    {
        throw new \RuntimeException('symfinity/font-manager is not installed.');
    }

    public function selectionFromActiveConfig(): ?TypographySelection
    {
        return null;
    }

    public function renderPreviewHtml(TypographySelection $selection): string
    {
        return '';
    }
}
