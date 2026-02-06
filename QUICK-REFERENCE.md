# Etch WP Menus - Quick Reference

## Installation
```bash
1. Upload etch-wp-menus.zip to WordPress
2. Activate plugin
3. Go to Tools → Etch WP Menus
```

## Two Approaches

### Direct Loop
**Best for:** WordPress sites with WordPress menus  
**Data Source:** `options.menus.global_navigation`  
**Field Names:** `item.title`, `item.url`, `item.current`, `item.children`

### Component
**Best for:** Headless sites, reusable components  
**Data Source:** `props.menuItems`  
**Field Names:** `item.label`, `item.url`, `item.active`, `item.children`

## Settings Reference

### Mobile Breakpoint
- **Range:** 320px - 1920px
- **Default:** 1200px
- **What it does:** Width at which navigation switches to mobile view

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

### Submenu Behaviors
- **Always Show:** Submenus visible on hover (desktop only)
- **Accordion:** Click to expand/collapse
- **Clickable:** Parent links are clickable

## Generated Code Structure

### HTML
```html
<nav class="global-nav">
  <div class="global-nav__container">
    <button class="global-nav__hamburger">...</button>
    <div class="global-nav__menu">
      <ul class="global-nav__menu-list">
        <!-- Menu items loop here -->
      </ul>
    </div>
  </div>
</nav>
```

### CSS Classes
```
.global-nav                   /* Root container */
.global-nav__container        /* Inner wrapper */
.global-nav__hamburger        /* Hamburger button */
.global-nav__hamburger-line   /* Hamburger lines */
.global-nav__menu             /* Menu wrapper */
.global-nav__menu-list        /* Menu <ul> */
.global-nav__menu-item        /* Menu <li> */
.global-nav__menu-link        /* Menu links */
.global-nav__submenu          /* Submenu <ul> */
.global-nav__submenu-item     /* Submenu <li> */
.global-nav__submenu-link     /* Submenu links */

/* State classes */
.is-active                    /* Active menu item */
.is-open                      /* Open menu/submenu */
.has-submenu                  /* Parent with children */
```

### JavaScript API
```javascript
globalNav.init()              // Initialize
globalNav.toggleMenu()        // Toggle mobile menu
globalNav.lockScroll()        // Lock body scroll
globalNav.unlockScroll()      // Unlock body scroll
globalNav.trapFocus()         // Enable focus trap
globalNav.releaseFocus()      // Disable focus trap
```

## Quick Customization

### Colors
```css
/* Menu link color */
.global-nav__menu-link {
  color: #2c3338;
}

/* Hover/active color */
.global-nav__menu-link:hover,
.global-nav__menu-link.is-active {
  color: #0073aa;
}
```

### Mobile Menu Width
```css
@media (max-width: 1200px) {
  .global-nav__menu {
    width: 300px; /* Adjust this */
  }
}
```

### Animation Speed
```css
.global-nav__hamburger-line,
.global-nav__menu {
  transition-duration: 0.3s; /* Adjust this */
}
```

## Common Tasks

### Add Logo
```html
<!-- Before hamburger button -->
<a href="/" class="global-nav__logo">
  <img src="/logo.png" alt="Logo">
</a>
```

### Make Navigation Sticky
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

### Add Background to Mobile Menu
```css
@media (max-width: 1200px) {
  .global-nav__menu {
    background: white; /* Change this */
  }
}
```

## Accessibility Features

✅ ARIA labels and roles  
✅ Keyboard navigation  
✅ Focus trap in mobile menu  
✅ Screen reader support  
✅ Body scroll lock  
✅ ESC key to close  

## Browser Support

✅ Chrome, Firefox, Safari, Edge (latest)  
✅ iOS Safari, Chrome Mobile  
✅ IE11 (with transpilation)

## File Structure
```
etch-wp-menus/
├── etch-wp-menus.php              # Main plugin file
├── includes/
│   ├── class-navigation-generator.php
│   └── class-admin-page.php
├── assets/
│   ├── css/
│   │   └── admin-builder.css
│   └── js/
│       └── admin-builder.js
├── templates/
│   └── admin-page.php
├── README.md
└── INSTALLATION.md
```

## Hooks & Filters

Currently no hooks available. Custom hooks may be added in future versions.

## Performance Tips

- Keep menu depth to 2 levels max
- Limit top-level items to 7 or fewer
- Use simple animations for better performance
- Consider lazy-loading for mega menus

## Support

**Website:** https://bbg.digital  
**Support:** support@bbg.digital  
**Version:** 1.0.0  
**License:** GPL v2 or later

---

**Quick Tip:** Always test on real mobile devices, not just browser resize!
