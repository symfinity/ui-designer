# Roadmap

Public milestones for symfinity/ui-designer on the path to 1.0.x.

Dev-only theme authoring companion for [symfinity/ui-kernel](../ui-kernel/ROADMAP.md). Designer semver tracks kernel capabilities but releases independently.

Current release line: **0.1.x** (v0.1.0 — Tier 1 + Tier 2 visual studio)

| Version | Focus | Status |
|---------|-------|--------|
| 0.1.x | Foundation | Current |
| 0.2.x | Collaboration | Planned |
| 0.3.x | Import depth | Planned |
| 0.4.x | Preview and compare | Planned |
| 0.5.x | Theme catalog | Planned |
| 0.6.x | Structural authoring | Planned |
| 0.7.x | Kernel sync | Planned |
| 0.8.x | Ecosystem | Planned |
| 0.9.x | Hardening | Planned |
| 1.0.x | Stable dev API | Planned |

## Details

### 0.1.x — Foundation

DTCG-native draft model, visual studio at `/ui-designer`, shadcn and design-token import, session preview on kernel gallery, export with contrast gate, undo stack and named snapshots, Flex recipe (dev/test only), handbook and OOTB verification

Requires ui-kernel **^0.3.1** (theme inheritance and capability probe)

### 0.2.x — Collaboration

Read-only share links for stakeholder review, review ZIP bundles with accessibility summary, YAML comment and provenance policy on export, stricter export gate options, snapshot export and restore UX polish

### 0.3.x — Import depth

Interactive import review (click-to-adjust ref mappings), Figma Variables and Tokens Studio adapter hardening, font pairing picker when font-manager is installed, import diagnostics and fixture-backed regression tests

### 0.4.x — Preview and compare

Dedicated preview pages (component sheet, contrast lab), side-by-side draft vs built-in baseline, multi-specimen ux-blocks canvas, composition graph visual merge indicators

### 0.5.x — Theme catalog

Explore hub for built-in and exported lineages, load consumer theme packs into draft, registry-backed preview resolver, import from handbook theme packs

### 0.6.x — Structural authoring

Full structural token panels (radius, shadow, spacing, motion) aligned with kernel theme schema, preset strip and tweak workflow, OKLCH nearest-ref colour controls beyond semantic roles

### 0.7.x — Kernel sync

Adopt ui-kernel token-engine hooks (computed tokens, expressions), generator palette preview in studio, recipe-aware ramp sampling for import, alignment with ui-kernel **0.4.x+** milestones

### 0.8.x — Ecosystem

symfinity-docs handbook examples, optional ux-workshop theme-pack handoff (JSON V1), font-manager pairing catalog integration, kiosk and ui-lab designer smoke gates

### 0.9.x — Hardening

Studio interaction acceptance suite expansion, visual regression baselines, `repo:doctor` designer checks, performance on large DTCG graphs, API freeze preparation, migration notes from ui-themer (manual paths only)

### 1.0.x — Stable dev API

SemVer-stable CLI and configuration contracts, documented extension ports, long-term compatibility with ui-kernel **1.0.x**, dev-only boundary frozen — still **not** a production runtime theme SSOT

## Releases

- [Packagist](https://packagist.org/packages/symfinity/ui-designer)
- [CHANGELOG](CHANGELOG.md)
- [Upgrade guide](docs/upgrade.md)
- [Documentation](docs/index.md)
