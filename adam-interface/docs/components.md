# ADAM Interface component library

ADAM Interface is the visual source of truth for ADAM-owned frontend and WordPress administration screens. It does not style native WordPress, WooCommerce, Forminator, or other third-party admin pages. An ADAM plugin opts its own admin hook suffix into the theme with `adam_interface_enable_admin_theme()`.

All components inherit Light, Dark, and System mode through the `adam-theme-light` or `adam-theme-dark` body class. Component CSS must use `--adam-*` tokens and must not contain its own colour palette.

## Adoption contract

1. Detect ADAM Interface with `function_exists( 'adam_interface_get_theme_manager' )`.
2. On public pages, call the Theme Manager's `enqueue_assets()` method if the global frontend assets are not already present.
3. On `admin_enqueue_scripts`, call `adam_interface_enable_admin_theme()` only after confirming the hook suffix belongs to the plugin.
4. Keep the plugin's existing stylesheet as a standalone fallback, using declarations such as `var(--adam-surface, #fff)`. When Interface is active, the token wins; without it, the old appearance remains usable.
5. Keep plugin CSS limited to layout and domain-specific structure. Use the component classes below for visual presentation.

## Admin page structure

```html
<div class="wrap adam-admin-page">
  <header class="adam-page-header">
    <div class="adam-page-header__content">
      <h1 class="adam-page-title">Page title</h1>
      <p class="adam-page-description">Short description.</p>
    </div>
    <div class="adam-page-actions">
      <a class="adam-button adam-button-primary" href="#">Add item</a>
    </div>
  </header>
  <div class="adam-stat-grid">…</div>
  <div class="adam-admin-layout">
    <main>…</main>
    <aside class="adam-card">…</aside>
  </div>
</div>
```

Use `adam-admin-layout--single` when no sidebar is present. The layout collapses automatically on tablets and phones.

## Components and classes

| Component | Classes |
| --- | --- |
| Card | `adam-card`, `adam-card-header`, `adam-card-body`, `adam-card-footer` |
| Button | `adam-button` with `adam-button-primary`, `-secondary`, `-success`, `-warning`, or `-danger` |
| Form | `adam-field`, `adam-field__label`, `adam-field__help`, `adam-input`, `adam-select`, `adam-textarea` |
| Table | `adam-table`; wrap in `adam-table-responsive` when columns may overflow |
| Tabs | `adam-tabs`, `adam-tabs__list`, `adam-tabs__tab`, `adam-tabs__panel`; tabs require correct tab ARIA roles and relationships |
| Modal | native `<dialog class="adam-modal">` with `adam-modal__header`, `__body`, and `__footer` |
| Notice | `adam-alert` with `adam-alert-info`, `-success`, `-warning`, or `-error` |
| Badge | `adam-badge` with the Phase 4 semantic modifiers `adam-badge--success`, `--warning`, or `--danger` |
| Breadcrumb | `<nav class="adam-breadcrumbs" aria-label="Breadcrumb"><ol>…</ol></nav>` |
| Empty state | `adam-empty-state` and its `__icon`, `__title`, `__description`, and `__actions` parts |
| Loading | `adam-loading`, `adam-loading__spinner`; always include an `adam-sr-only` status label |
| Pagination | `<nav class="adam-pagination" aria-label="Pagination">`; mark the current item with `aria-current="page"` |
| Toolbar | `adam-toolbar`, `adam-toolbar__group` |
| Search | `<label class="adam-search">` containing an accessible label and `adam-input` |
| Dropdown | `adam-dropdown`, an `aria-controls` trigger with `data-adam-dropdown-toggle`, and a hidden `adam-dropdown__menu` |
| Confirmation | `adam-confirmation adam-modal`, preferably through the PHP or JavaScript helper |
| Statistic | `adam-stat-grid`, `adam-stat-card`, and the documented `adam-stat-card__*` parts |
| Section header | `adam-section-header`, `__content`, `__title`, `__description`, and `__actions` |
| Icon | `adam-icon`; SVG markup should use `currentColor` so it follows the theme |

Phase 4 double-hyphen button and notice modifiers remain supported for incremental migration.

## Design tokens

- Colour: `--adam-bg`, `--adam-surface`, `--adam-surface-2`, `--adam-text`, semantic status tokens, borders, links, focus, overlays and shadows.
- Spacing: `--adam-space-1` through `--adam-space-8`.
- Typography: `--adam-font-family`, `--adam-font-size-xs` through `--adam-font-size-2xl`, weight and line-height tokens.
- Shape: `--adam-radius-sm`, `--adam-radius`, `--adam-radius-lg`, `--adam-border-width`.
- Layout: content/sidebar widths and documented breakpoint values `--adam-breakpoint-sm` through `--adam-breakpoint-xl`. CSS custom properties cannot be used in media-query conditions, so the component stylesheet owns the matching canonical queries.
- Layers: `--adam-z-base`, `--adam-z-dropdown`, `--adam-z-sticky`, `--adam-z-overlay`, `--adam-z-modal`, `--adam-z-toast`.
- Motion: fast/default/slow duration and timing tokens. Shared components respect `prefers-reduced-motion`.
- Icons: `--adam-icon-size-sm`, `--adam-icon-size`, `--adam-icon-size-lg`, `--adam-icon-size-xl`.

## PHP helpers

Helpers return escaped HTML and do not echo it:

```php
echo adam_interface_notice( 'Saved.', 'success' );
echo adam_interface_button( 'Create', admin_url( 'admin.php?page=example' ) );
echo adam_interface_stat_card( 'Members', '128', array( 'trend' => '+4 this month' ) );
echo adam_interface_empty_state( 'No results', 'Try changing the filters.' );
echo adam_interface_loading_indicator( 'Loading members' );
echo adam_interface_confirmation_dialog( 'Delete this item?' );
```

For advanced use, retrieve the renderer with `adam_interface_get_components()`. Text is escaped by default. Notice HTML and empty-state action HTML are passed through WordPress's safe post HTML allowlist.

## JavaScript API

The existing theme API remains available:

```js
ADAMInterface.setTheme('dark');
ADAMInterface.getTheme();
ADAMInterface.getResolvedTheme();
```

Component helpers:

```js
const accepted = await ADAMInterface.confirm({
  title: 'Delete member?',
  message: 'This action cannot be undone.',
  confirmLabel: 'Delete'
});

ADAMInterface.setLoading(button, true, 'Saving');
ADAMInterface.setLoading(button, false);
ADAMInterface.components.bindDropdowns(container);
```

`confirm()` restores focus when it closes and supports Escape. Dropdown triggers require `aria-controls` and receive synchronized `aria-expanded` state. Theme changes continue to dispatch `adam:themeChanged` with `mode`, `theme`, and `resolvedTheme` details.

## Accessibility requirements

- Supply visible labels or an accessible name for every control.
- Use native controls where possible, including `<button>`, `<select>`, and `<dialog>`.
- Keep the shared focus indicator; do not remove outlines.
- Use `role="status"` for informational updates and `role="alert"` for urgent errors.
- Mark decorative icons `aria-hidden="true"`; label meaningful icon-only buttons.
- Preserve logical DOM order when responsive CSS changes visual layout.
