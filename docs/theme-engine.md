# ADAM UI Night Theme engine

ADAM UI stores Night presets in the `adam_ui_themes` WordPress option. **ADAM Night** is the safe built-in default and cannot be deleted. Administrators can edit Night overrides under **ADAM UI → Theme Editor**, duplicate a preset, rename or delete custom Night presets, and import/export complete JSON packages. Legacy Light theme data remains stored for backwards compatibility but is no longer exposed or emitted.

The editor is generated from eight reusable component definitions: Header, Sections, Feature Sections, Hero, Cards, Buttons, Forms, and Footer. It no longer exposes a page-oriented colour matrix. Each component offers only its meaningful background and a small visual-style choice, while foreground colours are derived automatically.

Light, Night, and System remain preference modes:

- Light removes the Night override class and renders the normal Blocksy website.
- Night adds `adam-theme-dark` and activates saved Night overrides.
- System follows `prefers-color-scheme` and resolves to either state.

ADAM UI does not generate a Light palette or a Light stylesheet. `variables.css` provides only structural tokens and a neutral bridge to Blocksy/browser values. `ui.css` is scoped exclusively to Night mode.

Every component panel includes a compact live example. Changes update the preview immediately. Colour fields support short and long HEX, RGB/RGBA, HSL/HSLA, named colours, and `transparent`; values are validated again before storage.

Visual styles are high-impact presets rather than collections of CSS controls. For example, Cards provides Flat, Elevated, Glass, and Minimal; Buttons provides Filled, Outline, and Soft; Header provides Solid, Transparent, and Elevated. A preset resolves into structural design tokens for borders, depth, spacing, opacity, and contrast, so all consumers change consistently.

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

## Registering components

Future ADAM plugins can add component types without editing the Theme Editor:

```php
adam_ui_register_theme_component(
  'event-card',
  array(
    'label'       => 'Event Card',
    'description' => 'Cards used by ADAM Comunidade events.',
    'controls'    => array(
      array(
        'label'  => 'Background',
        'fields' => array(
          array(
            'token'   => 'adam-event-card-bg',
            'label'   => 'Background',
            'type'    => 'color',
            'default' => '#1a2019',
          ),
        ),
      ),
    ),
    'styles'   => array(),
    'tokens'   => array(
      'adam-event-card-text' => array(
        'type'    => 'color',
        'default' => '#f2f4ee',
      ),
    ),
    'contrast' => array(
      'adam-event-card-bg' => array( 'adam-event-card-text' ),
    ),
    'preview'  => '<article class="adam-event-card-preview"><h3>Event</h3><p>Details</p></article>',
  )
);
```

The registered definition automatically becomes an editor tab, participates in persistence and automatic contrast, and is included in the live-preview schema. Plugins may alternatively extend the complete registry through the `adam_ui_theme_components` filter.

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
