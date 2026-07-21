# ADAM Interface production API

ADAM Interface 0.6 is the permanent visual service layer for ADAM-owned plugins. It supplies theme state, tokens, shared components, asset coordination, compatibility reporting, events, and diagnostics. It never opts native WordPress or third-party screens into ADAM styling.

## Runtime requirements and compatibility

An integration remains optional and must never call Interface APIs before checking that they exist:

```php
if ( function_exists( 'adam_interface_register_plugin' ) ) {
    adam_interface_register_plugin(
        'adam-community',
        'ADAM Comunidade',
        array(
            'version'            => ADAM_COMMUNITY_VERSION,
            'requires_interface' => '0.6.0',
            'components'         => array( 'card', 'button', 'forms', 'notice' ),
			'plugin_file'        => plugin_basename( ADAM_COMMUNITY_FILE ),
        )
    );
}
```

`ADAM_Interface::register_plugin()` is the equivalent static API. ADAM Interface also discovers active WordPress plugins whose header name starts with `ADAM`; explicit registration is preferred because it supplies minimum-version and component metadata.

An incompatible registration produces a non-fatal WordPress notice and a diagnostics entry. It never stops the registering plugin.

## Theme priority and settings

Administrators configure the framework at **ADAM Interface → Settings**:

1. A valid logged-in user-meta preference has highest priority.
2. If enabled, System mode follows `prefers-color-scheme`.
3. The website Light or Dark default is the final fallback.

Anonymous choices use `localStorage` only when visitor switching is enabled. Logged-in choices use the `adam_interface_theme` user-meta key and are saved asynchronously through an authenticated, nonce-protected WordPress endpoint. Disabling System mode removes it from both the selector and accepted persistence values.

The extension filters are:

- `adam_interface_default_theme_mode`
- `adam_interface_system_fallback`
- `adam_interface_theme_mode`
- `adam_interface_theme_storage_config`

Future palette, accessibility, branding, or seasonal modules should extend these contracts rather than replace the Theme Manager.

## Stable PHP helpers

```php
adam_interface();                  // Main service container.
adam_theme();                      // Theme Manager.
adam_asset();                      // Asset registry.
adam_asset( 'components' );        // Registered public asset URL.
adam_notice( 'Saved', 'success' ); // Escaped component markup.
adam_button( 'Create', $url );
adam_card( 'Content', array( 'title' => 'Summary' ) );
```

The longer Phase 1–5 helpers remain supported, including `adam_interface_notice()`, `adam_interface_button()`, `adam_interface_stat_card()`, `adam_interface_empty_state()`, and `adam_interface_loading_indicator()`.

## Request-driven asset loading

Core variables, the two token-only themes, base integration CSS, and the theme controller form the minimal global foundation. Component CSS and interaction JavaScript are loaded only after a component request:

```php
if ( function_exists( 'adam_interface' ) ) {
    adam_interface()->enqueue_component( 'table' );
    adam_interface()->enqueue_component( 'modal' );
    adam_interface()->enqueue_component( 'forms' );
}
```

Request components during the appropriate `wp_enqueue_scripts`, `login_enqueue_scripts`, or scoped `admin_enqueue_scripts` callback. WordPress handle dependencies deduplicate every request. Non-interactive components request no component JavaScript. The old Theme Manager `enqueue_assets()` method intentionally loads the complete bundle for backward compatibility; new integrations should use `enqueue_core_assets()` plus explicit component requests.

Built-in component identifiers:

`admin-layout`, `badge`, `breadcrumbs`, `button`, `card`, `confirmation`, `dropdown`, `empty-state`, `forms`, `loading`, `modal`, `notice`, `pagination`, `search`, `section-header`, `stat-card`, `table`, `tabs`, and `toolbar`.

Future visual extensions may register a central component:

```php
$assets = adam_asset();
$assets->register_component(
    'community-feed',
    array(
        'style_handle'  => 'adam-community-feed',
        'script_handle' => 'adam-community-feed',
    )
);
```

The extension owns handle registration but Interface owns request tracking and diagnostics.

## ADAM-owned WordPress admin screens

An integration must verify its hook suffix, page slug, post type, or taxonomy before opting in:

```php
if ( $is_adam_owned_screen && function_exists( 'adam_interface_enable_admin_theme' ) ) {
    adam_interface_enable_admin_theme();
    adam_interface()->enqueue_component( 'admin-layout' );
}
```

The base stylesheet is scoped to `#wpbody-content` in WordPress admin. The toolbar, admin menu, native screens, WooCommerce, Forminator, and third-party plugins retain their own presentation.

## JavaScript namespace and events

`window.ADAMInterface` is the only public runtime namespace. WordPress bootstrap configuration globals are consumed and removed during initialization.

```js
ADAMInterface.setTheme('dark');
ADAMInterface.getTheme();
ADAMInterface.getResolvedTheme();
ADAMInterface.on('adam:themeChanged', listener);
ADAMInterface.off('adam:themeChanged', listener);
ADAMInterface.emit('adam:customEvent', { source: 'community' });

const accepted = await ADAMInterface.confirm({ message: 'Continue?' });
ADAMInterface.setLoading(button, true, 'Saving');
```

Document events:

- `adam:themeChanged` — `{ mode, theme, resolvedTheme }`
- `adam:componentLoaded` — `{ component }`
- `adam:modalOpened` — `{ dialog }`
- `adam:modalClosed` — `{ dialog, confirmed }`
- `adam_interface_plugin_registered` — PHP action containing registration metadata

Events allow plugins to cooperate without importing or polling one another.

## Diagnostics

**ADAM Interface → Diagnostics** reports:

- configured and resolved theme;
- user, system, or website-default source;
- registered ADAM plugins and their versions;
- requested component families;
- CSS and JavaScript framework version;
- compatibility warnings.

Diagnostics are read-only and require `manage_options`.

## File boundaries

- `variables.css` — theme-independent design tokens.
- `light.css` and `dark.css` — color-dependent tokens only.
- `interface.css` — low-specificity theme integration.
- `utilities.css` — lightweight layout utilities.
- `components.css` — the reusable component library.
- `theme-switcher.css` — the optional public selector.
- `admin.css` — Interface-owned settings and diagnostics only.

Plugin stylesheets should contain domain layout and standalone token fallbacks, never an active competing palette.
