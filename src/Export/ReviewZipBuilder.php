<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Export;

use Symfinity\UiDesigner\Factory\DtcgDraftFactory;
use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

final class ReviewZipBuilder
{
    public function __construct(
        private readonly DtcgDraftFactory $draftFactory,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function build(DesignerDraft $draft, string $targetZipPath): string
    {
        $pack = $this->draftFactory->buildExportPack($draft);
        $tmpDir = sys_get_temp_dir() . '/ui-designer-review-' . bin2hex(random_bytes(4));
        $this->filesystem->mkdir($tmpDir);

        $this->filesystem->dumpFile($tmpDir . '/theme.meta.yaml', $pack['theme.meta.yaml']);
        foreach ($pack['variants'] as $variantId => $yaml) {
            $filename = $variantId . '.dtcg.yaml';
            $this->filesystem->dumpFile($tmpDir . '/' . $filename, $yaml);
        }

        $this->filesystem->mkdir(\dirname($targetZipPath));
        $zip = new ZipArchive();
        if ($zip->open($targetZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException(sprintf('Could not create ZIP at "%s".', $targetZipPath));
        }

        foreach (glob($tmpDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                $zip->addFile($file, basename($file));
            }
        }
        $zip->close();
        $this->filesystem->remove($tmpDir);

        return $targetZipPath;
    }
}
