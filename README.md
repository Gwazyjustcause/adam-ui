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

See [Component library](docs/components.md), [Plugin integration](docs/plugin-integration.md), [Production API](docs/production-api.md), and [Theme engine](docs/theme-engine.md) for the supported tokens, visual editor, presets, import/export, inspector, components, helpers, asset loading, events, and compatibility contract.

The Night Theme Editor follows a three-level inheritance model. **Global Theme** defines the shared surfaces, typography, accent, border, shadow, and radius; **Components** contains only the backgrounds and structural choices that genuinely differ; **Advanced** stores optional fine-grained overrides separately. Header, Sections, Feature Sections, Hero, Cards, Buttons, Forms, and Footer ship as built-ins, and future ADAM plugins can add editor-ready component types with `adam_ui_register_theme_component()`.

## Theme Switcher

The Light / Night / System control is a reusable ADAM UI component. Administrators can select its placement and presentation under **ADAM UI → Settings → Theme Switcher**:

- a standard WordPress widget for any registered widget area;
- the dynamic **ADAM UI / Theme Switcher** Gutenberg block;
- the `[adam_theme_switcher]` shortcode;
- a floating control in any screen corner.

Existing installations keep their current footer control as a legacy default. Saving any new placement disables that automatic footer injection. Templates and ADAM-owned plugins can render the same shared implementation with `adam_ui_theme_switcher()`.
