<div align="center">

# UI Designer

### Dev-only DTCG theme designer for UI Kernel

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)](composer.json)
[![Symfony](https://img.shields.io/badge/Symfony-7.4+-343434?style=flat&logo=symfony&logoColor=white)](composer.json)
<br/>
[![CI](https://github.com/symfinity/ui-designer/actions/workflows/ci.yml/badge.svg)](https://github.com/symfinity/ui-designer/actions/workflows/ci.yml)
<br/>
[![Release](https://img.shields.io/packagist/v/symfinity/ui-designer.svg?style=flat&logo=packagist&logoColor=white)](https://packagist.org/packages/symfinity/ui-designer)
[![Downloads](https://img.shields.io/packagist/dt/symfinity/ui-designer.svg?style=flat&logo=packagist&logoColor=white)](https://packagist.org/packages/symfinity/ui-designer)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](LICENSE)

</div>

> [!NOTE]
> **Read-only mirror.**
> See [CONTRIBUTING.md](CONTRIBUTING.md) for how to propose changes.

## Features

- **DTCG-native authoring** — W3C design tokens + `theme.meta.yaml`; no legacy YAML pack format
- **Visual studio** — `/ui-designer` with ref picker, contrast panel, undo, and snapshots
- **Import engines** — shadcn registry, W3C, Tokens Studio, Figma variables
- **Session preview** — kernel gallery deep link without mutating the theme registry
- **Export packs** — validated DTCG trees under `config/themes/{lineage}/`
- **Dev-only forever** — Flex recipe registers bundle for **dev** and **test** only

## Prerequisites

Add the [symfinity/recipes](https://github.com/symfinity/recipes) Flex endpoint to your project's `composer.json` (see [recipes README](https://github.com/symfinity/recipes/blob/main/README.md)).

Requires **symfinity/ui-kernel ^0.3.1**.

## Installation

```bash
composer require --dev symfinity/ui-designer
```

The Flex recipe applies OOTB — bundle + `config/packages/symfinity_ui_designer.yaml`. **Do not enable in production.**

## Quick Start

With `APP_ENV=dev`, open **`/ui-designer`**. See [Quickstart](docs/quickstart.md).

## Documentation

- **[Quickstart](docs/quickstart.md)** — first authoring loop
- **[Installation](docs/installation.md)** — Flex OOTB, prod guardrails
- **[Configuration](docs/configuration.md)** — export root, undo, snapshots
- **[Commands](docs/commands.md)** — `ui-designer:*` CLI
- **[Upgrade](docs/upgrade.md)** — 0.1.0 release notes
- **[Roadmap](ROADMAP.md)** — 0.1.x–1.0.x milestones
- **[Verification](docs/verification.md)** — clean-app smoke (P0 profile)

## Requirements

- PHP 8.2 or higher
- Symfony 7.4 or 8.x
- symfinity/ui-kernel ^0.3.1

## Support

- [GitHub Issues](https://github.com/symfinity/ui-designer/issues)
- [Security](.github/SECURITY.md)
- [Contributing](CONTRIBUTING.md)

## License

[MIT](LICENSE)
