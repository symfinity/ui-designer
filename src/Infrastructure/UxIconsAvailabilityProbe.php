<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Infrastructure;

/**
 * Detects whether symfony/ux-icons is present in the consumer app (optional suggest).
 */
final class UxIconsAvailabilityProbe
{
    public function isInstalled(): bool
    {
        return class_exists(\Symfony\UX\Icons\IconRenderer::class, false)
            || interface_exists(\Symfony\UX\Icons\IconRendererInterface::class, false)
            || class_exists(\Symfony\UX\Icons\Twig\UXIconRuntime::class, false);
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function iconSets(): array
    {
        if (!$this->isInstalled()) {
            return [];
        }

        return [
            ['id' => 'lucide', 'label' => 'Lucide'],
            ['id' => 'phosphor', 'label' => 'Phosphor Icons'],
            ['id' => 'tabler', 'label' => 'Tabler Icons'],
            ['id' => 'heroicons', 'label' => 'Heroicons'],
            ['id' => 'mdi', 'label' => 'Material Design Icons'],
        ];
    }
}
