# Etch WP Menus - Build Guide

Comprehensive architecture reference for developers and AI agents working on this plugin.

**Version:** 2.0.0
**Last Updated:** February 6, 2026

---

## Table of Contents

1. [Plugin Architecture](#plugin-architecture)
2. [ETCH Block Tree Format](#etch-block-tree-format)
3. [ETCH Style Object Format](#etch-style-object-format)
4. [ETCH Template Syntax](#etch-template-syntax)
5. [Key Discoveries & Gotchas](#key-discoveries--gotchas)
6. [Dual CSS Architecture](#dual-css-architecture)
7. [WordPress Integration](#wordpress-integration)
8. [Settings & Their Effects](#settings--their-effects)
9. [File-by-File Breakdown](#file-by-file-breakdown)
10. [CSS Architecture](#css-architecture)
11. [JavaScript Architecture](#javascript-architecture)
12. [Known Limitations](#known-limitations)

---

## Plugin Architecture

### Overview

This WordPress plugin generates navigation code in four formats for the ETCH theme builder by Digital Gravy. Users configure settings in the admin UI, click "Generate", and receive HTML, CSS, JS, and ETCH JSON output.

### Request Flow

```
Admin UI (templates/admin-page.php)
  → jQuery AJAX POST to admin-ajax.php
  → Etch_WP_Menus::ajax_generate_code()
  → Etch_Navigation_Generator methods:
      generate_html()        → HTML tab
      generate_css()         → CSS tab
      generate_javascript()  → JS tab
      generate_etch_json()   → ETCH JSON tab (includes styles + script)
  → JSON response → admin-builder.js renders output tabs
```

### Data Flow (Frontend)

```
WordPress Menu (wp_terms, wp_termmeta, wp_posts)
  → wp_get_nav_menu_items($menu_id)
  → _wp_menu_item_classes_by_context($menu_items)   [frontend only]
  → Build hierarchical tree with state_classes
  → Injected via etch/dynamic_data/option filter
  → Available as options.menus.{menu_slug} in ETCH templates
```

---

## ETCH Block Tree Format

The ETCH JSON output uses Gutenberg-compatible block serialization. Every block has these fields:

```php
array(
    'blockName'    => 'etch/element',     // Block type
    'attrs'        => array( ... ),       // Block attributes
    'innerBlocks'  => array( ... ),       // Child blocks
    'innerHTML'    => "\n\n",             // Gutenberg innerHTML convention
    'innerContent' => array( "\n", "\n" ), // Gutenberg innerContent convention
)
```

### Block Types

#### `etch/element`
Renders an HTML element (div, nav, ul, li, a, button, span).

```php
'attrs' => array(
    'metadata'   => array( 'name' => 'Menu Item' ),  // Structure Panel label
    'tag'        => 'li',                              // HTML tag
    'attributes' => array(                             // HTML attributes
        'class' => 'global-nav__menu-item {item.state_classes}',
        'role'  => 'none',
        'href'  => '{item.url}',                       // ETCH variable interpolation
    ),
    'styles'     => array( 'global-nav-menu-item' ),   // References top-level styles keys
)
```

#### `etch/text`
Renders text content (supports ETCH template variables).

```php
'attrs' => array(
    'metadata' => array( 'name' => 'Menu Label' ),
    'content'  => '{item.title}',
)
```

#### `etch/loop`
Iterates over a data source. **Always uses `item` as the iterator variable.**

```php
'attrs' => array(
    'metadata' => array( 'name' => 'Menu Items' ),
    'target'   => 'options.menus.primary_menu',   // Data source path
)
```

#### `etch/condition`
Conditionally renders child blocks. **`conditionString` MUST be a top-level attr.**

```php
'attrs' => array(
    'metadata'        => array( 'name' => 'If Has Children' ),
    'conditionString' => 'item.children',          // MUST be top-level
    'condition'       => array(
        'leftHand'  => 'item.children',
        'operator'  => 'isTruthy',
        'rightHand' => null,
    ),
)
```

### innerHTML / innerContent Convention

Follows Gutenberg serialization:

- **0 children:** `innerHTML: "\n\n"`, `innerContent: ["\n", "\n"]`
- **1 child:** `innerHTML: "\n\n"`, `innerContent: ["\n", null, "\n"]`
- **2 children:** `innerHTML: "\n\n\n"`, `innerContent: ["\n", null, "\n\n", null, "\n"]`
- **N children:** `["\n", null, "\n\n", null, ... null, "\n"]` (null placeholders for child positions)

### Script Attachment

JavaScript is base64-encoded and attached to the root `nav` element:

```php
$nav_element['attrs']['script'] = array(
    'code' => base64_encode( $js ),
    'id'   => $cls . '-' . time(),
);
```

---

## ETCH Style Object Format

Each style in the `styles` collection is keyed by an identifier and has this shape:

```php
'global-nav-menu-link' => array(
    'type'       => 'class',                           // Always 'class'
    'selector'   => '.global-nav__menu-link',          // CSS selector
    'collection' => 'default',                         // Always 'default'
    'css'        => "text-decoration: none;\ncolor: #2c3338;\nfont-weight: 500;",
    'readonly'   => false,
)
```

### Rules

- **Selector:** Flat CSS selector (no SCSS nesting). Use full BEM selectors.
- **CSS:** Individual property declarations separated by `\n`. No braces.
- **Pseudo-elements/states:** Use separate style objects (e.g., `.menu-link:hover` is its own entry).
- **Media queries:** Write as raw CSS string in the `css` field: `@media (max-width: 1200px) { ... }` — the entire block including braces.
- **Style keys:** Referenced from element blocks via `attrs.styles` array.

---

## ETCH Template Syntax

Used in HTML tab output and block tree `content`/`attributes` fields.

### Loops
```
{#loop options.menus.primary_menu as item}
  ...
{/loop}
```

### Conditionals (HTML tab only)
```
{#if item.children}
  ...
{/if}
```

**CRITICAL:** `{#if}` does NOT work inside ETCH block tree `attributes`. Only simple variable interpolation works in block attributes.

### Variable Interpolation
```
{item.title}
{item.url}
{item.state_classes}
```

Works in both HTML tab templates AND block tree `attributes` values.

### Nested Loops
In the HTML tab, nested loops use different variable names:
```
{#loop options.menus.primary_menu as item}
  {#loop item.children as child}
    {#loop child.children as subchild}
```

In the ETCH block tree, nested `etch/loop` blocks **always use `item`** — ETCH automatically scopes each loop level.

---

## Key Discoveries & Gotchas

### 1. `{#if}` Does Not Work in Block Tree Attributes

**Problem:** Placing `{#if item.current}is-current{/if}` inside a block's `attributes.class` causes ALL items to get ALL classes.

**Solution:** Pre-compute a `state_classes` string in the PHP data layer:
```php
$state = array();
if ( $menu_item['current'] )        $state[] = 'is-current';
if ( $menu_item['current_parent'] ) $state[] = 'is-current-parent';
if ( ! empty( $menu_item['children'] ) ) $state[] = 'has-submenu';
$menu_item['state_classes'] = implode( ' ', $state );
```
Then use `{item.state_classes}` in the block attribute — simple interpolation works.

### 2. `conditionString` Must Be Top-Level

**Problem:** Placing `conditionString` inside the `condition` object resulted in empty `{#if }` output.

**Solution:** `conditionString` is a top-level attr on `etch/condition` blocks, alongside the `condition` object.

### 3. ETCH Loop Blocks Always Use `item`

**Problem:** Using `child`, `subchild` etc. as loop iterator names in the block tree didn't scope correctly.

**Solution:** All `etch/loop` blocks use `item` as the iterator. ETCH scopes each nested loop automatically. The target for nested loops is always `item.children`.

### 4. `sanitize_prop_name()` vs `sanitize_key()`

**Problem:** WordPress's `sanitize_key()` lowercases everything, turning `menuItems` into `menuitems`.

**Solution:** Custom `sanitize_prop_name()` that preserves camelCase — only removes characters not matching `[a-zA-Z0-9_]`.

### 5. `_wp_menu_item_classes_by_context()`

**Problem:** `wp_get_nav_menu_items()` alone does NOT populate `current-menu-item`, `current-menu-parent`, `current-menu-ancestor` classes.

**Solution:** Call `_wp_menu_item_classes_by_context( $menu_items )` after getting menu items. Only works on frontend (not in admin), so wrap in `if ( ! is_admin() )`.

### 6. Submenu Behaviour Scope

**Problem:** Applying `submenu_behavior` settings (always/accordion/clickable) to desktop CSS broke hover-reveal.

**Solution:** Desktop CSS ALWAYS uses hover-reveal. The `submenu_behavior` setting ONLY affects the mobile `@media` block.

### 7. Duplicate Hamburger Button

ETCH may render its own `etch-burger` element alongside our `__hamburger` button. These are independent — our button is inside the generated `<nav>`, ETCH's is external. Set our hamburger z-index higher (1000) than the menu (999).

---

## Dual CSS Architecture

Every CSS change must be made in **two places**:

### 1. SCSS-Nested (CSS Tab)
Generated by `generate_css()`. Uses SCSS `&` nesting under `.{$cls}`:
```scss
.global-nav {
  &__menu-item { position: relative; }
  &__submenu { position: absolute; ... }
}
```
Users copy this to their CSS panel or stylesheet.

### 2. Flat Individual Styles (ETCH JSON)
Generated by `build_etch_styles()`. Each rule is a separate style object with flat selectors:
```php
'global-nav-submenu' => array(
    'selector' => '.global-nav__submenu',
    'css'      => 'position: absolute; ...',
)
```
These are embedded in the ETCH JSON and render automatically in the builder.

### Mobile CSS Split

- **SCSS:** `get_menu_position()` returns a complete `@media` block with SCSS nesting
- **Flat:** `get_flat_mobile_css()` returns a single style object whose `css` field is a raw `@media` block with flat selectors

Both methods accept the same parameters: `$position`, `$breakpoint`, `$settings`/`$mobile_depth`, `$submenu_behavior`.

---

## WordPress Integration

### Filter: `etch/dynamic_data/option`

The `add_menus_to_etch()` method in `etch-wp-menus.php` hooks into this ETCH filter to inject all WordPress menus as hierarchical arrays:

```php
add_filter( 'etch/dynamic_data/option', array( $this, 'add_menus_to_etch' ) );
```

This makes `options.menus.{menu_slug}` available throughout ETCH.

### Menu Slug Sanitization

Menu names are sanitized via `sanitize_for_etch()`:
- Lowercased
- Spaces and hyphens converted to underscores
- Special characters removed

Examples: "Primary Menu" → `primary_menu`, "Footer-Navigation" → `footer_navigation`

### Current Page Detection

On the frontend, `_wp_menu_item_classes_by_context()` inspects `$wp_query` to mark:
- `current-menu-item` → `is-current`
- `current-menu-parent` + `current-menu-ancestor` → `is-current-parent`

These are stored as boolean fields AND pre-computed into `state_classes`.

---

## Settings & Their Effects

| Setting | Values | Affects |
|---------|--------|---------|
| `approach` | `direct`, `component` | Loop target, HTML template |
| `menu_id` | WordPress menu ID | Menu name slug in loop target |
| `container_class` | String (default: `global-nav`) | All CSS selectors, HTML classes |
| `component_prop_name` | String (default: `menuItems`) | Component loop target |
| `mobile_menu_support` | Boolean | Hamburger, mobile CSS, JS output |
| `mobile_breakpoint` | 320-1920 (default: 1200) | @media max-width value |
| `hamburger_animation` | `spin`, `squeeze`, `collapse`, `arrow` | Hamburger CSS transforms |
| `menu_position` | `left`, `right`, `top`, `full` | Mobile menu CSS positioning |
| `submenu_behavior` | `always`, `accordion`, `clickable` | Mobile submenu CSS + JS |
| `submenu_depth_desktop` | 0-5 (default: 1) | Submenu block tree depth |
| `submenu_depth_mobile` | 0-5 (default: 1) | Mobile submenu CSS |
| `close_methods` | Array: `hamburger`, `outside`, `esc` | JS event listeners |
| `accessibility` | Array: `focus_trap`, `scroll_lock`, `aria`, `keyboard` | JS features |

---

## File-by-File Breakdown

### `etch-wp-menus.php` (Main Plugin File)
- Singleton pattern (`Etch_WP_Menus`)
- Registers admin menu under Tools
- Enqueues admin CSS/JS (only on plugin page)
- AJAX handlers: `ajax_generate_code()`, `ajax_get_menu_json()`
- `add_menus_to_etch()` — the `etch/dynamic_data/option` filter callback

### `includes/class-navigation-generator.php` (Code Generator)
The core engine. Key methods:

**Public API:**
- `generate_html($settings)` — dispatches to direct or component HTML
- `generate_css($settings)` — SCSS-nested CSS with responsive breakpoints
- `generate_javascript($settings)` — Vanilla JS IIFE
- `generate_etch_json($settings)` — Complete ETCH block tree + styles + script
- `get_menu_json($menu_id)` — Menu data preview

**HTML Generation:**
- `generate_direct_html()` — HTML with `{#loop options.menus.{slug} as item}`
- `generate_component_html()` — HTML with `{#loop props.menuItems as item}`
- `generate_submenu_html_direct()` — Recursive submenu HTML (direct approach)
- `generate_submenu_html_component()` — Recursive submenu HTML (component approach)

**CSS Generation:**
- `generate_css()` — Main SCSS output, calls `get_menu_position()` for mobile
- `get_menu_position($position, $breakpoint, $settings)` — SCSS @media block per position
- `get_hamburger_animation($type)` — SCSS hamburger transforms

**ETCH JSON Generation:**
- `generate_etch_json()` — Assembles complete block tree, styles, script
- `build_etch_styles($settings)` — All flat style objects (desktop + mobile)
- `build_submenu_blocks($depth, $max_depth, $approach)` — Recursive submenu block tree
- `build_hamburger_block()` — Hamburger button block with 3 spans
- `get_flat_mobile_css($position, $breakpoint, $mobile_depth, $submenu_behavior)` — @media block
- `get_flat_hamburger_animation($type)` — Flat animation style objects

**Block Tree Helpers:**
- `make_element_block($name, $tag, $attributes, $style_keys, $inner_blocks)`
- `make_text_block($name, $content)`
- `make_loop_block($name, $target, $inner_blocks)`
- `make_condition_block($name, $left_hand, $operator, $right_hand, $inner_blocks)`
- `make_inner_html($child_count)` — Gutenberg innerHTML convention
- `make_inner_content($child_count)` — Gutenberg innerContent convention

**Utilities:**
- `get_css_class($settings)` / `sanitize_css_class($name)` — Container class
- `sanitize_for_etch($name)` — Menu slug (underscores only)
- `sanitize_prop_name($name)` — Component prop name (preserves camelCase)
- `get_menu_name($settings)` — Resolved menu slug from settings
- `has_mobile_support($settings)` — Boolean check
- `get_depth_var_name($depth)` — Variable names for HTML tab: child, subchild, etc.

### `includes/class-admin-page.php`
- Static `render()` method loads the template
- Provides default settings

### `templates/admin-page.php`
- WordPress-native admin UI with radio buttons, toggles, sliders
- Approach selector (direct/component)
- Menu dropdown, container class field, component prop name
- Mobile settings: breakpoint, hamburger animation, position, submenu behaviour
- Accessibility checkboxes, close method checkboxes
- Output area with 4 tabs: ETCH JSON (recommended), HTML, CSS, JS
- Sidebar: field names reference, data structure info

### `assets/js/admin-builder.js`
- jQuery-based admin interaction
- Settings collection from form fields
- AJAX request to generate code
- Tab switching, copy-to-clipboard
- Hamburger animation preview
- Menu JSON preview loader

### `assets/css/admin-builder.css`
- WordPress-native admin styling
- Toggle switches, pill radio buttons, card layouts
- Code output blocks with syntax styling
- Responsive admin layout

---

## CSS Architecture

### BEM Methodology

All selectors use BEM with a dynamic prefix (`$cls`, default `global-nav`):

```
Block:    .global-nav
Elements: .global-nav__container, __hamburger, __menu, __menu-list, etc.
Modifiers: .is-current, .is-current-parent, .is-open, .is-active, .has-submenu
```

### Desktop Styles (Always Applied)
- Horizontal flex layout
- Submenu hidden with `opacity: 0; visibility: hidden`
- Submenu revealed on `:hover` with transition
- Nested submenus fly out to the right
- Hamburger hidden (`display: none`)
- Submenu toggle hidden (`display: none`)

### Mobile Styles (Inside @media)
- Hamburger shown (`display: flex`)
- Submenu toggle shown (`display: flex`) — for accordion mode
- Menu is fixed-position panel (left/right/top/full)
- Menu starts off-screen, slides in with `.is-open`
- Box shadow only on `.is-open` state
- Menu list stacks vertically
- Submenu behaviour varies by setting:
  - **Always:** Expanded, visible
  - **Accordion:** Collapsed (`max-height: 0`), expand on `.is-open`
  - **Clickable:** Hidden (`display: none`)

### Chevron Toggle (Accordion Mode, Mobile Only)
```css
.__submenu-toggle {
  /* Positioned absolute right inside .has-submenu li */
  /* Contains ::after pseudo-element with CSS border chevron */
  /* Rotates from 45deg (down) to -135deg (up) when .is-open */
}
```

### Submenu Dash Inset (Mobile Only)
```css
.__submenu-link::before {
  content: '—';
  position: absolute;
  left: 0;
  /* Visually indents submenu items */
}
```

---

## JavaScript Architecture

### Pattern: IIFE with Object Literal

```javascript
(function() {
  'use strict';
  const globalNav = {
    isOpen: false,
    scrollPosition: 0,
    init: function() { ... },
    toggleMenu: function() { ... },
    // ... modular methods
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { globalNav.init(); });
  } else {
    globalNav.init();
  }
})();
```

### Modular Features (Conditionally Included)

Each feature is added to the JS output only if its corresponding setting is enabled:

- **`toggleMenu()`** — Always included. Toggles `.is-active` on hamburger, `.is-open` on menu.
- **`lockScroll()` / `unlockScroll()`** — If `scroll_lock` accessibility enabled
- **`trapFocus()` / `releaseFocus()` / `handleFocusTrap()`** — If `focus_trap` enabled
- **`handleClickOutside()`** — If `outside` close method enabled
- **`handleEscKey()`** — If `esc` close method enabled
- **`setupSubmenuAccordion()`** — If `submenu_behavior === 'accordion'`

### Accordion Toggle

The accordion JS targets `.__submenu-toggle` buttons (not links):
```javascript
const submenuToggles = this.menu.querySelectorAll('.global-nav__submenu-toggle');
submenuToggles.forEach(toggle => {
    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const parent = toggle.parentElement;
        const submenu = parent.querySelector('.global-nav__submenu');
        parent.classList.toggle('is-open');
        submenu.style.maxHeight = parent.classList.contains('is-open')
            ? submenu.scrollHeight + 'px' : '0';
    });
});
```

Parent `<a>` links remain fully navigable — they are not hijacked.

---

## Known Limitations

1. **`_wp_menu_item_classes_by_context()` only works on frontend** — In admin context (`is_admin()`), current/parent/ancestor detection is skipped. Menu JSON preview won't show current states.

2. **ETCH loop variable is always `item`** — Cannot use custom iterator names in block tree. HTML tab output uses `child`/`subchild` for readability but the block tree always uses `item`.

3. **`{#if}` does not work in block attributes** — Only simple `{variable}` interpolation works. State detection must be pre-computed as `state_classes`.

4. **No hooks/filters on generated output** — The plugin doesn't provide WordPress hooks for customizing generated code. Users modify the output after generation.

5. **ETCH-specific format** — The ETCH JSON output is specific to the ETCH theme builder. It cannot be used with other builders.

6. **Mobile breakpoint is hardcoded to 60px hamburger height** — The `top: 60px` offset assumes the container + hamburger is approximately 60px tall. If users change container padding significantly, they may need to adjust.

7. **Max accordion height** — Accordion submenus use `max-height: 500px` as a fallback in CSS, with JS setting the actual `scrollHeight`. Very tall submenus may have a brief transition quirk.

---

## Reference

- **ETCH Builder:** Digital Gravy — https://developer.etchbuilder.com
- **Plugin Website:** https://bbg.digital
- **WordPress Codex:** `wp_get_nav_menu_items()`, `_wp_menu_item_classes_by_context()`
