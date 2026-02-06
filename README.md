# Etch WP Menus

Generate customizable navigation code for the ETCH theme builder with mobile breakpoints and nested CSS.

## Description

Etch WP Menus is a WordPress plugin that provides an intuitive interface for generating professional navigation code tailored specifically for the ETCH theme builder. With support for mobile breakpoints, multiple animation styles, and comprehensive accessibility features, this plugin streamlines the process of creating beautiful, functional navigation menus.

## Features

- **Two Implementation Approaches**:
  - **Direct Loop**: Binds directly to WordPress menus using `{#loop options.menus.global_navigation}`
  - **Component**: Creates reusable components with `{#loop props.menuItems as item}`

- **Customizable Mobile Breakpoints**: Set custom breakpoints (320-1920px) for when navigation switches to mobile view

- **Four Hamburger Animations**:
  - Spin (rotate to X)
  - Squeeze (compress to arrow)
  - Collapse (vertical stack)
  - Arrow (left/right point)

- **Four Menu Positions**:
  - Left slide
  - Right slide
  - Top dropdown
  - Full overlay

- **Submenu Behaviors**:
  - Always show
  - Accordion
  - Clickable

- **Accessibility Features**:
  - Focus trap in mobile menu
  - Body scroll lock
  - ARIA labels
  - Keyboard navigation support

- **Professional Code Output**:
  - Fully nested CSS (BEM methodology)
  - Clean, commented HTML
  - Vanilla JavaScript (no dependencies)
  - Copy-to-clipboard functionality

## Installation

1. Download the plugin ZIP file
2. In WordPress Admin, go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP file and click **Install Now**
4. Click **Activate Plugin**
5. Navigate to **Tools → Etch WP Menus**

## Usage

### Quick Start

1. **Configure Settings**:
   - Choose your implementation approach (Direct Loop or Component)
   - Set your mobile breakpoint
   - Select hamburger animation style
   - Choose menu position and behavior

2. **Generate Code**:
   - Click **Generate Navigation Code**
   - Four tabs will appear with your code: HTML, CSS, JavaScript, and Quick Start

3. **Copy to ETCH**:
   - Copy the HTML to ETCH's HTML Panel
   - Copy the CSS to ETCH's CSS Panel
   - Copy the JavaScript to ETCH's JavaScript Panel

### Direct Loop Approach

Best for traditional WordPress sites where you want navigation to automatically sync with WordPress menus.

**Steps**:
1. Go to WordPress Admin → Appearance → Menus
2. Create a menu called "Global Navigation"
3. Add menu items and save
4. Generate code using "Direct Loop" approach
5. Paste code into ETCH panels

The navigation will automatically pull from your WordPress menu.

### Component Approach

Best for headless/decoupled architectures where you want reusable components with flexible data sources.

**Steps**:
1. Generate code using "Component" approach
2. Create a new component in ETCH
3. Paste the HTML code
4. Use the provided props schema
5. Pass menu data when using the component

Example:
```html
<Navigation menuItems={customMenuData} />
```

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- ETCH Theme (any version)

## CSS Architecture

All CSS is fully nested under `.global-nav` to avoid conflicts:

```css
.global-nav {
  &__container { }
  &__hamburger { }
  &__menu { }
  &__menu-list { }
  &__menu-item { }
  &__menu-link { }
  &__submenu { }
}
```

This ensures zero conflicts with existing styles in your ETCH theme.

## JavaScript Features

The generated JavaScript includes:
- Hamburger menu toggle
- Body scroll lock (when menu is open)
- Focus trap (keyboard accessibility)
- Click outside to close
- ESC key to close
- Submenu accordion (on mobile)
- ARIA attribute management

All features are modular and can be enabled/disabled via the admin interface.

## Customization

After generating the code, you can customize:
- Colors in the CSS
- Font sizes and weights
- Spacing and padding
- Transition speeds
- Menu width (for side menus)

The code is clean, well-commented, and easy to modify.

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile Safari (iOS)
- Chrome Mobile (Android)

## Accessibility

The generated navigation includes:
- Proper ARIA labels and roles
- Keyboard navigation support
- Focus management
- Screen reader friendly
- WCAG AA compliant color contrast

## Support

For support, feature requests, or bug reports:
- Visit: https://bbg.digital/support
- Email: support@bbg.digital

## Changelog

### Version 1.0.0
- Initial release
- Two implementation approaches (Direct Loop and Component)
- Four hamburger animations
- Four menu positions
- Customizable mobile breakpoint
- Full accessibility features
- Copy-to-clipboard functionality

## Credits

**Author**: Stuart Davison  
**Plugin URI**: https://bbg.digital  
**License**: GPL v2 or later

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```
