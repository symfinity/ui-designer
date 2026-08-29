<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Typography;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Enum\ProviderFeature;
use Symfinity\FontManager\Exception\ConfigurationException;
use Symfinity\FontManager\Import\FontPairingImportPort;
use Symfinity\FontManager\Provider\FontProviderInterface;
use Symfinity\FontManager\Provider\ProviderRegistry;
final class FontManagerFontPreviewAdapter implements FontPreviewPort
{
    /** @var list<string> */
    private const POPULAR_FAMILIES = [
        'Inter',
        'Roboto',
        'Open Sans',
        'Lato',
        'Montserrat',
        'Poppins',
        'Source Sans 3',
        'Nunito',
        'Raleway',
        'Work Sans',
        'Playfair Display',
        'Merriweather',
        'Source Serif 4',
        'Libre Baskerville',
        'JetBrains Mono',
        'Fira Code',
        'IBM Plex Mono',
        'DM Sans',
        'DM Serif Display',
        'Space Grotesk',
    ];

    /**
     * @param array<string, mixed> $pairingsConfig
     * @param array<string, mixed> $fontsConfig
     */
    public function __construct(
        private readonly ProviderRegistry $providerRegistry,
        private readonly FontPairingImportPort $pairingImportPort,
        private readonly FonttrioPairingCatalog $bundledPairingCatalog,
        private readonly array $pairingsConfig = [],
        private readonly array $fontsConfig = [],
    ) {
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function searchAvailable(): bool
    {
        try {
            $provider = $this->googleProvider();

            return $provider->supports(ProviderFeature::SEARCH) && $this->canSearch($provider);
        } catch (\Throwable) {
            return false;
        }
    }

    public function listPairings(): array
    {
        /** @var array<string, array{id: string, label: string, source: string}> $indexed */
        $indexed = [];

        foreach ($this->bundledPairingCatalog->list() as $entry) {
            $indexed[$entry['id']] = $entry;
        }

        /** @var mixed $catalog */
        $catalog = $this->pairingsConfig['catalog'] ?? [];
        if (is_array($catalog)) {
            foreach ($catalog as $id => $config) {
                if (!is_string($id) || !is_array($config)) {
                    continue;
                }

                $source = isset($config['source']) && is_string($config['source'])
                    ? $config['source']
                    : '@fonttrio/' . $id;
                $label = isset($config['label']) && is_string($config['label']) ? $config['label'] : $id;

                $indexed[$id] = [
                    'id' => $id,
                    'label' => $label,
                    'source' => $source,
                ];
            }
        }

        $entries = array_values($indexed);
        usort(
            $entries,
            static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']),
        );

        return $entries;
    }

    public function search(string $query, int $limit = 20): array
    {
        if (!$this->searchAvailable()) {
            return $this->filterPopular($query, $limit);
        }

        try {
            $provider = $this->googleProvider();
            $results = $provider->searchFonts($query, $limit);
        } catch (\Throwable) {
            return $this->filterPopular($query, $limit);
        }

        $mapped = [];
        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $family = $result['family'] ?? '';
            if (!is_string($family) || $family === '') {
                continue;
            }

            $mapped[] = [
                'family' => $family,
                'category' => is_string($result['category'] ?? null) ? $result['category'] : 'sans-serif',
            ];
        }

        return $mapped;
    }

    public function popularFamilies(): array
    {
        return self::POPULAR_FAMILIES;
    }

    public function selectionFromPairing(string $pairingId): TypographySelection
    {
        $source = $this->resolvePairingSource($pairingId);
        $result = $this->pairingImportPort->import($source);

        $heading = $this->familyForRole($result->getRoles()['heading'], $result->getFonts());
        $body = $this->familyForRole($result->getRoles()['body'], $result->getFonts());
        $mono = $this->familyForRole($result->getRoles()['mono'], $result->getFonts());

        return (new TypographySelection())->withPairing($result->getId(), $heading, $body, $mono);
    }

    public function selectionFromActiveConfig(): ?TypographySelection
    {
        $active = $this->pairingsConfig['active'] ?? null;
        if (!is_string($active) || $active === '') {
            return null;
        }

        /** @var mixed $roles */
        $roles = $this->pairingsConfig['active_roles'] ?? [];
        if (!is_array($roles)) {
            return $this->selectionFromPairing($active);
        }

        $heading = $this->familyFromSlug(is_string($roles['heading'] ?? null) ? $roles['heading'] : null);
        $body = $this->familyFromSlug(is_string($roles['body'] ?? null) ? $roles['body'] : null);
        $mono = $this->familyFromSlug(is_string($roles['mono'] ?? null) ? $roles['mono'] : null);

        if ($heading === null || $body === null || $mono === null) {
            return $this->selectionFromPairing($active);
        }

        return (new TypographySelection())->withPairing($active, $heading, $body, $mono);
    }

    public function renderPreviewHtml(TypographySelection $selection): string
    {
        if ($selection->isEmpty()) {
            return '';
        }

        $provider = $this->googleProvider();
        $parts = [
            '<link rel="preconnect" href="https://fonts.googleapis.com">',
            '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
        ];

        foreach ([
            'heading' => [$selection->headingFamily, [400, 500, 600, 700]],
            'body' => [$selection->bodyFamily, [400, 500, 600, 700]],
            'mono' => [$selection->monoFamily, [400, 500, 700]],
        ] as [$family, $weights]) {
            if (!is_string($family) || $family === '') {
                continue;
            }

            $parts[] = $this->renderStylesheetLink($provider, $family, $weights, ['normal', 'italic']);
        }

        $css = $this->renderBridgeCss($selection);
        if ($css !== '') {
            $parts[] = $css;
        }

        return implode("\n", $parts);
    }

