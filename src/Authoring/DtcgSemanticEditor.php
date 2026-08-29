<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Authoring;

use Symfinity\UiDesigner\Model\DesignerDraft;

final class DtcgSemanticEditor
{
    public function __construct(
        private readonly DtcgRolePathMapper $pathMapper,
    ) {
    }

    public function assertPaletteRefOnly(string $role, string $ref): void
    {
        $trimmed = trim($ref);
        if ($trimmed === '') {
            throw new \InvalidArgumentException(sprintf('Semantic role "%s" must not be empty.', $role));
        }

        if (str_starts_with($trimmed, '#')
            || str_starts_with(strtolower($trimmed), 'rgb(')
            || str_starts_with(strtolower($trimmed), 'hsl(')
            || str_starts_with(strtolower($trimmed), 'oklch(')) {
            throw new \InvalidArgumentException(sprintf(
                'Semantic role "%s" must use palette refs only, not raw colour literals (got "%s").',
                $role,
                $ref,
            ));
        }
    }

    public function readRoleRef(DesignerDraft $draft, string $role, string $variantId = 'light'): ?string
    {
        $payload = $draft->variantPayloads[$variantId] ?? $draft->variantPayloads[array_key_first($draft->variantPayloads) ?? ''] ?? null;
        if (!is_array($payload)) {
            return null;
        }

        $path = $this->pathMapper->pathForRole($role);
        $value = $this->readNestedValue($payload, $path . '.$value');
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $this->pathMapper->dtcgAliasToPaletteRef($value);
    }

    /**
     * @return array<string, string> role => palette ref
     */
    public function allRoleRefs(DesignerDraft $draft, ?string $variantId = null): array
    {
        $variantId ??= $this->activeVariantId($draft);
        $refs = [];
        foreach ($this->pathMapper->allRoles() as $role) {
            $ref = $this->readRoleRef($draft, $role, $variantId);
            if ($ref !== null) {
                $refs[$role] = $ref;
            }
        }

        return $refs;
    }

    public function assignRoleRef(DesignerDraft $draft, string $role, string $ref, ?string $variantId = null): DesignerDraft
    {
        $this->assertPaletteRefOnly($role, $ref);
        $this->pathMapper->pathForRole($role);

        $variantId ??= $this->activeVariantId($draft);
        $payloads = $draft->variantPayloads;
        if (!array_key_exists($variantId, $payloads)) {
            throw new \InvalidArgumentException(sprintf('Variant "%s" not found in draft.', $variantId));
        }

        $path = $this->pathMapper->pathForRole($role);
        $payload = $payloads[$variantId];
        $this->writeTokenValue($payload, $path, $this->pathMapper->paletteRefToDtcgAlias($ref));
        $payloads[$variantId] = $payload;

        return new DesignerDraft(
            id: $draft->id,
            lineage: $draft->lineage,
            designSystemId: $draft->designSystemId,
            graph: $draft->graph,
            variants: $draft->variants,
            variantPayloads: $payloads,
        );
    }

    private function activeVariantId(DesignerDraft $draft): string
    {
        if ($draft->variants !== []) {
            return (string) array_key_first($draft->variants);
        }

        if ($draft->variantPayloads !== []) {
            return (string) array_key_first($draft->variantPayloads);
        }

        return 'light';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readNestedValue(array $payload, string $dottedPath): mixed
    {
        $current = $payload;
        foreach (explode('.', $dottedPath) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeTokenValue(array &$payload, string $tokenPath, string $aliasValue): void
    {
        $segments = explode('.', $tokenPath);
        $cursor = &$payload;
        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                    $cursor[$segment] = ['$type' => 'color'];
                }
                $cursor[$segment]['$value'] = $aliasValue;

                return;
            }

            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }
    }
}
