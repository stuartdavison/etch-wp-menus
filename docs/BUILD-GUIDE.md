# Etch WP Menus - Build Guide

Comprehensive architecture reference for developers and AI agents working on this plugin.

**Version:** 3.0.0
**Last Updated:** February 9, 2026

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
WordPress Menu (wp_terms, wp_term_relationships)
  → wp_get_nav_menu_items($menu_id)
  → _wp_menu_item_classes_by_context($menu_items)   [frontend only]
  → Build hierarchical tree with state_classes + link_classes
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
        'class'      => 'global-navigation__item {item.state_classes}',
        'role'       => 'none',
        'data-level' => '1',
    ),
    'styles'     => array( 'global-navigation-item' ), // References top-level styles keys
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

### Block Tree Structure (v3)

When mobile is enabled, the nav is the root block. The hamburger is a sibling of `__menu` inside nav — it stays visible when `__menu` slides off-screen:

```
Nav Element (nav.{cls}, data-position="{pos}", data-behavior="{behavior}")
├── Hamburger Button (button.__hamburger, position: absolute)
│   ├── Line 1 (span.__hamburger-line)
│   ├── Line 2 (span.__hamburger-line)
│   └── Line 3 (span.__hamburger-line)
└── Menu Panel (div.__menu, position: fixed + transform on mobile)
    └── Menu List (ul.__list)
        └── Loop (etch/loop → options.menus.{slug})
            └── Menu Item (li.__item {item.state_classes})
                ├── Link (a.__link {item.link_classes})
                │   └── Text ({item.title})
                ├── Condition (if item.children)
                │   └── Toggle Button (button.__submenu-toggle)
                │       └── Icon (span.__submenu-icon)
                └── Condition (if item.children)
                    └── Submenu (ul.__sub-menu)
                        └── Loop (etch/loop → item.children)
                            └── ... (recursive)
```

When mobile is disabled, the nav element is the root block directly (no hamburger, no `__menu` wrapper — `<ul>` is a direct child).

---

## ETCH Style Object Format

Each style in the `styles` collection is keyed by an identifier and has this shape:

```php
'global-navigation-link' => array(
    'type'       => 'class',                                // Always 'class'
    'selector'   => '.global-navigation__link',             // CSS selector
    'collection' => 'default',                              // Always 'default'
    'css'        => "text-decoration: none;\ncolor: var(--menu-clr-text);\nfont-weight: 500;",
    'readonly'   => false,
)
```

### Rules

- **Selector:** Flat CSS selector (no nesting). Use full BEM selectors.
- **CSS:** Individual property declarations separated by `\n`. No braces.
- **CSS Custom Properties:** All values reference `var(--menu-*)` tokens where appropriate.
- **Pseudo-elements/states:** Use separate style objects (e.g., `.link:hover` is its own entry).
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
{item.link_classes}
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

**Problem:** Placing `{#if item.current}current-page{/if}` inside a block's `attributes.class` causes ALL items to get ALL classes.

**Solution:** Pre-compute separate class strings in the PHP data layer:
```php
// state_classes — for <li> elements
$state = array();
if ( $menu_item['current_parent'] ) $state[] = 'current-parent';
if ( ! empty( $menu_item['children'] ) ) $state[] = 'has-submenu';
$menu_item['state_classes'] = implode( ' ', $state );

// link_classes — for <a> elements
$link = array();
if ( $menu_item['current'] ) $link[] = 'current-page';
$menu_item['link_classes'] = implode( ' ', $link );
```
Then use `{item.state_classes}` on `<li>` and `{item.link_classes}` on `<a>` — simple interpolation works.

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

Desktop CSS ALWAYS uses hover-reveal with configurable delay. The `submenu_behavior` setting (accordion/slide) ONLY affects the mobile `@media` block. This is enforced in both the CSS tab output and the ETCH flat styles.

### 7. Hamburger Inside `<nav>`, Sibling of `__menu`

