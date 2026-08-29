<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Authoring;

use Symfinity\UiKernel\Token\SemanticColorMap;

/**
 * Maps semantic colour roles to DTCG dotted paths (without {@code $value}).
 */
final class DtcgRolePathMapper
{
    /** @var array<string, string> role => dotted DTCG path */
    private const ROLE_TO_PATH = [
        'primary' => 'color.primary',
        'secondary' => 'color.secondary',
        'accent' => 'color.accent',
        'neutral' => 'color.neutral',
        'surface' => 'color.surface.base',
        'surface_elevated' => 'color.surface.elevated',
        'text' => 'color.text.default',
        'text_muted' => 'color.text.muted',
        'border' => 'color.border.default',
        'danger' => 'color.danger',
        'success' => 'color.success',
        'warning' => 'color.warning',
        'info' => 'color.info',
        'focus' => 'color.focus',
        'overlay' => 'color.overlay',
        'skeleton_base' => 'color.skeleton.base',
        'skeleton_shine' => 'color.skeleton.shine',
    ];

    public function pathForRole(string $role): string
    {
        if (!isset(self::ROLE_TO_PATH[$role])) {
            throw new \InvalidArgumentException(sprintf('Unknown semantic role "%s".', $role));
        }

        return self::ROLE_TO_PATH[$role];
    }

    public function isKnownRole(string $role): bool
    {
        return isset(self::ROLE_TO_PATH[$role]) || isset(SemanticColorMap::ROLE_TO_CSS[$role]);
    }

    /**
     * @return list<string>
     */
    public function allRoles(): array
    {
        return array_keys(SemanticColorMap::ROLE_TO_CSS);
    }

    /**
     * Convert palette ref ({@code red.500}) to DTCG alias ({@code {color.red.500}}).
     */
    public function paletteRefToDtcgAlias(string $ref): string
    {
        $trimmed = trim($ref);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Palette ref must not be empty.');
        }

        if (str_starts_with($trimmed, '{')) {
            return $trimmed;
        }

        return sprintf('{color.%s}', $trimmed);
    }

    /**
     * Extract palette ref from DTCG alias or raw stored value.
     */
    public function dtcgAliasToPaletteRef(string $value): string
    {
        $trimmed = trim($value);
        if (preg_match('/^\{color\.(.+)\}$/', $trimmed, $matches) === 1) {
            return $matches[1];
        }

        return $trimmed;
    }
}
