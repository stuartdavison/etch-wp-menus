# Etch WP Menus

Generate customizable navigation code for the ETCH theme builder with mobile breakpoints, accordion submenus, and comprehensive accessibility.

## Description

Etch WP Menus is a WordPress plugin that provides an intuitive admin interface for generating professional navigation code tailored for the ETCH theme builder by Digital Gravy. It outputs four code formats: HTML, CSS, JavaScript, and ETCH JSON (a complete block tree with styles and script ready to paste into ETCH's Structure Panel).

## Version

**Current:** 2.0.0

## Features

- **Two Implementation Approaches**:
  - **Direct Loop**: Binds to WordPress menus via `{#loop options.menus.{slug} as item}`
  - **Component**: Reusable components via `{#loop props.menuItems as item}`

- **ETCH JSON Block Tree**: Generates complete `etch/element`, `etch/loop`, `etch/condition`, and `etch/text` blocks — fully editable in the ETCH Structure Panel

- **Pre-computed State Classes**: `item.state_classes` provides BEM modifier classes (`is-current`, `is-current-parent`, `has-submenu`) for each menu item

- **Dynamic Container Class**: Customizable CSS class prefix (default: `global-nav`)

- **Customizable Mobile Breakpoints**: 320px - 1920px range (default: 1200px)

- **Four Hamburger Animations**: Spin, Squeeze, Collapse, Arrow

- **Four Menu Positions**: Left slide, Right slide, Top dropdown, Full overlay

- **Three Submenu Behaviors** (mobile only — desktop always uses hover):
  - **Always Show**: Submenus expanded by default
  - **Accordion**: Click chevron toggle to expand/collapse (with animated chevron)
  - **Clickable**: Parent links navigate, submenus hidden on mobile

- **Mobile UI**:
  - No default background colours (user-controlled via ETCH)
  - Box shadow only appears when menu is open (no bleed when off-screen)
  - Menu aligned below hamburger with top offset
  - Submenu links inset with dash prefix
  - Chevron toggle button for accordion mode

- **Accessibility**: Focus trap, scroll lock, ARIA labels, keyboard navigation, ESC to close

- **Dual CSS Output**: SCSS-nested (CSS tab) and flat individual styles (ETCH JSON)

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
options.menus.{menu_slug} → array of menu items with children, state_classes, etc.
```

WordPress's `_wp_menu_item_classes_by_context()` is called on the frontend to detect current page, parent, and ancestor states.

### Code Generation

The admin UI generates four outputs:

1. **HTML** — ETCH template syntax with loops and conditionals
2. **CSS** — SCSS-nested BEM styles with responsive breakpoints
3. **JavaScript** — Vanilla JS IIFE with hamburger, accordion, scroll lock, focus trap
4. **ETCH JSON** — Complete block tree with embedded styles and base64-encoded script

## Support

**Website:** https://bbg.digital
**Email:** support@bbg.digital

## License

GPL v2 or later

## Credits

**Author**: Stuart Davison | BBG Digital
