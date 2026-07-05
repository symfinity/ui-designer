<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Util;

final class PayloadUtil
{
    public static function string(mixed $value, string $default = ''): string
    {
        return is_string($value) ? $value : $default;
    }

    public static function int(mixed $value, int $default = 0): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    public static function optionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array<string, mixed>
     */
    public static function stringKeyMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $result[$key] = $item;
            }
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public static function stringMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                continue;
            }

            if (is_string($item)) {
                $result[$key] = $item;
            } elseif (is_int($item) || is_float($item)) {
                $result[$key] = (string) $item;
            }
        }

        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function nestedStringKeyMaps(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (!is_string($key) || !is_array($item)) {
                continue;
            }

            $result[$key] = self::stringKeyMap($item);
        }

        return $result;
    }
}
