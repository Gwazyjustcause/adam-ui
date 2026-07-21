# ADAM UI

ADAM UI is the shared UI framework, theme manager, and design system for the ADAM ecosystem.

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

See [Component library](docs/components.md) and [Production API](docs/production-api.md) for the supported tokens, components, helpers, asset loading, plugin registration, events, and compatibility contract.
