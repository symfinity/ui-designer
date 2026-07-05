<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Export;

final class ExportPathGuard
{
    public function assertWritable(string $exportRoot): void
    {
        $root = rtrim($exportRoot, '/');
        if ($root === '') {
            throw new \InvalidArgumentException('Export root must not be empty.');
        }

        if (is_dir($root) && !is_writable($root)) {
            throw new \RuntimeException(sprintf('Export root "%s" is not writable.', $root));
        }
    }

    public function assertLineagePath(string $exportRoot, string $lineage): string
    {
        $this->assertWritable($exportRoot);
        $path = rtrim($exportRoot, '/') . '/' . $lineage;
        if (str_contains($path, '..')) {
            throw new \InvalidArgumentException('Invalid lineage path.');
        }

        return $path;
    }
}