    private function googleProvider(): FontProviderInterface
    {
        return $this->providerRegistry->getProvider('google');
    }

    private function canSearch(FontProviderInterface $provider): bool
    {
        try {
            $provider->searchFonts('a', 1);

            return true;
        } catch (ConfigurationException) {
            return false;
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolvePairingSource(string $pairingId): string
    {
        /** @var mixed $catalog */
        $catalog = $this->pairingsConfig['catalog'] ?? [];
        if (is_array($catalog) && isset($catalog[$pairingId]) && is_array($catalog[$pairingId])) {
            $source = $catalog[$pairingId]['source'] ?? null;
            if (is_string($source) && $source !== '') {
                return $source;
            }
        }

        foreach ($this->bundledPairingCatalog->list() as $entry) {
            if ($entry['id'] === $pairingId && $entry['source'] !== '') {
                return $entry['source'];
            }
        }

        return '@fonttrio/' . $pairingId;
    }

    /**
     * @param array<string, array<string, mixed>> $fonts
     */
    private function familyForRole(string $slug, array $fonts): string
    {
        $entry = $fonts[$slug]['family'] ?? null;
        if (is_string($entry) && $entry !== '') {
            return $entry;
        }

        return $this->familyFromSlug($slug) ?? $slug;
    }

    private function familyFromSlug(?string $slug): ?string
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $entry = $this->fontsConfig[$slug] ?? null;
        if (!is_array($entry)) {
            return null;
        }

        $family = $entry['family'] ?? null;

        return is_string($family) && $family !== '' ? $family : null;
    }

    /**
     * @param list<int>    $weights
     * @param list<string> $styles
     */
    private function renderStylesheetLink(
        FontProviderInterface $provider,
        string $family,
        array $weights,
        array $styles,
    ): string {
        $links = $provider->renderCdnLinks($family, $weights, $styles, FontDisplay::SWAP);
        if (preg_match('/<link rel="stylesheet" href="[^"]+">/', $links, $matches) === 1) {
            return $matches[0];
        }

        return $links;
    }

    private function renderBridgeCss(TypographySelection $selection): string
    {
        $sans = $this->cssFamily($selection->bodyFamily, 'sans-serif');
        $heading = $this->cssFamily($selection->headingFamily, 'serif');
        $mono = $this->cssFamily($selection->monoFamily, 'monospace');

        $hostBridge = sprintf(
            '@layer kernel.profile{[data-ui-root],:root,html,body{--ui-font-family-sans:%1$s;--font-heading:%2$s;--ui-font-family-mono:%3$s;--font-family-sans:%1$s;--font-family-mono:%3$s}}',
            $sans,
            $heading,
            $mono,
        );

        $hostTypography = sprintf(
            ':root,html,body,[data-ui-root]{--ui-font-family-sans:%1$s;--font-heading:%2$s;--ui-font-family-mono:%3$s}'
            . 'body,[data-ui-role="typography"][data-ui-variant="p"],[data-ui-variant="body"]{font-family:%1$s!important}'
            . 'h1,h2,h3,h4,h5,h6,[data-ui-role="typography"][data-ui-variant="h1"],[data-ui-role="typography"][data-ui-variant="h2"],[data-ui-role="typography"][data-ui-variant="h3"]{font-family:%2$s!important}'
            . 'code,pre,kbd,samp,[data-ui-variant="small"].fonts-lab__mono,.ui-designer-drawer__select-mono{font-family:%3$s!important}',
            $sans,
            $heading,
            $mono,
        );

        // Unlayered + scoped — drawer chrome font-family otherwise wins over @layer host bridge.
        $drawerPreview = sprintf(
            '[data-drawer-type-preview] [data-ui-role="typography"][data-ui-variant="h2"]{font-family:%1$s!important;font-weight:600;font-size:1.125rem;line-height:1.3;margin:0 0 .5rem;color:var(--ud-drawer-text,#fafafa)}'
            . '[data-drawer-type-preview] [data-ui-role="typography"][data-ui-variant="p"]{font-family:%2$s!important;font-size:.875rem;line-height:1.5;margin:0 0 .35rem;color:var(--ud-drawer-text,#fafafa)}'
            . '[data-drawer-type-preview] [data-ui-role="typography"][data-ui-variant="small"]:not([data-drawer-type-mono]){font-family:%2$s!important;font-size:.75rem;line-height:1.45;margin:0 0 .35rem;color:var(--ud-drawer-muted,#a3a3a3)}'
            . '[data-drawer-type-preview] [data-drawer-type-mono]{font-family:%3$s!important;font-size:.75rem;line-height:1.45;margin:0;color:var(--ud-drawer-muted,#a3a3a3)}',
            $heading,
            $sans,
            $mono,
        );

        return sprintf(
            '<style id="ui-designer-typography-preview-css">%s</style><style id="ui-designer-host-typography-css">%s</style><style id="ui-designer-drawer-type-preview-css">%s</style>',
            $hostBridge,
            $hostTypography,
            $drawerPreview,
        );
    }

    private function cssFamily(?string $family, string $fallbackGeneric): string
    {
        if ($family === null || $family === '') {
            return $fallbackGeneric;
        }

        $escaped = str_replace("'", "\\'", $family);

        return sprintf("'%s', %s", $escaped, $fallbackGeneric);
    }

    /**
     * @return list<array{family: string, category: string}>
     */
    private function filterPopular(string $query, int $limit): array
    {
        $queryLower = strtolower(trim($query));
        $results = [];

        foreach (self::POPULAR_FAMILIES as $family) {
            if ($queryLower !== '' && !str_contains(strtolower($family), $queryLower)) {
                continue;
            }

            $results[] = [
                'family' => $family,
                'category' => str_contains(strtolower($family), 'mono') ? 'monospace' : 'sans-serif',
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }
}
