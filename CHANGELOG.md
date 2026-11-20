# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.2] - 2026-11-20

### Fixed

- PHPUnit bootstrap registers this package's PSR-4 prefixes from `composer.json` after loading the monorepo or package autoload, and narrows the decoded autoload sections before reading `psr-4`

### Notes

- No change to the theme generator, tokens, or the public API

## [0.1.1] - 2026-07-12

### Added

- **Theme Generator drawer** — fixed right overlay with grouped token panel (Brand, Base, Other; Sidebar and Chart when theme roles exist), OKLCH colour controls, palette ref apply, and Light/Dark mode toggle
- **App-wide preview host** — include `@UiDesigner/preview/_host.html.twig` in your base layout; `ui_designer_preview_url()` preserves preview tokens on internal links; session fallback when the query param is absent
- **Start customizing** — mints a preview token, opens the drawer over your app, and returns to the page you started from
- **Live sync** — token edits in the drawer update the host page preview stylesheet without a full reload (including Turbo navigations)
- Drawer routes for start, close, fragment, reopen, and apply-colour under `/ui-designer/drawer/*`
- Twig helpers: `ui_designer_preview_context()`, `ui_designer_drawer_session()`, `ui_designer_drawer_preview_css()`

### Changed

- Token panel groups semantic roles into Brand, Base, and Other (plus Sidebar/Chart when available)
- `/ui-designer/studio` redirects to your app when a drawer session is active; import hub remains the empty-state entry
- Exiting preview clears host session binding and closes the drawer

### Notes

- Flex recipe post-install documents the preview host partial and `framework.fragments: true` requirement
- Requires **symfinity/ui-kernel ^0.3.1** (unchanged from 0.1.0)

## [0.1.0] - 2026-07-05

### Added

- Initial release of **symfinity/ui-designer** — dev-only DTCG theme authoring for UI Kernel
- Visual studio at `/ui-designer` — ref picker, contrast panel, structural controls, ux-blocks preview canvas
- DTCG import from registry themes and design-token files (W3C, Tokens Studio, Figma)
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
