# Quick start

## Install

```bash
composer require --dev symfinity/ui-designer
```

Flex registers the bundle for **dev/test** and copies `config/packages/symfinity_ui_designer.yaml`.

## Open the studio

With `APP_ENV=dev`:

```bash
symfony server:start
# or your usual dev web server
```

Browse to **`/ui-designer`** (redirects to `/ui-designer/studio`).

## First authoring loop

1. **Load preset** — choose **semantic** (or default) from the studio preset strip
2. **Pick a colour** — select **primary** role, click a palette ramp ref (e.g. `blue.600`)
3. **Preview** — ux-blocks specimens update live in the canvas
4. **Export** — use the studio export action or CLI:

```bash
php bin/console ui-designer:export --lineage=my-brand --preset=semantic
```

Exported packs land under `config/themes/{lineage}/` (DTCG + `theme.meta.yaml`).

## Import from registry

```bash
php bin/console ui-designer:import-registry path/to/theme.json --name=my-brand
php bin/console ui-designer:validate var/ui-designer/my-brand
```

Or use **Import** in the studio UI for review before commit.

## Kernel gallery preview

From the studio, open kernel preview — lands on `/kernel?preview={token}` with session-injected CSS. Exit preview returns to the designer.

## App-wide preview (0.1.1+)

Wire the preview host once in `templates/base.html.twig`:

```twig
{% include '@UiDesigner/preview/_host.html.twig' %}
```

Enable `framework.fragments: true` in your framework config (the drawer embeds via Twig `render()`).

From `/ui-designer`, load a draft and click **Start customizing** — the Theme Generator drawer opens over your app. Use `ui_designer_preview_url('route_name')` on internal links so the preview token survives navigation. Edits in the drawer update the host page CSS without a full reload.

## See also

- [Commands](commands.md)
- [Configuration](configuration.md)
- [CHANGELOG](../CHANGELOG.md)
- [Contributing](../CONTRIBUTING.md)
- [GitHub Issues](https://github.com/symfinity/ui-designer/issues)
