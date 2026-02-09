# Component Props Setup Guide

## Understanding Component Props in ETCH

When using the **Component Approach**, you're creating a reusable navigation component that receives data through props. Here's how it works:

## Complete Data Flow

```
WordPress Database (wp_terms, wp_term_relationships)
    ↓
Plugin Hook: etch/dynamic_data/option filter
    ↓
Plugin adds: options.menus.{menu_slug} = [menu data array]
    ↓
ETCH receives: options.menus.primary_menu (available in loops)
    ↓
Component Property: menuItems = {options.menus.primary_menu}
    ↓
Component HTML: {#loop props.menuItems as item}
    ↓
Rendered Navigation
```

## How The Plugin Makes Menus Available

The plugin uses ETCH's `etch/dynamic_data/option` filter hook to inject WordPress menu data:

```php
add_filter('etch/dynamic_data/option', function( $data ) {
    // Get all WordPress menus
    $menus = wp_get_nav_menus();
    
    // Add each menu as options.menus.{menu_slug}
    foreach ($menus as $menu) {
        // IMPORTANT: ETCH requires underscores, not hyphens
        $menu_slug = sanitize_for_etch($menu->name);
        // "Primary Menu" → "primary_menu"
        // "Footer-Navigation" → "footer_navigation"
        
        $data['menus'][$menu_slug] = [
            /* hierarchical menu array */
        ];
    }
    
    return $data;
});
```

**Critical**: ETCH property names can only contain letters, digits, and underscores. The plugin automatically converts menu names:
- Spaces → underscores
- Hyphens → underscores  
- Special characters → removed

Examples:
- "Primary Menu" → `options.menus.primary_menu` ✅
- "Footer-Navigation" → `options.menus.footer_navigation` ✅
- "Main Nav (2024)" → `options.menus.main_nav_2024` ✅

This makes `options.menus.{menu_slug}` available throughout ETCH!

## Plugin Configuration

### Component Property Name Field
- **Default**: `menuItems`
- **Generates**: `{#loop props.menuItems as item}`
- **Customizable**: Change to any valid property name

### Auto-Generated Menu Reference
- Selected WordPress menu is auto-converted to slug format
- "Primary Menu" becomes `options.menus.primary_menu`
- "Footer Navigation" becomes `options.menus.footer_navigation`
- Used as the component property's default value

## ETCH Setup

1. **Create component in ETCH**
2. **Add property**: `menuItems` (or your custom name)
3. **Set default value**: `{options.menus.primary_menu}`
4. **Paste generated HTML**

The property value `{options.menus.primary_menu}` tells ETCH: "Get the menu data that the plugin added"

## Why Props Instead of Direct Reference?

**Component with Props (Reusable)**:
```html
{#loop props.menuItems as item}
```
Can be used with ANY data source - WordPress menus OR custom data

**Direct Reference (Not Reusable)**:
```html
{#loop options.menus.primary_menu as item}
```
Hardcoded to one specific menu - works but not reusable

## Data Structure

The plugin provides this structure to ETCH:

```json
{
  "menus": {
    "primary_menu": [
      {
        "id": 123,
        "title": "Home",
        "url": "/",
        "target": "",
        "classes": "menu-item-home current-menu-item",
        "current": true,
        "current_parent": false,
        "state_classes": "",
        "link_classes": "current-page",
        "children": []
      },
      {
        "id": 124,
        "title": "About",
        "url": "/about",
        "target": "",
        "classes": "menu-item menu-item-has-children",
        "current": false,
        "current_parent": false,
        "state_classes": "has-submenu",
        "link_classes": "",
        "children": [
          {
            "id": 125,
            "title": "Team",
            "url": "/about/team",
            "target": "",
            "classes": "",
            "current": false,
            "current_parent": false,
            "state_classes": "",
            "link_classes": "",
            "children": []
          }
        ]
      }
    ]
  }
}
```

### Key Fields
- **`state_classes`**: Pre-computed utility classes for `<li>` elements (`has-submenu`, `current-parent`) — used directly in ETCH block tree attributes via `{item.state_classes}`
- **`link_classes`**: Pre-computed utility classes for `<a>` elements (`current-page`) — used via `{item.link_classes}`
- **`current`**: Boolean — true if this is the current page
- **`current_parent`**: Boolean — true if this is an ancestor of the current page
- **`children`**: Array of child menu items (same structure, recursive)

## Technical Details

### The etch/dynamic_data/option Filter

According to ETCH documentation, this filter extends the `options` key in dynamic data:

```php
add_filter('etch/dynamic_data/option', function( $data ) {
    // $data is the options array
    // Add custom data to it
    $data['custom_key'] = 'custom_value';
    return $data;
});
```

Our plugin uses this to add:
- `$data['menus']['primary_menu']` = array of menu items
- `$data['menus']['footer_navigation']` = array of menu items
- `$data['menus']['{any_menu_slug}']` = array of menu items

### Available in ETCH As

- Direct Loop: `{#loop options.menus.primary_menu as item}`
- Component Prop Default: `{options.menus.primary_menu}`
- Anywhere in ETCH: `{options.menus.primary_menu}`

See full documentation in plugin files.
