# Installation

## Prerequisites

- PHP 8.2+
- Symfony 7.4 or 8.x
- [symfinity/ui-kernel](https://github.com/symfinity/ui-kernel) **^0.3.1** (theme inheritance)
- [symfinity/recipes](https://github.com/symfinity/recipes) Flex endpoint in your project's `composer.json` (see [recipes README](https://github.com/symfinity/recipes/blob/main/README.md))

For live preview specimens in the studio, install **symfinity/ux-blocks-core** in your dev project (suggested dev dependency — not required for bundle boot).

## Composer

```bash
composer require --dev symfinity/ui-designer
```

## Symfony Flex (OOTB)

The recipe `symfinity/ui-designer` **0.1** applies automatically:

| Step | Result |
|------|--------|
| Bundle | `Symfinity\UiDesigner\UiDesignerBundle` registered in **`dev` and `test` only** |
| Config | `config/packages/symfinity_ui_designer.yaml` copied from package defaults |
| Author routes | Available when `APP_ENV=dev` — `/ui-designer` |

No manual `bundles.php` edits are required when Flex succeeds.

## Production

**Do not enable this bundle in production.**

UI Designer is for local theme authoring — not runtime theme SSOT.

- The Flex recipe does **not** register the bundle for `prod`.
- Do not add `Symfinity\UiDesigner\UiDesignerBundle` to `prod` in `config/bundles.php`.
- `/ui-designer` returns **404** in `prod` when the bundle is absent (expected).

## Manual installation

When Flex is unavailable:

1. `composer require --dev symfinity/ui-designer symfinity/ui-kernel:^0.3.1`
2. Register `Symfinity\UiDesigner\UiDesignerBundle` for **`dev` and `test`** only in `config/bundles.php`
3. Copy `config/packages/symfinity_ui_designer.yaml` from the package into your project

## Verify installation

```bash
php bin/console debug:config symfinity_ui_designer
php bin/console list ui-designer
```

See [Verification](verification.md) for clean-app smoke steps.

## Next steps

[Quick start](quickstart.md).
