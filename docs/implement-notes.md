# UI Designer v0.1.1 — implement notes (142)

**Date:** 2026-07-12  
**Maintainer sign-off:** ui-lab smoke + drawer close/reopen cycle verified

## Shipped in this patch

| Area | Notes |
|------|--------|
| Drawer shell | Fixed right overlay `@UiDesigner/drawer/_shell.html.twig`; title **UI Designer**; dark `#0a0a0a` chrome |
| Preview host | `@UiDesigner/preview/_host.html.twig` + `PreviewHostSubscriber` + `ui_designer_preview_url()` |
| Close / reopen | × closes drawer; preview banner **Open UI Designer** reopens without new token |
| ui-lab | `base.html.twig` includes preview host; hub/kernel/fonts links preserve preview query |
| Live sync | Drawer JS updates `#ui-designer-preview-css` on apply-colour / preview-mode / undo |
| Tests | 45 PHPUnit (2 skipped without font-manager); PHPStan level max + baseline |

## Smoke (ui-lab)

```bash
make dogfood-serve SLUG=ui-lab
# /ui-designer → load semantic → Start customizing → / with drawer
# Navigate / → /kernel → /fonts with preview banner
# Edit Primary in drawer → hub button recolors without reload
# Close drawer (×) → Open UI Designer from banner → repeat
```

## Visual sign-off checklist (maintainer)

- [x] Drawer density matches reference layout (header, mode toggle above fold)
- [x] Flat colour list in ui-kernel semantic order
- [x] OKLCH debounce maps to nearest ref and updates swatch
- [ ] Mobile sheet layout acceptable (< 1280px) — deferred polish

## Known limits

- Preview session store remains in-memory (141); use `preview` query on links — session token fallback stores token id only
- Sidebar/Chart groups hidden until roles exist in semantic map
- Typography tab requires optional `symfinity/font-manager` for live font search

## Contracts (141 + 142 → `_org`)

All normative contracts live under `specs/symfinity/symfinity/_org/contracts/ui-designer/` — see package ROADMAP and symfinity program ROADMAP rows **141** / **142**.
