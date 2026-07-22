# ADAM UI theme engine

ADAM UI stores theme definitions in the `adam_ui_themes` WordPress option. The bundled **ADAM Light**, **ADAM Night**, and **High Contrast** presets provide safe defaults; bundled presets cannot be deleted. Administrators can edit tokens under **ADAM UI → Theme Editor**, duplicate a preset, rename or delete custom themes, and import/export complete JSON theme packages.

The editor is driven by one PHP schema. Saving a theme validates every colour and component value, then the Theme Manager emits scoped CSS variables for `.adam-theme-light` and `.adam-theme-dark`. The existing Light, Night, and System selector remains the runtime mode controller.

## PHP API

```php
$all_light_tokens = adam_tokens( 'light' );
$surface = adam_token( 'adam-surface', '#fff', 'light' );
$repository = adam_ui_themes();
```

Ecosystem plugins should consume variables such as `--adam-bg`, `--adam-surface`, `--adam-surface-elevated`, `--adam-text`, `--adam-text-secondary`, `--adam-text-muted`, `--adam-primary`, `--adam-border`, `--adam-divider`, `--adam-radius`, and `--adam-shadow-md`. They must not ship an independent colour palette when ADAM UI is active.

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
