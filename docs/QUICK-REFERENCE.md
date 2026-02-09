# Etch WP Menus - Quick Reference

## Installation
```bash
1. Upload etch-wp-menus.zip to WordPress
2. Activate plugin
3. Go to Tools > Etch WP Menus
```

## Two Approaches

### Direct Loop
**Best for:** WordPress sites with WordPress menus
**Data Source:** `options.menus.{menu_slug}`
**Field Names:** `item.title`, `item.url`, `item.current`, `item.current_parent`, `item.state_classes`, `item.link_classes`, `item.children`, `item.target`, `item.classes`

### Component
**Best for:** Headless sites, reusable components
**Data Source:** `props.menuItems` (customizable)
**Field Names:** Same as Direct Loop

## Settings Reference

### Mobile Breakpoint
- **Range:** 320px - 1920px
- **Default:** 1200px
- **What it does:** Width at which navigation switches to mobile view

### Container Class
- **Default:** `global-navigation`
- **What it does:** CSS class prefix for all BEM selectors

### Hamburger Animations
- **Spin:** Rotates to X shape
- **Squeeze:** Compresses to X
- **Collapse:** Stacks vertically to X

### Menu Positions
- **Left:** Slides in from left side
- **Right:** Slides in from right side
- **Top:** Drops down from top

### Submenu Behaviors (Mobile Only)
Desktop always uses hover-reveal with delay regardless of this setting.
- **Accordion:** Click chevron toggle to expand/collapse (with animated chevron rotation)
- **Slide:** Horizontal panel navigation with back buttons (Netflix-style)

## Generated Code Structure

### ETCH JSON Block Tree
```
nav.global-navigation (root block, data-position, data-behavior attributes)
  ├── button.__hamburger (absolutely positioned, stays visible on mobile)
  └── div.__menu (slides off-screen on mobile via position:fixed + transform)
      └── ul.__list
          └── loop → menu items
              └── li.__item
                  ├── a.__link
                  ├── condition → button.__submenu-toggle
                  └── condition → ul.__sub-menu → loop (recursive)
```

### CSS Classes
```
.global-navigation                    /* Root nav element */
.global-navigation__hamburger         /* Hamburger button (inside nav, sibling of __menu) */
.global-navigation__hamburger-line    /* Hamburger lines (x3) */
.global-navigation__menu              /* Menu panel wrapper (slides on mobile) */
.global-navigation__list              /* Menu <ul> */
.global-navigation__item              /* Menu <li> (shared all levels) */
.global-navigation__link              /* Menu <a> (shared all levels) */
.global-navigation__sub-menu          /* Submenu <ul> */
.global-navigation__submenu-toggle    /* Chevron toggle button */
.global-navigation__submenu-icon      /* Chevron icon span */
.global-navigation__back              /* Back button wrapper (slide mode) */
.global-navigation__back-button       /* Back button element */
.global-navigation__back-icon         /* Back chevron icon */

/* Data attributes on <nav> (not modifier classes — ETCH strips classes) */
data-position="left|right|top"        /* Menu position */
data-behavior="accordion|slide"       /* Submenu behaviour */

/* Utility classes (on <li> via item.state_classes) */
.has-submenu                          /* Item with children */
.current-parent                       /* Ancestor of current page */

/* Utility classes (on <a> via item.link_classes) */
.current-page                         /* Current page link */

/* JS-managed classes */
.is-open                              /* Menu panel open (on __menu) */
.is-active                            /* Hamburger active state */
.__item--submenu-open                 /* Accordion/desktop open submenu */
.cascade-left                         /* Edge detection: cascade left */
.menu-open                            /* Body scroll lock */
```

### JavaScript API (ES6 Class)
```javascript
// Automatically initialized on DOMContentLoaded
const nav = new AccessibleNavigation(navElement);

// Key properties
nav.nav                          // The <nav> element
nav.menu                         // The __menu wrapper div
nav.hamburger                    // The hamburger button

// Instance methods
nav.toggleMenu()                 // Toggle mobile menu
nav.openMenu()                   // Open mobile menu (adds .is-open to __menu)
nav.closeMenu()                  // Close mobile menu
nav.checkMobile()                // Check if below breakpoint
nav.setupSlideMode()             // Build/rebuild slide panels
nav.setupDesktopHover()          // Setup hover with delay
nav.checkSubMenuEdges()          // Desktop edge detection
nav.setupKeyboardNavigation()    // Full WCAG keyboard nav
nav.handleAccordionToggle(e)     // Toggle accordion submenu
```

## Quick Customization

### Colors (via CSS Custom Properties)
```css
:root {
  --menu-clr-text: #2c3338;
  --menu-clr-text-accent: #0073aa;
  --menu-clr-bg: #ffffff;
  --menu-clr-bg-accent: #f0f0f1;
  --menu-clr-bg-hover: #f9f9f9;
}
```

### Mobile Menu Width
```css
:root {
  --menu-mobile-width: 320px;
}
```

### Spacing
```css
:root {
  --menu-padding-x: 1.25rem;
  --menu-padding-y: 0.75rem;
  --menu-gap: 0.5rem;
  --menu-padding-top: 80px;
}
```

### Transitions
```css
:root {
  --menu-transition-duration: 0.2s;
  --menu-transition-easing: ease-in-out;
  --menu-hover-delay: 0.15s;
}
```

### Sticky Navigation
```css
.global-navigation {
  position: sticky;
  top: 0;
  z-index: 1000;
}
```

## ETCH-Specific Gotchas

1. **Selector deduplication**: ETCH only renders the LAST style with a given selector. Never create two styles with the same selector.
2. **CSS wrapping**: ETCH wraps `css` inside `selector`, creating descendant selectors. Plain properties target the element itself.
3. **No `&` nesting**: ETCH outputs `&` literally. Use full BEM selectors in `css` fields.
4. **Class stripping**: ETCH controls the `class` attribute via styles. Extra classes on elements get stripped. Use `data-*` attributes for runtime state.

## Hooks & Filters

### `etch/dynamic_data/option`
The plugin uses this ETCH filter to inject menu data:
```php
add_filter( 'etch/dynamic_data/option', array( $this, 'add_menus_to_etch' ) );
// Makes options.menus.{slug} available in ETCH templates
```

## File Structure
```
etch-wp-menus/
├── etch-wp-menus.php              # Main plugin file
├── includes/
│   ├── class-navigation-generator.php  # Code generation engine
│   └── class-admin-page.php       # Admin handler
├── assets/
│   ├── css/admin-builder.css      # Admin styles
│   └── js/admin-builder.js        # Admin JavaScript
├── templates/
│   └── admin-page.php             # Admin UI template
├── docs/
│   ├── QUICK-REFERENCE.md         # This file
│   ├── INSTALLATION.md            # Setup guide
│   ├── COMPONENT-PROPS-GUIDE.md   # Component data flow
│   ├── BUILD-GUIDE.md             # Architecture reference
│   └── BUILD-SUMMARY.md           # Original build overview
├── README.md
└── CHANGELOG.md
```

## Support

**Website:** https://bbg.digital
**Support:** support@bbg.digital
**Version:** 3.0.0
**License:** GPL v2 or later