The hamburger is inside `<nav>` as its first child, but it is a **sibling** of the `__menu` wrapper div (not inside it). On mobile, `__menu` gets `position: fixed` + `transform` to slide off-screen. Because the hamburger is outside `__menu`, it stays visible. The JS finds the hamburger via `document.querySelector('[aria-controls="' + navElement.id + '"]')` (matching the `aria-controls` attribute to the nav's `id`).

### 8. `__menu` Wrapper Pattern

The `__menu` div wraps the `<ul>` and is the element that slides on/off screen on mobile. The `is-open` class is applied to `__menu` (not `<nav>`). This pattern ensures the hamburger stays accessible at all times. JS stores this as `this.menu` and appends sliding panels to it.

### 9. No Modifier Classes — Use Data Attributes

ETCH strips extra classes from elements (it controls the `class` attribute via its styles system). Instead of `.global-navigation--left` and `.global-navigation--slide`, position and behaviour are stored as `data-position` and `data-behavior` attributes on `<nav>`. JS reads these via `this.nav.dataset.behavior`.

### 10. ETCH Selector Deduplication

ETCH only renders the **last** style with a given selector value. If multiple styles share selector `.global-navigation`, only the last one appears in the rendered CSS. All CSS for `.global-navigation` must be combined into a single style entry (`nav-all`). Each ETCH style must have a unique selector.

### 11. Desktop Sub-menu Styles Must Be Media-Query Wrapped

Desktop dropdown positioning (`position: absolute; opacity: 0; visibility: hidden`) and hover reveal (`opacity: 1; visibility: visible`) must be inside `@media (min-width)` blocks. Without this, they leak into mobile and cause submenus to appear as floating dropdowns on hover inside sliding panels.

### 12. Slide Mode Panel Structure

The ETCH block tree and HTML output use the same nested structure for both accordion and slide modes. Slide mode's flat panel structure is built **dynamically by JavaScript** from the nested HTML at the mobile breakpoint. JS reads `this.nav.dataset.behavior === 'slide'` to activate `setupSlideMode()`.

---

## Dual CSS Architecture

Every CSS change must be made in **two places**:

### 1. CSS-Nested (CSS Tab)
Generated by `generate_css()`. Uses native CSS nesting under `.{$cls}`:
```css
.global-navigation {
  & .global-navigation__item { position: relative; }
  & .global-navigation__sub-menu { position: absolute; ... }
}
```
Users copy this to their CSS panel or stylesheet.

### 2. Flat Individual Styles (ETCH JSON)
Generated by `build_etch_styles()`. Each rule is a separate style object with flat selectors:
```php
'global-navigation-sub-menu' => array(
    'selector' => '.global-navigation__sub-menu',
    'css'      => 'position: absolute; ...',
)
```
These are embedded in the ETCH JSON and render automatically in the builder.

### Mobile CSS Split

- **CSS Tab:** Mobile styles are part of the main `generate_css()` output, wrapped in `@media (max-width: {breakpoint}px)`. The `__menu` wrapper gets `position: fixed` in a separate rule.
- **Flat (ETCH JSON):** `get_flat_mobile_css()` returns three style objects:
  - `nav-all` (selector `.{cls}`) — base nav + all child-element responsive CSS (the ONLY style with this selector)
  - `menu-all` (selector `.{cls}__menu`) — `position: fixed` + `transform` on mobile
  - `menu-mobile-open` (selector `.{cls}__menu.is-open`) — `transform` to reveal panel

### CSS Custom Properties

Both CSS outputs reference `:root` custom properties:
```css
:root {
  --menu-clr-text: #2c3338;
  --menu-clr-text-accent: #0073aa;
  --menu-clr-bg: #ffffff;
  --menu-clr-bg-accent: #f0f0f1;
  --menu-clr-bg-hover: #f9f9f9;
  --menu-mobile-width: 320px;
  --menu-padding-x: 1.25rem;
  --menu-padding-y: 0.75rem;
  --menu-gap: 0.5rem;
  --menu-padding-top: 80px;
  --menu-toggle-size: 44px;
  --menu-transition-duration: 0.2s;
  --menu-transition-easing: ease-in-out;
  --menu-hover-delay: 0.15s;
  --menu-mobile-breakpoint: {breakpoint}px;
}
```
Users customise appearance by overriding these tokens — no need to edit generated CSS.

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
- `current-menu-item` → `current` boolean → `link_classes: "current-page"` (on `<a>`)
- `current-menu-parent` + `current-menu-ancestor` → `current_parent` boolean → `state_classes: "current-parent"` (on `<li>`)

These are stored as boolean fields AND pre-computed into `state_classes` and `link_classes`.

---

## Settings & Their Effects

| Setting | Values | Affects |
|---------|--------|---------|
| `approach` | `direct`, `component` | Loop target, HTML template |
| `menu_id` | WordPress menu ID | Menu name slug in loop target |
| `container_class` | String (default: `global-navigation`) | All CSS selectors, HTML classes |
| `component_prop_name` | String (default: `menuItems`) | Component loop target |
| `mobile_menu_support` | Boolean | Hamburger, mobile CSS, JS output |
| `mobile_breakpoint` | 320–1920 (default: 1200) | @media max-width value, CSS custom property |
| `hamburger_animation` | `spin`, `squeeze`, `collapse` | Hamburger CSS transforms |
| `menu_position` | `left`, `right`, `top` | Mobile menu CSS positioning, modifier class on `<nav>` |
| `submenu_behavior` | `accordion`, `slide` | Mobile submenu CSS + JS, modifier class on `<nav>` |
| `submenu_depth_desktop` | 0–5 (default: 1) | Submenu block tree depth |
| `submenu_depth_mobile` | 0–5 (default: 1) | Mobile submenu CSS indentation |
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
- Builds `state_classes` (`has-submenu`, `current-parent`) and `link_classes` (`current-page`)

### `includes/class-navigation-generator.php` (Code Generator)
The core engine. Key methods:

**Public API:**
- `generate_html($settings)` — dispatches to direct or component HTML
- `generate_css($settings)` — CSS with custom properties + native nesting
- `generate_javascript($settings)` — ES6 `class AccessibleNavigation`
- `generate_etch_json($settings)` — Complete ETCH block tree + styles + script
- `get_menu_json($menu_id)` — Menu data preview

**HTML Generation:**
- `generate_direct_html()` — HTML with `{#loop options.menus.{slug} as item}`
- `generate_component_html()` — HTML with `{#loop props.menuItems as item}`
- `generate_submenu_html_direct()` — Recursive submenu HTML (direct approach)
- `generate_submenu_html_component()` — Recursive submenu HTML (component approach)

**CSS Generation:**
- `generate_css()` — Main CSS output with `:root` tokens, native nesting, responsive breakpoints
- `get_menu_position($position, $breakpoint)` — Position-specific mobile CSS
- `get_hamburger_animation($type)` — Hamburger transforms (spin, squeeze, collapse)

**JavaScript Generation:**
- `generate_javascript()` — ES6 class with slide mode, desktop hover, edge detection, keyboard nav

**ETCH JSON Generation:**
- `generate_etch_json()` — Assembles complete block tree, styles, script
- `build_etch_styles($settings)` — All flat style objects (desktop + mobile + hamburger)
- `build_submenu_blocks($depth, $max_depth, $approach)` — Recursive submenu block tree
- `build_hamburger_block()` — Hamburger button block with 3 spans + aria-controls
- `get_flat_mobile_css($position, $breakpoint, $submenu_behavior)` — Returns 3 styles: `nav-all` (combined .{cls} CSS), `menu-all` (.__menu positioning), `menu-mobile-open` (.__menu.is-open)
- `get_flat_hamburger_animation($type)` — Flat animation style objects

**Block Tree Helpers:**
- `make_element_block($name, $tag, $attributes, $style_keys, $inner_blocks)`
- `make_text_block($name, $content)`
- `make_loop_block($name, $target, $inner_blocks)`
- `make_condition_block($name, $left_hand, $operator, $right_hand, $inner_blocks)`
- `make_inner_html($child_count)` — Gutenberg innerHTML convention
- `make_inner_content($child_count)` — Gutenberg innerContent convention

**Utilities:**
- `get_css_class($settings)` / `sanitize_css_class($name)` — Container class (default: `global-navigation`)
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
- Mobile settings: breakpoint, hamburger animation (spin/squeeze/collapse), position (left/right/top), submenu behaviour (accordion/slide)
- Accessibility checkboxes, close method checkboxes
- Output area with 4 tabs: ETCH JSON (recommended), HTML, CSS, JS
- Sidebar: field names reference, data structure info

### `assets/js/admin-builder.js`
- jQuery-based admin interaction
- Settings collection from form fields
- AJAX request to generate code
- Tab switching, copy-to-clipboard
- Hamburger animation preview (spin, squeeze, collapse)
- Menu JSON preview loader
- Mobile settings show/hide toggle
- Container class auto-population from menu name

### `assets/css/admin-builder.css`
- WordPress-native admin styling
- Toggle switches, pill radio buttons, card layouts
- Code output blocks with syntax styling
- Responsive admin layout

---

## CSS Architecture

### BEM Methodology

All selectors use BEM with a dynamic prefix (`$cls`, default `global-navigation`):

```
Block:    .global-navigation
Elements: .__hamburger, .__menu, .__list, .__item, .__link, .__sub-menu,
          .__submenu-toggle, .__submenu-icon, .__back, .__back-button, .__back-icon
Attributes: data-position="left|right|top", data-behavior="accordion|slide" (on <nav>)
Utilities: .has-submenu, .current-parent (on <li>), .current-page (on <a>)
JS State:  .is-open (on .__menu), .is-active (on hamburger),
           .__item--submenu-open (on <li>), .cascade-left, .menu-open (on body)
```

### Desktop Styles (Inside @media min-width)
- Horizontal flex layout for `__list`
- `__sub-menu` hidden with `opacity: 0; visibility: hidden` — **inside desktop `@media` only** to prevent mobile leak
- Submenu revealed on parent `:hover` and `:focus-within` with `transition-delay` — **desktop `@media` only**
- Nested submenus cascade to the right (`left: 100%`) — **desktop `@media` only**
- `.cascade-left` class (JS-managed) cascades to the left for edge detection
- Hamburger hidden (`display: none`)
- Submenu toggle hidden (`display: none`)

### Mobile Styles (Inside @media max-width)
- Hamburger shown (`display: flex`, position: absolute)
- `__menu` wrapper gets `position: fixed` + `transform` to slide off-screen (not the nav — nav stays in flow)
- `__menu.is-open` slides the panel into view
- Menu list stacks vertically
- Submenu behaviour varies by `data-behavior` attribute:
  - **`accordion`:** Submenu hidden with `max-height: 0; overflow: hidden`. Toggle shown. `__item--submenu-open` reveals submenu. Chevron rotates.
  - **`slide`:** JS builds flat panels dynamically from nested HTML. Original `__list` hidden via `__menu > __list { display: none }`. Panels slide horizontally with back buttons.

### Chevron Toggle (Mobile Only)
```css
.__submenu-icon {
  /* CSS border chevron pointing down */
  /* Rotates 180deg when parent has __item--submenu-open class */
}
```

### Submenu Indentation (Mobile Only)
Submenu items use `padding-left` for visual indentation:
- Level 2: `padding-left: 40px`
- Level 3: `padding-left: 70px`

### CSS Custom Properties
All values use `:root` tokens. Users override tokens to customise without editing generated CSS:
- `--menu-clr-*` — Colour tokens
- `--menu-padding-*` — Spacing tokens
- `--menu-transition-*` — Animation tokens
- `--menu-mobile-width` — Mobile panel width
- `--menu-toggle-size` — Toggle button size
- `--menu-mobile-breakpoint` — Stored for JS to read via `getComputedStyle()`

---

## JavaScript Architecture

### Pattern: ES6 Class

```javascript
class AccessibleNavigation {
  constructor(navElement) {
    this.nav = navElement;
    this.menu = navElement.querySelector('.global-navigation__menu');
    this.hamburger = document.querySelector('[aria-controls="' + navElement.id + '"]');
    this.isMobile = false;
    this.isOpen = false;
    this.init();
  }

  init() {
    this.checkMobile();
    window.addEventListener('resize', () => this.checkMobile());
    this.hamburger.addEventListener('click', () => this.toggleMenu());
    this.setupDesktopHover();
    this.checkSubMenuEdges();
    this.setupKeyboardNavigation();
    // Conditional: accordion toggle, slide mode, close methods
  }
  // ...
}

document.addEventListener('DOMContentLoaded', function() {
  const nav = document.querySelector('.global-navigation');
  if (nav) new AccessibleNavigation(nav);
});
```

### Core Methods

- **`checkMobile()`** — Reads `--menu-mobile-breakpoint` from CSS via `getComputedStyle()`, sets `this.isMobile`
- **`toggleMenu()` / `openMenu()` / `closeMenu()`** — Toggle `is-open` on `__menu` (not nav), `is-active` on hamburger, `menu-open` on body. Focus management.
- **`setupDesktopHover()`** — `mouseenter`/`mouseleave` on `.has-submenu` items with 200ms delay timeout. Closes sibling submenus. Adds `__item--submenu-open` class.
- **`checkSubMenuEdges()`** — On `mouseenter`, checks if `__sub-menu` overflows viewport right edge. Adds `.cascade-left` if so.
- **`setupKeyboardNavigation()`** — Full WCAG arrow key support. Context-aware (top-level horizontal vs submenu vertical, mobile vs desktop).
- **`handleAccordionToggle(e)`** — Toggles `__item--submenu-open` on parent item, updates `aria-expanded`, manages `max-height`.

### Conditional Features (Included Based on Settings)

- **`setupSlideMode()`** — Only if `submenu_behavior === 'slide'`. Builds flat panel structure from nested HTML. Creates `sliding-nav-panels` container, `sliding-panel` divs, back buttons.
- **`setupSlidePanelListeners()`** — Click handlers for forward/back panel navigation.
- **Scroll lock** — `lockScroll()` / `unlockScroll()` — saves scroll position, applies `menu-open` to body.
- **Focus trap** — `trapFocus()` / `releaseFocus()` — traps Tab key within open mobile menu.
- **Click outside** — Closes menu when clicking outside nav element.
- **ESC key** — Closes menu on Escape key press.

### Key Element Discovery

```javascript
// Hamburger — inside <nav> but linked via aria-controls
this.hamburger = document.querySelector('[aria-controls="' + navElement.id + '"]');
// Menu panel — the wrapper div that slides on mobile
this.menu = navElement.querySelector('.global-navigation__menu');
```

---

## Known Limitations

1. **`_wp_menu_item_classes_by_context()` only works on frontend** — In admin context (`is_admin()`), current/parent/ancestor detection is skipped. Menu JSON preview won't show current states.

2. **ETCH loop variable is always `item`** — Cannot use custom iterator names in block tree. HTML tab output uses `child`/`subchild` for readability but the block tree always uses `item`.

3. **`{#if}` does not work in block attributes** — Only simple `{variable}` interpolation works. State detection must be pre-computed as `state_classes` and `link_classes`.

4. **No hooks/filters on generated output** — The plugin doesn't provide WordPress hooks for customizing generated code. Users modify the output after generation.

5. **ETCH-specific format** — The ETCH JSON output is specific to the ETCH theme builder. It cannot be used with other builders.

6. **Slide mode built dynamically** — The slide panel structure is created by JavaScript at runtime from nested HTML. The ETCH block tree does not contain slide-specific blocks.

7. **CSS custom properties browser support** — All styling uses CSS custom properties (`var(--menu-*)`). Requires browsers that support custom properties (all modern browsers).

---

## Reference

- **ETCH Builder:** Digital Gravy — https://developer.etchbuilder.com
- **Plugin Website:** https://bbg.digital
- **WordPress Codex:** `wp_get_nav_menu_items()`, `_wp_menu_item_classes_by_context()`
