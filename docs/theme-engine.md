# ADAM UI Night Theme engine

ADAM UI stores Night presets in the `adam_ui_themes` WordPress option. **ADAM Night** is the safe built-in default and cannot be deleted. Administrators can edit Night overrides under **ADAM UI → Theme Editor**, duplicate a preset, rename or delete custom Night presets, and import/export complete JSON packages. Legacy Light theme data remains stored for backwards compatibility but is no longer exposed or emitted.

The editor exposes six approachable component areas: Header, Sections, Cards, Buttons, Forms, and Footer. Advanced retains detailed Night surface, border, hover, and state controls. Foreground colours are derived automatically from component backgrounds, so administrators do not maintain separate text colours.

Light, Night, and System remain preference modes:

- Light removes the Night override class and renders the normal Blocksy website.
- Night adds `adam-theme-dark` and activates saved Night overrides.
- System follows `prefers-color-scheme` and resolves to either state.

ADAM UI does not generate a Light palette or a Light stylesheet. `variables.css` provides only structural tokens and a neutral bridge to Blocksy/browser values. `ui.css` is scoped exclusively to Night mode.

Every component panel includes a compact live example. Changes update the preview immediately. Colour fields support short and long HEX, RGB/RGBA, HSL/HSLA, named colours, and `transparent`; values are validated again before storage.

## PHP API

```php
$night_tokens = adam_tokens();
$surface = adam_token( 'adam-card-bg', '#1a2019' );
$repository = adam_ui_themes();
```

Ecosystem plugins should consume component variables such as `--adam-header-bg`, `--adam-footer-bg`, `--adam-card-bg`, `--adam-btn-primary-bg`, `--adam-form-border`, and `--adam-table-row-bg`. In Light mode the interoperability bridge resolves through Blocksy or browser values; in Night mode saved ADAM overrides replace them. Plugins must not ship an independent Night palette.

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

Reusable section modifiers map to Standard, Alternate, Feature Strip, CTA, and Image Overlay roles. Their foreground tokens are calculated from the selected Night backgrounds.

## JavaScript API

```js
ADAMUI.setTheme('light'); // removes Night overrides
ADAMUI.setTheme('dark');  // enables Night overrides
ADAMUI.setTheme('system');
ADAMUI.getTheme();
ADAMUI.getResolvedTheme();
```

The `adam:themeChanged` event contract is unchanged.

## JSON format

Exports contain the format name, schema version, metadata, and every Night token. Imports are validated against the installed schema and always become custom Night presets.
