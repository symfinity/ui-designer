# Commands

All commands use the `ui-designer:` prefix. Available when the bundle is registered (typically **dev** and **test**).

## ui-designer:validate

Validate a DTCG export pack directory.

```bash
php bin/console ui-designer:validate config/themes/my-brand
```

## ui-designer:export

Export a built-in preset draft to the themes directory.

```bash
php bin/console ui-designer:export --lineage=my-brand --preset=semantic
```

## ui-designer:import-registry

Import registry theme JSON into DTCG draft files.

```bash
php bin/console ui-designer:import-registry fixtures/zinc.json --name=my-brand
```

## ui-designer:import-tokens

Import W3C, Tokens Studio, or Figma variables export into DTCG.

```bash
php bin/console ui-designer:import-tokens path/to/tokens.json --format=w3c-dtcg
```

## See also

- [Quickstart](quickstart.md)
- [Configuration](configuration.md)
