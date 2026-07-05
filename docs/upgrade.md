# Upgrade

## 0.1.0

First public release. Install with:

```bash
composer require --dev symfinity/ui-designer:^0.1
```

Requires **symfinity/ui-kernel ^0.3.1**.

## Migrating from ui-themer

UI Designer replaces **ui-themer** for new authoring work. There is **no** import path for legacy ui-themer YAML packs (`preset` / `tone` / `semantics`).

Recommended path:

1. `composer remove symfinity/ui-themer` (when no longer needed)
2. `composer require --dev symfinity/ui-designer`
3. Re-author themes in DTCG or import from shadcn / token files

## See also

- [Installation](installation.md)
- [CHANGELOG](../CHANGELOG.md)
