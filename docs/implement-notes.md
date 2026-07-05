# UI Designer v0.1.0 — implement notes (141)

## Visual sign-off checklist (T029)

Maintainer smoke on dogfood `ui-lab` after `make dogfood-serve SLUG=ui-lab`:

| Check | Route / action | Expected |
|-------|----------------|----------|
| Hub link | `/` | Links to **UI Designer** at `/ui-designer` |
| Studio shell | `/ui-designer/studio` | Nav Import · Studio · Export; panel + canvas; kernel-token chrome |
| Preset load | Load **semantic** preset | Preview CSS via single `#theme-live-css`; ux-blocks specimens visible |
| Ref picker | Apply primary → `blue.600` | Live preview updates; contrast panel rows refresh |
| Undo | Ctrl/Cmd+Z or Undo control | Previous ref restored |
| Snapshot | Save label → restore | Draft matches snapshot payload |
| Kernel preview | POST preview → `/kernel?preview=` | Banner links back to UI Designer; exit revokes token |
| Import hub | `/ui-designer/import` | Shadcn registry review stub reachable |
| Prod gate | `APP_ENV=prod` | `/ui-designer` → 404 |

Screenshot or checklist tick required before maintainer tag **v0.1.0**.

## QA commands

```bash
cd src/symfinity
docker compose --env-file .env.docker run --rm -T -w /app php \
  vendor/bin/phpunit -c packages/ui-designer/phpunit.xml.dist
```
