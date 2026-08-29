# Configuration

Configuration root: **`symfinity_ui_designer`**.

Default file: `config/packages/symfinity_ui_designer.yaml` (copied by Flex recipe).

## Options

| Key | Default | Description |
|-----|---------|-------------|
| `export_root` | `%kernel.project_dir%/var/ui-designer` | Draft snapshots and export scratch area |
| `snapshots.max` | `10` | Named snapshots retained per draft |
| `undo.max_depth` | `20` | Session undo stack depth |
| `strict_export` | `false` | Block export when contrast gate fails |

## Example

```yaml
when@dev:
    symfinity_ui_designer:
        export_root: '%kernel.project_dir%/var/ui-designer'
        snapshots:
            max: 10
        undo:
            max_depth: 20
        strict_export: true
```

## Environment scope

Package defaults use `when@dev` and `when@test` — author routes and config are unavailable in **prod**.

## UI Kernel dependency

Designer requires **symfinity/ui-kernel ^0.3.1** for DTCG resolution and theme inheritance. Configure UI Kernel separately under `symfinity_ui_kernel`.

## See also

- [Installation](installation.md)
- [Commands](commands.md)
