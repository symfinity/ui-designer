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

### Org Packagist lab (symfinity-root)

Simulates an external consumer — **no org path repo**, live **flex/main** recipe, Packagist dist:

```bash
make dogfood-new SLUG=ui-designer-packagist-lab VERSION='7.4.*' FORCE=1
make dogfood-serve SLUG=ui-designer-packagist-lab
```

Open **`/ui-designer`**. Requires network (Packagist + symfinity/recipes).

For in-tree authoring smoke (path repo + full UI stack), use **`SLUG=ui-lab`** instead.

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
