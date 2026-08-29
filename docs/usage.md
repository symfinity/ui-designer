# Usage

Day-to-day patterns after [Installation](installation.md) and [Quick start](quickstart.md).

## Studio workflow

1. Open **`/ui-designer`** in `APP_ENV=dev`
2. Load a preset lineage from the strip
3. Pick colour roles and ramp refs — canvas specimens update live
4. Export DTCG packs to `config/themes/{lineage}/` or use CLI `ui-designer:export`

## App-wide preview (0.1.1+)

Wire `@UiDesigner/preview/_host.html.twig` once in `templates/base.html.twig`. Enable `framework.fragments: true`.

From the studio, **Start customizing** opens the Theme Generator drawer over your app routes. Use `ui_designer_preview_url('route_name')` on internal links so the preview token survives navigation.

## Import paths

- **Registry JSON** — `ui-designer:import-registry` or studio Import UI
- **DTCG / Tokens Studio** — `ui-designer:import-tokens` (see [Commands](commands.md))

## See also

- [Configuration](configuration.md)
- [Verification](verification.md)
