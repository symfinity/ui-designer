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

## Import from shadcn

```bash
php bin/console ui-designer:import-registry path/to/theme.json --name=my-brand
php bin/console ui-designer:validate var/ui-designer/my-brand
```

Or use **Import** in the studio UI for review before commit.

## Kernel gallery preview

From the studio, open kernel preview — lands on `/kernel?preview={token}` with session-injected CSS. Exit preview returns to the designer.

## See also

- [Commands](commands.md)
- [Configuration](configuration.md)
- [CHANGELOG](../CHANGELOG.md)
- [Contributing](../CONTRIBUTING.md)
- [GitHub Issues](https://github.com/symfinity/ui-designer/issues)
