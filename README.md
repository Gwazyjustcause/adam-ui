# ADAM UI

ADAM UI is the shared UI framework and Night Theme override system for the ADAM ecosystem. Light mode remains the website's native Blocksy appearance.

## Installation

Install this directory as `wp-content/plugins/adam-ui/`. WordPress loads the plugin from `adam-ui/adam-ui.php`; activate **ADAM UI** from the Plugins page.

The plugin does not style native WordPress or third-party administration pages. ADAM-owned plugins opt into its component library and asset registry, and continue to provide standalone fallbacks when ADAM UI is unavailable.

## Integration

- PHP namespace/prefix: `ADAM_UI`
- PHP service accessor: `adam_ui()`
- JavaScript namespace: `window.ADAMUI`
- Text domain: `adam-ui`
- Asset handles: `adam-ui-*`
- Theme event: `adam:themeChanged`

See [Component library](docs/components.md), [Production API](docs/production-api.md), and [Theme engine](docs/theme-engine.md) for the supported tokens, visual editor, presets, import/export, inspector, components, helpers, asset loading, events, and compatibility contract.
