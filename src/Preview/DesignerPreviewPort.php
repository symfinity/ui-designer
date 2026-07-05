<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Preview;

use Symfinity\UiDesigner\Authoring\DtcgSemanticEditor;
use Symfinity\UiDesigner\Authoring\StructuralDraft;
use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfinity\UiDesigner\Studio\StructuralPreviewOverlay;
use Symfinity\UiKernel\Css\CssGenerator;
use Symfinity\UiKernel\Dtcg\BuiltinDtcgThemeCatalog;
use Symfinity\UiKernel\Dtcg\ThemeDtcgResolver;
use Symfinity\UiKernel\Palette\PaletteRampSampler;
use Symfinity\UiKernel\Token\DesignTokenSet;
use Symfinity\UiKernel\Token\PaletteRefGrammar;
use Symfinity\UiKernel\Token\SemanticColorMap;

final class DesignerPreviewPort
{
    public function __construct(
        private readonly BuiltinDtcgThemeCatalog $catalog,
        private readonly ThemeDtcgResolver $dtcgResolver,
        private readonly CssGenerator $cssGenerator,
        private readonly StructuralPreviewOverlay $structuralOverlay,
        private readonly DtcgSemanticEditor $semanticEditor,
        private readonly PaletteRampSampler $paletteSampler,
    ) {
    }

    public function preview(DesignerDraft $draft, ?StructuralDraft $structural = null, ?string $variantKey = null): PreviewArtifact
    {
        $variant = $this->catalog->get($draft->id);
        $variantKey ??= $this->resolveVariantKey($draft);
        $tokens = $this->resolveTokens($draft, $variantKey, $variant);

        $css = $this->cssGenerator->forResolvedTokens(
            $draft->id,
            $tokens,
            $variant->schemaVersion(),
            scrollMotion: $variant->scrollMotion(),
        );

        $structural ??= StructuralDraft::empty();
        $css = $this->structuralOverlay->overlayCss(
            $draft->id,
            $css,
            $structural->radiusStep,
            $structural->shadowStep,
            $variant->schemaVersion(),
        );

        return new PreviewArtifact(
            themeId: $draft->id,
            css: $css,
            resolvedTokens: $tokens->all(),
        );
    }

    /**
     * @return array<string, array{ref: string, color: string}>
     */
    public function resolvedRoleColors(DesignerDraft $draft, ?string $variantKey = null): array
    {
        try {
            $variant = $this->catalog->get($draft->id);
            $variantKey ??= $this->resolveVariantKey($draft);
            $tokens = $this->resolveTokens($draft, $variantKey, $variant);
        } catch (\Throwable) {
            return [];
        }

        $roles = [];
        foreach (SemanticColorMap::ROLE_TO_CSS as $role => $cssVar) {
            $color = $tokens->all()[$cssVar] ?? null;
            if (!is_string($color) || $color === '') {
                continue;
            }
            $ref = $this->semanticEditor->readRoleRef($draft, $role, $variantKey) ?? '';
            $roles[$role] = [
                'ref' => $ref,
                'color' => $color,
            ];
        }

        return $roles;
    }

    private function resolveTokens(
        DesignerDraft $draft,
        string $variantKey,
        \Symfinity\UiKernel\Dtcg\BuiltinThemeVariant $variant,
    ): DesignTokenSet {
        $base = $this->dtcgResolver->resolve($variant);
        $recipe = $variant->paletteRecipe();
        $overrides = [];

        foreach ($this->semanticEditor->allRoleRefs($draft, $variantKey) as $role => $ref) {
            if (!isset(SemanticColorMap::ROLE_TO_CSS[$role])) {
                continue;
            }

            try {
                PaletteRefGrammar::assertValid($ref);
            } catch (\Throwable) {
                continue;
            }

            $overrides[SemanticColorMap::ROLE_TO_CSS[$role]] = $this->paletteSampler->resolveToSrgb($ref, $recipe);
        }

        if ($overrides === []) {
            return $base;
        }

        return new DesignTokenSet(
            [...$base->all(), ...$overrides],
            $base->schemaVersion(),
        );
    }

    private function resolveVariantKey(DesignerDraft $draft): string
    {
        if ($draft->variants !== []) {
            return (string) array_key_first($draft->variants);
        }

        if ($draft->variantPayloads !== []) {
            return (string) array_key_first($draft->variantPayloads);
        }

        return 'light';
    }
}
