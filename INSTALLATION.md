# Etch WP Menus - Installation & Usage Guide

## Table of Contents
1. [Installation](#installation)
2. [First Time Setup](#first-time-setup)
3. [Using Direct Loop Approach](#using-direct-loop-approach)
4. [Using Component Approach](#using-component-approach)
5. [Customization Tips](#customization-tips)
6. [Troubleshooting](#troubleshooting)

---

## Installation

### Method 1: WordPress Admin Upload

1. Download the `etch-wp-menus.zip` file
2. Log in to your WordPress admin dashboard
3. Navigate to **Plugins → Add New**
4. Click **Upload Plugin** at the top of the page
5. Click **Choose File** and select the `etch-wp-menus.zip` file
6. Click **Install Now**
7. Once installed, click **Activate Plugin**

### Method 2: FTP Upload

1. Extract the `etch-wp-menus.zip` file
2. Upload the `etch-wp-menus` folder to `/wp-content/plugins/`
3. Log in to your WordPress admin dashboard
4. Navigate to **Plugins**
5. Find "Etch WP Menus" and click **Activate**

### Verification

After activation, you should see:
- A new menu item under **Tools → Etch WP Menus**
- Version 1.0.0 in the plugins list

---

## First Time Setup

1. Navigate to **Tools → Etch WP Menus**
2. You'll see the navigation builder interface with five main sections:
   - ETCH Implementation Approach
   - Basic Settings
   - Hamburger Animation
   - Mobile Menu
   - Accessibility Features

3. **Choose Your Approach**:
   - **Direct Loop**: Select this if you're using WordPress menus
   - **Component**: Select this if you want a reusable component

4. **Configure Basic Settings**:
   - Set your mobile breakpoint (default: 1200px)
   - This determines when the navigation switches to mobile view

5. Click **Generate Navigation Code**

---

## Using Direct Loop Approach

### When to Use
- Traditional WordPress sites
- You want to manage menus via WordPress Admin
- Automatic synchronization with WordPress menu changes
- Simplest setup

### Setup Steps

#### Step 1: Create WordPress Menu
1. Go to **Appearance → Menus**
2. Click **Create a new menu**
3. Name it "Global Navigation"
4. Click **Create Menu**
5. Add pages, custom links, or categories to your menu
6. Organize menu items (drag and drop to reorder)
7. Create submenus by dragging items slightly to the right
8. Click **Save Menu**

#### Step 2: Generate Code
1. Go to **Tools → Etch WP Menus**
2. Select **Direct Loop** approach
3. Configure your settings:
   - Mobile breakpoint: 1200px (or your preference)
   - Hamburger animation: Spin (or choose another)
   - Menu position: Left (or your preference)
   - Enable desired features
4. Click **Generate Navigation Code**

#### Step 3: Copy to ETCH
1. **HTML Tab**: Click "Copy Code" and paste into ETCH → HTML Panel
2. **CSS Tab**: Click "Copy Code" and paste into ETCH → CSS Panel
3. **JavaScript Tab**: Click "Copy Code" and paste into ETCH → Settings → Custom Code → JavaScript

#### Step 4: Test
1. Preview your site
2. Test desktop navigation
3. Resize browser to test mobile breakpoint
4. Test hamburger menu functionality
5. Test submenu navigation

### Example WordPress Menu Structure
```
Home
About
  ├─ Our Team
  ├─ Our Story
  └─ Contact Us
Services
  ├─ Web Design
  ├─ Development
  └─ Consulting
Portfolio
Blog
Contact
```

This structure will automatically appear in your ETCH navigation!

---

## Using Component Approach

### When to Use
- Headless/decoupled WordPress
- Need multiple navigation instances with different data
- Working with REST API or GraphQL
- Building a design system
- Maximum flexibility

### Setup Steps

#### Step 1: Generate Code
1. Go to **Tools → Etch WP Menus**
2. Select **Component** approach
3. Configure your settings (same options as Direct Loop)
4. Click **Generate Navigation Code**

#### Step 2: Create ETCH Component
1. In ETCH, go to **Components → Add New**
2. Name it "Navigation" (or your preference)
3. Copy the HTML from the plugin and paste into the component
4. Note the Props Schema provided in the HTML output

#### Step 3: Add CSS and JavaScript
1. **CSS**: Copy from plugin and paste into ETCH → CSS Panel
2. **JavaScript**: Copy from plugin and paste into ETCH → Settings → Custom Code → JavaScript

#### Step 4: Use the Component
When using the component, pass menu data as props:

```html
<Navigation menuItems={menuData} />
```

#### Props Schema
The component expects this data structure:

```json
{
  "menuItems": [
    {
      "label": "Home",
      "url": "/",
      "active": true,
      "children": []
    },
    {
      "label": "About",
      "url": "/about",
      "active": false,
      "children": [
        {
          "label": "Team",
          "url": "/about/team",
          "active": false
        },
        {
          "label": "Story",
          "url": "/about/story",
          "active": false
        }
      ]
    }
  ]
}
```

#### Example: Using with REST API
```javascript
// Fetch menu data from WordPress REST API
fetch('/wp-json/wp/v2/menus/global-navigation')
  .then(response => response.json())
  .then(menuData => {
    // Transform and pass to component
    const transformedData = transformMenuData(menuData);
    renderNavigation(transformedData);
  });
```

---

## Customization Tips

### Changing Colors

In the CSS, find and modify these color variables:

```css
/* Desktop menu link color */
.global-nav__menu-link {
  color: #2c3338; /* Change this */
}

/* Desktop menu link hover color */
.global-nav__menu-link:hover {
  color: #0073aa; /* Change this */
}

/* Active menu item color */
.global-nav__menu-link.is-active {
  color: #0073aa; /* Change this */
}

/* Hamburger line color */
.global-nav__hamburger-line {
  background-color: #2c3338; /* Change this */
}
```

### Adjusting Mobile Menu Width

For left/right slide menus:

```css
@media (max-width: 1200px) {
  .global-nav__menu {
    width: 300px; /* Change this - try 280px, 320px, 400px */
  }
}
```

### Changing Animation Speed

```css
/* Hamburger animation */
.global-nav__hamburger-line {
  transition: all 0.3s linear; /* Change 0.3s to 0.2s, 0.4s, etc. */
}

/* Menu slide animation */
.global-nav__menu {
  transition: transform 0.3s ease; /* Change 0.3s and ease */
}
```

### Adding a Logo

Add this HTML before the hamburger button:

```html
<a href="/" class="global-nav__logo">
  <img src="/path/to/logo.png" alt="Your Logo">
</a>
```

Add this CSS:

```css
.global-nav__logo {
  img {
    height: 40px;
    width: auto;
  }
}
```

### Sticky Navigation

Add this CSS to make the navigation sticky:

```css
.global-nav {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
```

---

## Troubleshooting

### Navigation Not Showing

**Check:**
1. Is the HTML in ETCH's HTML Panel?
2. Is the CSS in ETCH's CSS Panel?
3. Did you save your changes in ETCH?
4. Clear browser cache and hard refresh (Ctrl+Shift+R)

### Mobile Menu Not Working

**Check:**
1. Is the JavaScript in ETCH's JavaScript Panel?
2. Open browser console (F12) and check for errors
3. Verify your breakpoint setting is correct
4. Test at the exact breakpoint width

### Menu Items Not Appearing (Direct Loop)

**Check:**
1. WordPress menu is named exactly "Global Navigation"
2. Menu has items added and saved
3. Menu is assigned to a location (if required by your theme)
4. Menu items are published, not drafts

### Submenu Not Showing

**Check:**
1. Menu items are properly indented in WordPress Admin
2. Parent item has class `has-submenu`
3. Check browser console for JavaScript errors

### Hamburger Animation Not Working

**Check:**
1. Correct animation type is selected
2. JavaScript is properly loaded
3. No CSS conflicts with `.is-active` class
4. Browser supports CSS transforms

### Click Outside Not Closing Menu

**Check:**
1. "Click outside menu" is enabled in settings
2. JavaScript has no errors in console
3. No other scripts are preventing event propagation

### Focus Trap Not Working

**Check:**
1. "Focus trap in mobile menu" is enabled
2. Menu contains focusable elements (links, buttons)
3. No JavaScript errors in console

---

## Getting Help

If you encounter issues not covered here:

1. Check browser console for errors (F12 → Console)
2. Verify all code was copied correctly
3. Test in different browsers
4. Disable other plugins to check for conflicts
5. Contact support at https://bbg.digital/support

---

## Best Practices

### Performance
- Keep menu structure simple (max 2 levels deep)
- Limit top-level items to 5-7 for best UX
- Test on mobile devices, not just desktop

### Accessibility
- Always keep accessibility features enabled
- Test with keyboard navigation (Tab, Enter, Esc)
- Use descriptive link text, not "Click here"
- Test with a screen reader

### Design
- Choose colors with sufficient contrast (WCAG AA)
- Keep mobile menu width reasonable (280-400px)
- Test hamburger animation on actual mobile devices
- Consider animation preferences (some users prefer reduced motion)

### Maintenance
- When updating WordPress menus, changes appear automatically (Direct Loop)
- Document any custom CSS changes you make
- Test after WordPress or ETCH updates

---

**Last Updated**: February 5, 2026  
**Plugin Version**: 1.0.0  
**Author**: Stuart Davison | BBG Digital
