# Verification

**Integration profile:** P0 — dev/test authoring tool (Flex OOTB).

## Local commands

From the package directory:

```bash
composer test
composer phpstan
```

## Clean-app smoke

On a fresh Symfony **7.4+** project with `APP_ENV=dev` and symfinity/recipes Flex endpoint configured:

```bash
composer require --dev symfinity/ui-kernel:^0.3.1 symfinity/ui-designer:^0.1
php bin/console debug:config symfinity_ui_designer
php bin/console list ui-designer
```

Start the dev server and open **`/ui-designer`** — expect HTTP **200** and the studio shell (nav: Import · Studio · Export).

With **symfinity/ux-blocks-core** installed, studio specimens render with live theme CSS.

## Prod gate

With `APP_ENV=prod` and bundle **not** registered for prod:

```bash
curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8000/ui-designer
```

Expect **404**.

## See also

- [Installation](installation.md)
- [Quickstart](quickstart.md)
- [CHANGELOG](../CHANGELOG.md)
