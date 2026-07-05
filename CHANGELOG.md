# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-07-05

### Added

- Initial release of **symfinity/ui-designer** — dev-only DTCG theme authoring for UI Kernel
- Visual studio at `/ui-designer` — ref picker, contrast panel, structural controls, ux-blocks preview canvas
- DTCG import from shadcn registry and design-token files (W3C, Tokens Studio, Figma)
- Session preview tokens and kernel gallery deep link (`/kernel?preview=`)
- Export to `config/themes/{lineage}/` with strict contrast export gate
- Undo stack (20 deep) and named snapshot save/restore
- Console commands: `ui-designer:validate`, `ui-designer:export`, `ui-designer:import-registry`, `ui-designer:import-tokens`
- Flex recipe `symfinity/ui-designer` 0.1 — bundle registered for **dev** and **test** only
- Handbook under `docs/` with OOTB verification profile
- Public [ROADMAP.md](ROADMAP.md) — **0.1.x** foundation as the current release line

### Notes

- Requires `symfinity/ui-kernel` **^0.3.1** (theme inheritance)
- DTCG-native authoring only — no legacy YAML pack import path
