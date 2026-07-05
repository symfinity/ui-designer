<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Import;

use Symfinity\UiDesigner\Authoring\DtcgSemanticEditor;
use Symfinity\UiDesigner\Model\AuthorGraphNode;
use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfinity\UiDesigner\Util\PayloadUtil;
use Symfinity\UiKernel\Palette\OklchColorSpace;
use Symfinity\UiKernel\Palette\PaletteRampSampler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ShadcnRegistryImporter implements DesignTokenImportPort
{
    /** @var array<string, string> */
    private const SHADCN_ROLE_MAP = [
        'primary' => 'primary',
        'secondary' => 'secondary',
        'accent' => 'accent',
        'destructive' => 'danger',
        'background' => 'surface',
        'foreground' => 'text',
        'muted-foreground' => 'text_muted',
        'border' => 'border',
        'ring' => 'focus',
    ];

    public function __construct(
        private readonly DtcgSemanticEditor $semanticEditor,
        private readonly OklchColorSpace $colorSpace = new OklchColorSpace(),
        private readonly PaletteRampSampler $sampler = new PaletteRampSampler(),
        private readonly ?HttpClientInterface $httpClient = null,
        private readonly string $paletteuiBaseUrl = 'https://paletteui.xyz/r',
    ) {
    }

    public function import(ImportRequest $request): ImportResult
    {
        if ($request->type !== 'shadcn-theme') {
            throw new \InvalidArgumentException(sprintf('Unsupported import type "%s".', $request->type));
        }

        $json = $this->loadJson($request);
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $name = $request->name ?? PayloadUtil::string($data['name'] ?? null, 'imported-theme');
        $lightVars = $this->extractCssVars($data, 'cssVars', 'light');
        $darkVars = $this->extractCssVars($data, 'cssVars', 'dark');

        $lightDraft = $this->buildDraftFromVars($name, $lightVars);
        $darkDraft = $darkVars !== [] ? $this->buildDraftFromVars($name . '-dark', $darkVars) : null;

        return new ImportResult($lightDraft, $darkDraft);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    private function extractCssVars(array $data, string $key, string $mode): array
    {
        $cssVars = $data[$key] ?? null;
        if (!is_array($cssVars)) {
            return [];
        }

        $modeVars = $cssVars[$mode] ?? $cssVars;
        if (!is_array($modeVars)) {
            return [];
        }

        $vars = [];
        foreach ($modeVars as $k => $v) {
            if (is_string($k) && is_string($v)) {
                $vars[$k] = $v;
            }
        }

        return $vars;
    }

    /**
     * @param array<string, string> $vars
     */
    private function buildDraftFromVars(string $id, array $vars): DesignerDraft
    {
        $basePayload = $this->baseColorPayload();
        $draft = new DesignerDraft(
            id: $id,
            lineage: 'imported-' . preg_replace('/[^a-z0-9-]+/i', '-', $id),
            graph: [new AuthorGraphNode($id, AuthorGraphNode::SOURCE_INLINE_DTCG, $basePayload)],
            variants: ['light' => $id],
            variantPayloads: ['light' => $basePayload],
        );

        foreach ($vars as $shadcnKey => $literal) {
            $role = self::SHADCN_ROLE_MAP[$shadcnKey] ?? null;
            if ($role === null) {
                continue;
            }

            try {
                $tuple = str_starts_with($literal, '#')
                    ? $this->colorSpace->fromHex($literal)
                    : $this->colorSpace->parse($literal);
                $ref = $this->nearestPaletteRef($tuple);
                $draft = $this->semanticEditor->assignRoleRef($draft, $role, $ref);
            } catch (\Throwable) {
                continue;
            }
        }

        return $draft;
    }

    /**
     * @return array<string, mixed>
     */
    private function baseColorPayload(): array
    {
        return [
            'color' => [
                'primary' => ['$type' => 'color', '$value' => '{color.blue.500}'],
                'secondary' => ['$type' => 'color', '$value' => '{color.mono.slate.500}'],
                'surface' => ['base' => ['$type' => 'color', '$value' => '{color.mono.neutral.100}']],
                'text' => [
                    'default' => ['$type' => 'color', '$value' => '{color.mono.slate.900}'],
                    'muted' => ['$type' => 'color', '$value' => '{color.mono.slate.500}'],
                ],
                'border' => ['default' => ['$type' => 'color', '$value' => '{color.mono.slate.300}']],
                'danger' => ['$type' => 'color', '$value' => '{color.red.500}'],
                'focus' => ['$type' => 'color', '$value' => '{color.blue.500}'],
            ],
        ];
    }

    private function nearestPaletteRef(\Symfinity\UiKernel\Palette\OklchTuple $tuple): string
    {
        $bestRef = 'blue.500';
        $bestDelta = PHP_FLOAT_MAX;
        foreach (['red', 'orange', 'blue', 'green', 'slate'] as $hue) {
            foreach ([400, 500, 600] as $step) {
                $ref = $hue === 'slate' ? "mono.slate.$step" : "$hue.$step";
                try {
                    $candidate = $this->sampler->resolveToOklch($ref);
                    $delta = abs($candidate->l - $tuple->l)
                        + abs($candidate->c - $tuple->c)
                        + abs($candidate->h - $tuple->h);
                    if ($delta < $bestDelta) {
                        $bestDelta = $delta;
                        $bestRef = $ref;
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return $bestRef;
    }

    private function loadJson(ImportRequest $request): string
    {
        if ($request->file !== null && is_file($request->file)) {
            return (string) file_get_contents($request->file);
        }

        $url = $request->url;
        if ($url === null && $request->name !== null) {
            $url = rtrim($this->paletteuiBaseUrl, '/') . '/' . rawurlencode($request->name) . '.json';
        }

        if ($url === null) {
            throw new \InvalidArgumentException('Registry import requires --url, --file, or name argument.');
        }

        $client = $this->httpClient ?? HttpClient::create(['timeout' => 30]);
        $response = $client->request('GET', $url);

        return $response->getContent();
    }
}
