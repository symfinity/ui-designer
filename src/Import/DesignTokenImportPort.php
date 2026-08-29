<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Import;

use Symfinity\UiDesigner\Model\DesignerDraft;

interface DesignTokenImportPort
{
    public function import(ImportRequest $request): ImportResult;
}

final readonly class ImportRequest
{
    public function __construct(
        public string $type,
        public ?string $url = null,
        public ?string $file = null,
        public ?string $name = null,
        public bool $continueOnError = false,
    ) {
    }
}

final readonly class ImportResult
{
    public function __construct(
        public DesignerDraft $draft,
        public ?DesignerDraft $darkDraft = null,
        /** @var list<string> */
        public array $warnings = [],
    ) {
    }
}
