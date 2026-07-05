<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Collaboration;

use Symfinity\UiDesigner\Util\PayloadUtil;
use Symfony\Component\Filesystem\Filesystem;

final class DraftSnapshotStore
{
    public function __construct(
        private readonly int $maxPerDraft,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function save(string $exportRoot, DraftSnapshot $snapshot): string
    {
        $dir = $this->draftDir($exportRoot, $snapshot->draftId);
        $this->filesystem->mkdir($dir);

        $filename = sprintf('%s-%s.json', $snapshot->id, $this->slugify($snapshot->label));
        $path = $dir . '/' . $filename;
        $encoded = json_encode($snapshot->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $this->filesystem->dumpFile($path, $encoded . "\n");

        $this->pruneIfNeeded($exportRoot, $snapshot->draftId);

        return $path;
    }

    /**
     * @return list<DraftSnapshot>
     */
    public function list(string $exportRoot, string $draftId): array
    {
        $dir = $this->draftDir($exportRoot, $draftId);
        if (!is_dir($dir)) {
            return [];
        }

        $snapshots = [];
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (!is_array($data)) {
                continue;
            }
            $snapshots[] = DraftSnapshot::fromArray(PayloadUtil::stringKeyMap($data));
        }

        usort(
            $snapshots,
            static fn (DraftSnapshot $a, DraftSnapshot $b): int => $b->createdAt <=> $a->createdAt,
        );

        return $snapshots;
    }

    public function find(string $exportRoot, string $draftId, string $snapshotId): ?DraftSnapshot
    {
        foreach ($this->list($exportRoot, $draftId) as $snapshot) {
            if ($snapshot->id === $snapshotId) {
                return $snapshot;
            }
        }

        return null;
    }

    private function draftDir(string $exportRoot, string $draftId): string
    {
        return rtrim($exportRoot, '/') . '/.drafts/' . $draftId;
    }

    private function pruneIfNeeded(string $exportRoot, string $draftId): void
    {
        $snapshots = $this->list($exportRoot, $draftId);
        if (count($snapshots) <= $this->maxPerDraft) {
            return;
        }

        $toRemove = array_slice($snapshots, $this->maxPerDraft);
        $dir = $this->draftDir($exportRoot, $draftId);
        foreach ($toRemove as $snapshot) {
            $pattern = $dir . '/' . $snapshot->id . '-*.json';
            foreach (glob($pattern) ?: [] as $file) {
                $this->filesystem->remove($file);
            }
        }
    }

    private function slugify(string $label): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $label) ?? '', '-'));

        return substr($slug !== '' ? $slug : 'snapshot', 0, 48);
    }
}
