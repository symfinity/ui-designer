# Upgrade

## 0.1.1

Theme Generator drawer and app-wide preview host.

```bash
composer update symfinity/ui-designer
```

### Preview host wiring

Add to your dev app `templates/base.html.twig` (inside `<body>`):

```twig
{% include '@UiDesigner/preview/_host.html.twig' %}
```

Enable fragment rendering in `config/packages/framework.yaml`:

```yaml
framework:
    fragments: true
```

Use `ui_designer_preview_url('your_route_name')` on internal links so preview tokens survive navigation.

From `/ui-designer`, choose **Start customizing** to open the drawer over your app. Token edits sync to the host page without a full reload.

## 0.1.0

First public release. Install with:

```bash
composer require --dev symfinity/ui-designer:^0.1
```

Requires **symfinity/ui-kernel ^0.3.1**.

Author themes in DTCG (`theme.meta.yaml` + variant files) or import from registry themes / W3C / Tokens Studio / Figma token files. There is no import path for legacy preset/tone YAML pack formats.

## See also

- [Installation](installation.md)
- [CHANGELOG](../CHANGELOG.md)
