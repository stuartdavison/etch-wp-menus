# Etch WP Menus - Project Build Summary

## Project Overview

**Plugin Name:** Etch WP Menus
**Version:** 3.0.0
**Author:** Stuart Davison
**Original Build Date:** February 5, 2026
**Last Updated:** February 9, 2026
**Status:** Complete and Ready for Production

## What's Included

This WordPress plugin generates professional, customizable navigation code specifically designed for the ETCH theme builder. It provides an admin interface where users can configure every aspect of their navigation menu and receive production-ready HTML, CSS, JavaScript, and ETCH JSON code.

## Architecture

### Core Components

1. **Main Plugin File** (`etch-wp-menus.php`)
   - Plugin registration and initialization
   - Admin menu integration
   - Asset enqueuing
   - AJAX handlers for code generation and menu JSON preview
   - `etch/dynamic_data/option` filter — injects menu data with `state_classes` and `link_classes`

2. **Navigation Generator** (`includes/class-navigation-generator.php`)
   - HTML generation (Direct Loop & Component approaches)
   - CSS generation with CSS custom properties and native nesting
   - JavaScript generation (ES6 `class AccessibleNavigation`)
   - ETCH JSON block tree generation with embedded styles and base64-encoded script

3. **Admin Page Handler** (`includes/class-admin-page.php`)
   - Settings page rendering
   - Default configuration management

4. **Admin Template** (`templates/admin-page.php`)
   - WordPress-native admin UI
   - Toggle switches, radio buttons, form fields
   - Tabbed output interface (ETCH JSON, HTML, CSS, JS)

5. **Frontend Assets**
   - `assets/css/admin-builder.css` — Admin styling
   - `assets/js/admin-builder.js` — Admin interaction and hamburger preview

## Key Features

### Implementation Approaches
- **Direct Loop:** WordPress menu integration via `{#loop options.menus.{slug} as item}`
- **Component:** Reusable component via `{#loop props.menuItems as item}`

### Customization Options
- CSS custom properties (`:root` tokens) for all styling
- Mobile breakpoint (320-1920px, default 1200px)
- Dynamic container class prefix (default: `global-navigation`)
- Three hamburger animations (Spin, Squeeze, Collapse)
- Three menu positions (Left, Right, Top)
- Two submenu behaviours — mobile only (Accordion, Slide)
- Desktop always uses hover-reveal with configurable delay

### Navigation Features
- Hamburger inside `<nav>`, sibling of `__menu` wrapper — stays visible when menu slides
- `__menu` wrapper div gets `position: fixed` + `transform` on mobile (not the nav)
- `data-position` and `data-behavior` attributes on `<nav>` (not modifier classes — ETCH strips them)
- Desktop hover-activated dropdowns with delay (media-query wrapped to prevent mobile leak)
- Smart edge detection (auto-cascades left near viewport edge)
- Accordion mode with animated chevron rotation
- Slide mode with horizontal panel navigation and back buttons
- Pre-computed `state_classes` (for `<li>`) and `link_classes` (for `<a>`)

### Accessibility (WCAG 2.1 Level AA)
- Full keyboard navigation (Arrow keys, Enter, Escape)
- Proper ARIA attributes (`aria-haspopup`, `aria-expanded`, `aria-current`)
- Focus management (menu open/close, submenu transitions)
- Focus trap in mobile menu
- Body scroll lock
- Screen reader optimised with `role="menubar"`, `role="menu"`, `role="menuitem"`

### Code Output
- **HTML** — ETCH template syntax with loops and conditionals
- **CSS** — CSS custom properties + native nesting with responsive breakpoints
- **JavaScript** — ES6 class with slide mode, desktop hover, edge detection, keyboard nav
- **ETCH JSON** — Complete block tree with embedded styles and base64-encoded script

## ETCH JSON Block Tree (v3)

```
nav.global-navigation (root block)
  ├── button.__hamburger (position: absolute, z-index: 1001)
  └── div.__menu (position: fixed + transform on mobile, .is-open slides it in)
      └── ul.__list
          └── loop → li.__item
              ├── a.__link
              ├── condition → button.__submenu-toggle > span.__submenu-icon
              └── condition → ul.__sub-menu → loop (recursive)
```

## ETCH-Specific Constraints Discovered

1. **Selector deduplication**: ETCH only renders the LAST style with a given selector. All CSS for `.global-navigation` must be in ONE style entry (`nav-all`).
2. **CSS wrapping**: ETCH wraps the `css` field inside the `selector`, creating descendant selectors. Plain properties apply to the element itself; nested selectors become descendants.
3. **Class stripping**: ETCH controls the `class` attribute via its styles system. Modifier classes added to elements get stripped. Use `data-*` attributes instead.
4. **No `&` CSS nesting**: ETCH outputs `&` literally. Use full BEM selectors in flat styles.

## File Structure

```
etch-wp-menus/
├── etch-wp-menus.php                    # Main plugin file
├── includes/
│   ├── class-navigation-generator.php   # Code generation engine
│   └── class-admin-page.php             # Admin handler
├── assets/
│   ├── css/
│   │   └── admin-builder.css            # Admin styles
│   └── js/
│       └── admin-builder.js             # Admin JavaScript
├── templates/
│   └── admin-page.php                   # Admin UI template
├── docs/
│   ├── QUICK-REFERENCE.md              # CSS classes, JS API, field names
│   ├── INSTALLATION.md                 # Setup guide
│   ├── COMPONENT-PROPS-GUIDE.md        # Component data flow
│   ├── BUILD-GUIDE.md                  # Architecture reference
│   └── BUILD-SUMMARY.md               # This file
├── README.md
└── CHANGELOG.md
```

## Technical Specifications

### WordPress Requirements
- **Minimum WordPress Version:** 5.8+
- **Minimum PHP Version:** 7.4+
- **Required:** ETCH Theme Builder
- **Required Capabilities:** `manage_options`

### Browser Support
- Chrome, Firefox, Safari, Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

### Security Features
- Nonce verification on AJAX requests
- Capability checks
- Data sanitization
- XSS prevention

## Version History

- **v1.0.0** (Feb 5, 2026) — Initial release
- **v2.0.0** (Feb 6, 2026) — ETCH JSON block tree, pre-computed state_classes, dual CSS architecture
- **v3.0.0** (Feb 9, 2026) — CSS custom properties, ES6 class, slide/accordion modes, __menu wrapper pattern, data attributes, ETCH constraint workarounds

## Support

**Website:** https://bbg.digital
**Email:** support@bbg.digital
**Documentation:** Included in plugin `docs/` directory

## License

GPL v2 or later

---

**Built by Stuart Davison | BBG Digital**
