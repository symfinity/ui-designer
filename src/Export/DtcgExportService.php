<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Export;

use Symfinity\UiDesigner\Factory\DtcgDraftFactory;
use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfony\Component\Filesystem\Filesystem;

final class DtcgExportService
{
    public function __construct(
        private readonly DtcgDraftFactory $draftFactory,
        private readonly ExportPathGuard $pathGuard,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function export(DesignerDraft $draft, ?string $exportRoot = null): string
    {
        $root = $exportRoot ?? $this->draftFactory->exportRoot();
        $lineageDir = $this->pathGuard->assertLineagePath($root, $draft->lineage);
        $this->filesystem->mkdir($lineageDir);

        $pack = $this->draftFactory->buildExportPack($draft);
        $this->filesystem->dumpFile($lineageDir . '/theme.meta.yaml', $pack['theme.meta.yaml']);

        foreach ($pack['variants'] as $variantId => $yaml) {
            $filename = is_string($variantId) && str_ends_with($variantId, '.yaml')
                ? $variantId
                : $variantId . '.dtcg.yaml';
            $this->filesystem->dumpFile($lineageDir . '/' . $filename, $yaml);
        }

        return $lineageDir;
    }
}
