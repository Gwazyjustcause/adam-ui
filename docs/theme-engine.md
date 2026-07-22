# ADAM UI theme engine

ADAM UI stores theme definitions in the `adam_ui_themes` WordPress option. The bundled **ADAM Light**, **ADAM Night**, and **High Contrast** presets provide safe defaults; bundled presets cannot be deleted. Administrators can edit tokens under **ADAM UI → Theme Editor**, duplicate a preset, rename or delete custom themes, and import/export complete JSON theme packages.

The editor is driven by one PHP schema organised around component families: Header, Footer, Hero, Sections, Cards, Buttons, Forms, Tables, and Notifications. Administrators work with familiar interface parts while ADAM UI maps every field to a stable CSS custom property. Saving a theme validates every colour and component value, then the Theme Manager emits scoped CSS variables for `.adam-theme-light` and `.adam-theme-dark`. The existing Light, Night, and System selector remains the runtime mode controller.

Every component panel includes a compact live example. A change is applied immediately to that panel and to the complete page preview without saving or refreshing. The native colour control is paired with a free-form CSS colour field so it supports short and long HEX, RGB/RGBA, HSL/HSLA, named colours, and `transparent`; the server validates the value again before storing it.

## PHP API

```php
$all_light_tokens = adam_tokens( 'light' );
$surface = adam_token( 'adam-surface', '#fff', 'light' );
$repository = adam_ui_themes();
```

Ecosystem plugins should consume component variables such as `--adam-header-bg`, `--adam-footer-bg`, `--adam-card-bg`, `--adam-btn-primary-bg`, `--adam-form-border`, and `--adam-table-row-bg`. Stable semantic aliases such as `--adam-bg`, `--adam-surface`, `--adam-text`, `--adam-primary`, and `--adam-border` remain available for incremental migration. Plugins must not ship an independent colour palette when ADAM UI is active.

## Component token examples

```css
.adam-card {
  background: var(--adam-card-bg);
  color: var(--adam-card-text);
  border-color: var(--adam-card-border);
}

.adam-button-primary {
  background: var(--adam-btn-primary-bg);
  color: var(--adam-btn-primary-text);
  border-color: var(--adam-btn-primary-border);
}
```

Reusable section modifiers map to the five editor roles: `adam-section--base` (Standard), `--muted`/`--soft` (Alternate), `--pale`/`--feature` (Feature Strip), `--accent` (CTA), and `--deep` (Image Overlay).

## JavaScript API

```js
ADAMUI.getToken('adam-surface');
ADAMUI.getTokens();
```

The existing `setTheme()`, `getTheme()`, `getResolvedTheme()`, and `adam:themeChanged` contract is unchanged.

## Theme Inspector

Administrators can opt in under **ADAM UI → Settings**. A small “Inspect ADAM UI” control then identifies the inferred surface, background, text, border, and radius token beneath the pointer. It is not loaded for ordinary visitors or when disabled.

## JSON format

Exports contain the format name, schema version, metadata, and every token—including colours, typography, spacing, radii, shadows, and component dimensions. Imports are validated against the installed schema and always create a custom theme.
