# Etch WP Menus

Generate customizable, accessible navigation code for the ETCH theme builder with CSS custom properties, slide/accordion mobile modes, and full keyboard navigation.

## Description

Etch WP Menus is a WordPress plugin that provides an intuitive admin interface for generating professional navigation code tailored for the ETCH theme builder by Digital Gravy. It outputs four code formats: HTML, CSS, JavaScript, and ETCH JSON (a complete block tree with styles and script ready to paste into ETCH's Structure Panel).

## Version

**Current:** 3.0.0

## Features

- **Two Implementation Approaches**:
    - **Direct Loop**: Binds to WordPress menus via `{#loop options.menus.{slug} as item}`
    - **Component**: Reusable components via `{#loop props.menuItems as item}`

- **ETCH JSON Block Tree**: Generates `etch/element`, `etch/loop`, `etch/condition`, and `etch/text` blocks — fully editable in ETCH's Structure Panel

- **Pre-computed State Fields**:
    - `item.state_classes` — utility classes for `<li>` elements (`has-submenu`, `current-parent`)
    - `item.link_classes` — utility classes for `<a>` elements (`current-page`)

- **CSS Custom Properties**: All styling controlled via `:root` tokens (`--menu-clr-text`, `--menu-padding-x`, etc.) — easy to customise without editing generated CSS

- **Dynamic Container Class**: Customizable CSS class prefix (default: `global-navigation`)

- **Customizable Mobile Breakpoints**: 320px - 1920px range (default: 1200px)

- **Three Hamburger Animations**: Spin, Squeeze, Collapse

- **Three Menu Positions**: Left, Right, Top

- **Two Submenu Behaviors** (mobile only — desktop always uses hover with delay):
    - **Accordion**: Click chevron toggle to expand/collapse with animated chevron rotation
    - **Slide**: Horizontal panel navigation with back buttons (Netflix-style)

- **Desktop Features**:
    - Hover-activated dropdowns with configurable delay (desktop media query only)
    - Smart edge detection (auto-cascades left when near viewport edge)
    - `focus-within` support for keyboard submenu reveal

- **Accessibility (WCAG 2.1 Level AA)**:
    - Full keyboard navigation (Arrow keys, Enter, Escape)
    - Proper ARIA attributes (`aria-haspopup`, `aria-expanded`, `aria-current`)
    - Focus management (menu open/close, submenu transitions)
    - Screen reader optimised with `role="menubar"`, `role="menu"`, `role="menuitem"`

- **ES6 JavaScript**: Modern `class AccessibleNavigation` pattern supporting multiple instances

- **Dual CSS Output**: CSS-nested (CSS tab) and flat individual styles (ETCH JSON)

## Installation

1. Download `etch-wp-menus.zip`
2. Upload via **Plugins > Add New > Upload Plugin**
3. Activate and navigate to **Tools > Etch WP Menus**

## Documentation

- [Quick Reference](docs/QUICK-REFERENCE.md) - CSS classes, JS API, field names
- [Installation Guide](docs/INSTALLATION.md) - Detailed setup instructions
- [Component Props Guide](docs/COMPONENT-PROPS-GUIDE.md) - Data flow for component approach
- [Build Guide](docs/BUILD-GUIDE.md) - Comprehensive architecture reference for developers
- [Build Summary](docs/BUILD-SUMMARY.md) - Original project build overview

## Requirements

- WordPress 5.8+
- PHP 7.4+
- ETCH Theme Builder

## How It Works

### WordPress Data Integration

The plugin hooks into `etch/dynamic_data/option` to inject hierarchical menu data:

```
options.menus.{menu_slug} → array of menu items with state_classes, link_classes, children, etc.
```

WordPress's `_wp_menu_item_classes_by_context()` is called on the frontend to detect current page, parent, and ancestor states.

### Code Generation

The admin UI generates four outputs:

1. **HTML** — ETCH template syntax with loops and conditionals
2. **CSS** — CSS custom properties + native nesting with responsive breakpoints
3. **JavaScript** — ES6 class with slide mode, desktop hover, edge detection, keyboard nav
4. **ETCH JSON** — Complete block tree with embedded styles and base64-encoded script

### ETCH JSON Architecture

The ETCH JSON block tree structure:

```
nav (root block)
  ├── hamburger button (sibling of __menu, stays visible on mobile)
  └── __menu div (slides off-screen on mobile via position:fixed + transform)
      └── ul.__list
          └── loop → menu items
```

Key architectural decisions:

- **Hamburger inside `<nav>` but outside `__menu`** — stays visible when `__menu` slides off-screen
- **`__menu` wrapper** gets `position: fixed` + `transform` on mobile (not the nav)
- **`is-open` class** applied to `__menu` (not `<nav>`)
- **`data-position` and `data-behavior` attributes** on `<nav>` (not modifier classes — ETCH strips extra classes)
- **One style per selector** — ETCH deduplicates styles sharing the same selector

## Support

**Website:** https://bbg.digital
**Email:** support@builtbygeeks.co.uk

## License

GPL v2 or later

## Credits

**Author**: Stuart Davison | BBG Digital
