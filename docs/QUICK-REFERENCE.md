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
**Field Names:** `item.title`, `item.url`, `item.current`, `item.current_parent`, `item.state_classes`, `item.children`, `item.target`, `item.classes`

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
- **Default:** `global-nav`
- **What it does:** CSS class prefix for all BEM selectors

### Hamburger Animations
- **Spin:** Rotates to X shape
- **Squeeze:** Compresses to arrow
- **Collapse:** Stacks vertically
- **Arrow:** Points left/right

### Menu Positions
- **Left:** Slides in from left side
- **Right:** Slides in from right side
- **Top:** Drops down from top
- **Full:** Full-screen overlay

### Submenu Behaviors (Mobile Only)
Desktop always uses hover-reveal regardless of this setting.
- **Always Show:** Submenus expanded by default on mobile
- **Accordion:** Click chevron toggle to expand/collapse
- **Clickable:** Parent links navigate, submenus hidden on mobile

## Generated Code Structure

### HTML
```html
<nav class="global-nav">
  <div class="global-nav__container">
    <button class="global-nav__hamburger">...</button>
    <div class="global-nav__menu">
      <ul class="global-nav__menu-list">
        <!-- Menu items loop here -->
        <li class="global-nav__menu-item has-submenu">
          <a class="global-nav__menu-link">...</a>
          <button class="global-nav__submenu-toggle"></button>
          <ul class="global-nav__submenu">...</ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
```

### CSS Classes
```
.global-nav                    /* Root container */
.global-nav__container         /* Inner wrapper */
.global-nav__hamburger         /* Hamburger button */
.global-nav__hamburger-line    /* Hamburger lines (x3) */
.global-nav__menu              /* Menu wrapper */
.global-nav__menu-list         /* Menu <ul> */
.global-nav__menu-item         /* Menu <li> */
.global-nav__menu-link         /* Menu <a> links */
.global-nav__submenu           /* Submenu <ul> */
.global-nav__submenu-item      /* Submenu <li> */
.global-nav__submenu-link      /* Submenu <a> links */
.global-nav__submenu-toggle    /* Accordion chevron button */

/* State classes (applied via item.state_classes) */
.is-current                    /* Current page */
.is-current-parent             /* Ancestor of current page */
.is-active                     /* Active state (on links) */
.is-open                       /* Open menu/submenu */
.has-submenu                   /* Item with children */
```

### JavaScript API
```javascript
globalNav.init()                       // Initialize
globalNav.toggleMenu()                 // Toggle mobile menu
globalNav.lockScroll()                 // Lock body scroll
globalNav.unlockScroll()               // Unlock body scroll
globalNav.trapFocus()                  // Enable focus trap
globalNav.releaseFocus()               // Disable focus trap
globalNav.setupSubmenuAccordion()      // Bind accordion toggle buttons
```

## Quick Customization

### Colors
```css
.global-nav__menu-link { color: #2c3338; }
.global-nav__menu-link:hover { color: #0073aa; }
```

### Mobile Menu Background
No default background is set. Add your own:
```css
@media (max-width: 1200px) {
  .global-nav__menu { background: white; }
}
```

### Mobile Menu Width
```css
@media (max-width: 1200px) {
  .global-nav__menu { width: 300px; }
}
```

### Sticky Navigation
```css
.global-nav {
  position: sticky;
  top: 0;
  z-index: 1000;
}
```

### Change Hamburger Color
```css
.global-nav__hamburger-line {
  background-color: #your-color;
}
```

### Change Chevron Color
```css
.global-nav__submenu-toggle::after {
  border-color: #your-color;
}
```

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
**Version:** 2.0.0
**License:** GPL v2 or later
