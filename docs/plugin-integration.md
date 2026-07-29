# ADAM plugin integration contract

ADAM UI is the visual dependency for ADAM-owned interfaces. Integrations remain optional: every plugin must keep its workflows and data functional when ADAM UI is unavailable, but it must not ship a competing Night palette.

## Register the plugin

Register compatibility metadata as soon as ADAM UI is available:

```php
if ( function_exists( 'adam_ui_register_plugin' ) ) {
	adam_ui_register_plugin(
		'adam-community',
		'ADAM Comunidade',
		array(
			'version'     => ADAM_COMMUNITY_VERSION,
			'requires_ui' => '5.0.0',
			'plugin_file' => plugin_basename( ADAM_COMMUNITY_FILE ),
		)
	);
}
```

## Automatic component discovery

Attach one callback during normal plugin bootstrap. ADAM UI runs it on `init` after active plugins have loaded:

```php
add_action(
	'adam_ui_register_components',
	static function ( $registry ) {
		$registry->register_plugin_component(
			'adam-community',
			'team-card',
			array(
				'name'             => 'Team Card',
				'category'         => 'Community Cards',
				'owner_name'       => 'ADAM Comunidade',
				'description'      => 'Team summaries used across community views.',
				'preview_template' => '<article class="adam-card"><h3>Team Mondego</h3><p>12 members</p></article>',
				'default_styles'   => array(
					'adam-team-card-gap' => '1rem',
				),
				'presets'          => array(
					'standard' => array(
						'label'  => 'Standard',
						'tokens' => array( 'adam-team-card-gap' => '1rem' ),
					),
				),
				'uses'             => array( 'card', 'button', 'status-badge' ),
				'controls'         => array(),
				'tokens'           => array(),
				'intelligence'     => array(),
				'assets'           => array(
					'style_handle'  => 'adam-community-team-card',
					'script_handle' => '',
				),
			)
		);
	}
);
```

The canonical procedural alternative is:

```php
adam_ui_register_component( 'adam-community', 'team-card', $definition );
```

Each definition supplies:

- `name`: administrator-facing component name;
- plugin-local identifier: the second registration argument;
- `category`: descriptive component category;
- `preview_template`: safe miniature editor markup or a callback;
- `default_styles`: base design-token values;
- `presets`: supported appearance presets and their token maps;
- `uses`: shared ADAM UI component dependencies;
- `controls`, `tokens`, and `intelligence`: Theme Editor and automatic colour contracts;
- `assets`: optional handles already registered by the owning plugin.

ADAM UI creates the global identifier `adam-community--team-card`. Another plugin cannot replace it, and ADAM Comunidade cannot replace another plugin's component.

## Markup and CSS isolation

Use the generated root classes:

```php
$classes = adam_ui_component_class( 'adam-community', 'team-card', 'community-team-card' );
echo '<article class="' . esc_attr( $classes ) . '">...</article>';
```

This returns `adam-ui-component adam-component--adam-community-team-card community-team-card`. Keep functional JavaScript, templates, permissions, URLs, and data inside the owning plugin. ADAM UI controls only shared presentation.

Plugin CSS may define layout and domain structure, but must consume shared variables:

```css
.adam-component--adam-community-team-card {
	display: grid;
	gap: var(--adam-team-card-gap, var(--adam-space-4));
	padding: var(--adam-card-padding);
	color: var(--adam-card-text);
	background: var(--adam-card-bg);
	border: var(--adam-card-border-width) solid var(--adam-card-border);
	border-radius: var(--adam-card-radius);
	box-shadow: var(--adam-card-shadow);
	transition: background-color var(--adam-duration), border-color var(--adam-duration);
}
```

Do not hardcode colours, fonts, spacing, radii, shadows, animation durations, focus states, or hover palettes. Do not target another plugin's namespaced root.

## Shared component library

Request existing components instead of recreating them:

```php
adam_ui_enqueue_components(
	array( 'card', 'button', 'table', 'modal', 'tabs', 'search-bar', 'status-badge', 'pagination' )
);
```

Available families include cards, buttons, forms, tables, notices, modals, side panels, tabs, search bars, status badges, pagination, breadcrumbs, empty states, loading indicators, dropdowns, confirmation dialogs, statistic cards, toolbars, and section headers.

A plugin-owned component's `uses` dependencies are requested automatically when its namespaced component is enqueued:

```php
adam_ui()->enqueue_component( 'adam-community--team-card' );
```

WordPress handle dependencies and the ADAM UI registry prevent duplicate stylesheet and script loads.

## Fallback requirement

Guard every integration call with `function_exists()` or `class_exists()`. When ADAM UI is missing, the plugin must retain usable structural fallback CSS. That fallback may provide neutral browser/system values, but it must not contain an independent branded or Night colour palette.

## Development checklist

- Register every genuinely custom visual component.
- Reuse a core component when its semantics already match.
- Declare shared dependencies through `uses`.
- Use the generated namespaced root class.
- Keep plugin CSS structural and token-driven.
- Never define a competing colour palette.
- Never read or modify another plugin's component registration.
- Keep business logic and data ownership inside the plugin.
- Test Light mode without ADAM overrides and Night mode with generated tokens.

## ADAM Comunidade Night Theme audit

ADAM UI's compatibility bridge recognises ADAM-owned semantic roots without
depending on an ADAM Comunidade page, route, or namespace. A root class whose
component word identifies a card, empty state, statistic, hero, filter,
toolbar, search bar, or form receives `data-adam-night-component` at runtime.
Its background, typography, borders, controls, buttons, and states then resolve
through the shared component tokens.

The public templates audited for this contract include the Community landing
page, Teams, Fields, Partners, Institutions, News, Events, directory archives
and cards, single entity views, submission forms, and manager portals. Their
hero banners, filter bars, search controls, selects, buttons, empty states,
cards, and statistics fit the shared semantic contract and do not need
page-specific Night CSS.

Media remains intentionally outside this bridge. Photographs, card cover
images, hero images, map tiles, videos, canvases, and SVG artwork are never
recoloured. A future plugin component that cannot express its surface using a
semantic component root must register that component with ADAM UI or add an
explicit `data-adam-night-component` contract in its own markup; ADAM UI must
not guess from a page URL or add a plugin-specific selector.
