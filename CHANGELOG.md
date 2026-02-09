# Changelog

All notable changes to this project will be documented in this file.

## [3.0.0] - 2026-02-09

### Breaking Changes
- **Default class prefix**: Changed from `global-nav` to `global-navigation`
- **HTML structure**: `__menu` wrapper div re-introduced around `<ul>` — this is the panel that slides on mobile. Hamburger is a sibling of `__menu` inside `<nav>`.
- **Hamburger position**: Inside `<nav>` as first child, positioned absolutely. Sibling of `__menu` so it stays visible when `__menu` slides off-screen.
- **Class names renamed**: `__menu-list` > `__list`, `__menu-item` > `__item`, `__menu-link` > `__link`, `__submenu` > `__sub-menu`, `__submenu-item` > `__item` (shared), `__submenu-link` > `__link` (shared)
- **State classes changed**: `is-current` removed from `state_classes`, `is-current-parent` > `current-parent`; new `link_classes` field with `current-page`
- **Removed hamburger animation**: Arrow
- **Removed menu position**: Full Overlay / Fullscreen
- **Removed submenu behaviors**: Always Show, Clickable
- **JavaScript pattern**: IIFE object literal > ES6 `class AccessibleNavigation`
- **No modifier classes on `<nav>`**: ETCH strips extra classes. Position and behaviour stored as `data-position` and `data-behavior` attributes instead.
- **`is-open` on `__menu`**: The `.is-open` class is now applied to the `__menu` wrapper div (not `<nav>`), since `__menu` is what slides.

### Added
- **`item.link_classes`**: New pre-computed field for `<a>` elements containing `current-page` when item is the active page
- **Slide submenu mode**: Horizontal panel navigation with back buttons — JS builds flat panels from nested HTML at mobile breakpoint
- **CSS Custom Properties**: All styling controlled via `:root` tokens (`--menu-clr-text`, `--menu-clr-text-accent`, `--menu-clr-bg`, `--menu-padding-x`, `--menu-padding-y`, `--menu-gap`, `--menu-toggle-size`, etc.)
- **Desktop hover with delay**: `mouseenter`/`mouseleave` with configurable timeout, sibling close logic
- **Edge detection**: JS checks nested submenus against viewport width, adds `.cascade-left` class for left-cascade
- **Full keyboard navigation**: WCAG 2.1 Level AA — ArrowDown/Up navigate items, ArrowRight opens submenu, ArrowLeft closes, Escape closes menu
- **`focus-within` submenu reveal**: Desktop submenus also revealed via `:focus-within` for keyboard accessibility
- **`data-level` attributes**: Each `<li>` gets `data-level="1"`, `data-level="2"`, etc.
- **`__submenu-icon` span**: Explicit chevron element inside toggle button (replaces CSS `::after` pseudo-element)
- **`__back` / `__back-button` / `__back-icon`**: Slide mode back navigation elements
- **`data-position` and `data-behavior` attributes**: On `<nav>` element (replaces modifier classes that ETCH strips)
- **`__menu` wrapper div**: Wraps `<ul>`, receives `position: fixed` + `transform` on mobile. Hamburger is a sibling.
- **`id` attribute on nav**: Enables `aria-controls` linkage from hamburger
- **`.sr-only` utility class**: Screen reader only styles in CSS output
- **`body.menu-open`**: Scroll lock utility class in CSS output
- **`role="menu"`**: Added to submenu `<ul>` elements
- **Multiple instance support**: ES6 class pattern allows multiple navigations on same page
- **Responsive panel rebuild**: Slide mode panels automatically removed/rebuilt when resizing across breakpoint
- **Desktop sub-menu styles media-query wrapped**: Hover dropdowns, positioning, and cascade styles are inside `@media (min-width)` so they don't leak into mobile

### Changed
- **`state_classes` format**: Now contains `has-submenu current-parent` (removed `is-current`, changed `is-current-parent` to `current-parent`)
- **CSS architecture**: Switched from hardcoded values to CSS custom properties throughout
- **Submenu indentation**: Mobile submenus use `padding-left: 40px` / `70px` instead of dash `::before` pseudo-element
- **Accordion open class**: Changed from `.is-open` on `<li>` to `.__item--submenu-open` (BEM modifier, JS-managed)
- **ETCH JSON block tree**: `nav > [hamburger, __menu > ul > loop > items]` — hamburger is first child of nav, sibling of `__menu`
- **Version**: Bumped to 3.0.0

### ETCH-Specific Discoveries
- **ETCH deduplicates styles by selector**: Only the LAST style with a given selector survives. All CSS for a selector must be combined into ONE style entry.
- **ETCH wraps `css` inside `selector`**: Creates descendant selectors. Plain properties target the element itself; nested selectors target descendants.
- **ETCH strips extra classes**: Modifier classes on elements get removed. Use `data-*` attributes instead.
- **CSS `&` nesting not supported**: ETCH outputs `&` literally. Use full BEM selectors.

## [2.0.0] - 2026-02-06

### Added
- **ETCH JSON Block Tree**: Complete rewrite from `etch/custom-html` to proper `etch/element`, `etch/loop`, `etch/condition`, `etch/text` blocks — fully editable in ETCH Structure Panel
- **Pre-computed `state_classes`**: BEM modifier classes (`is-current`, `is-current-parent`, `has-submenu`) computed in PHP data layer because `{#if}` does not work inside ETCH block tree attributes
- **`is-current-parent` highlighting**: Current page ancestors now highlighted with the same colour as current page items, on both desktop and mobile
- **Dynamic `container_class`**: Customizable CSS class prefix field (default: `global-nav`)
- **Accordion chevron toggle**: Dedicated `<button class="__submenu-toggle">` with animated CSS chevron (rotates on open/close), replaces previous link-hijacking approach
- **Submenu dash inset**: Mobile submenu links prefixed with em-dash via `::before` pseudo-element for visual hierarchy
- **ETCH flat styles**: Individual style objects for ETCH JSON output alongside SCSS-nested CSS tab
- **Hamburger animation styles**: Flat ETCH style rules for all four hamburger animations
- **Mobile responsive styles**: Complete `@media` block as flat ETCH style for responsive behaviour
- **WordPress native current-page detection**: Uses `_wp_menu_item_classes_by_context()` instead of manual `get_queried_object_id()` comparison
- **`sanitize_prop_name()`**: Preserves camelCase for ETCH component property names (replaces `sanitize_key()` which lowercased everything)
- **Documentation**: `docs/BUILD-GUIDE.md` comprehensive architecture reference for AI agent handoff

### Changed
- **Mobile UI**: Removed all default background colours from mobile menu and submenus — ETCH users control backgrounds
- **Drop shadow**: Box shadow now only appears when menu `.is-open`, preventing bleed when panel is off-screen
- **Menu alignment**: Mobile menu panels now start below hamburger (`top: 60px`, `height: calc(100vh - 60px)`) instead of covering the full viewport
- **Full overlay**: Changed from centred content to top-aligned with padding offset
- **Accordion JS**: Toggle targets `.{cls}__submenu-toggle` button instead of `.has-submenu > a`, keeping parent links fully navigable
- **Submenu behaviour scope**: Settings (always/accordion/clickable) only affect mobile — desktop always uses hover-reveal
- **ETCH loop variable**: All nested loops use `item` as iterator (ETCH scopes automatically), not `child`/`subchild`
- **`conditionString`**: Moved to top-level attr on `etch/condition` blocks (was incorrectly nested inside `condition` object)
- **Version**: Bumped to 2.0.0
- **Documentation**: Moved QUICK-REFERENCE.md, INSTALLATION.md, BUILD-SUMMARY.md, COMPONENT-PROPS-GUIDE.md to `docs/` folder
- **README.md**: Complete rewrite reflecting v2.0.0 features and architecture

### Fixed
- **Conditional classes**: All `<li>` elements were receiving every state class because `{#if}` doesn't work in ETCH block attributes — fixed with `{item.state_classes}` interpolation
- **Submenu hover on desktop**: Added missing hover/focus styles to ETCH flat styles
- **Hamburger not working**: Added mobile responsive styles as flat ETCH styles
- **Duplicate hamburger button**: Documented that ETCH's own `etch-burger` element is separate; bumped our hamburger z-index to 1000
- **"Always show" behaviour**: Was collapsed when it should be expanded — added `submenu_behavior` branching
- **Submenu behaviour on desktop**: Was incorrectly applying mobile-only settings to desktop — reverted desktop to always hover-reveal

## [1.3.0] - 2026-02-05

### Changed
- **ETCH JSON Tab**: Now the first tab (recommended method) with "RECOMMENDED" badge
- **Improved ETCH JSON Instructions**: Enhanced visual design with step-by-step guide and benefits explanation
- **Tab Highlighting**: First tab has subtle blue background to indicate recommended approach
- **User Experience**: Streamlined workflow by defaulting to the fastest setup method

## [1.2.0] - 2025-02-05

### Fixed
- **Quick Start Guide Code Blocks**: All code blocks now properly close with closing backticks
- **Component Guide Step 4**: Separated code examples with proper spacing and explanations
- **Custom Data Structure**: Moved to its own section after steps with proper heading
- **Step 7 Added**: Testing step added to Component guide for consistency with Direct Loop guide
- **Data Flow Diagram**: Added introductory text before code block
- **JSON Structure**: Fixed double braces to single braces in JSON examples

## [1.1.0] - 2025-02-05

### Improved
- **Quick Start Guide Formatting**: Complete restructure with clear step headings and consistent numbered lists

## [1.0.0] - 2025-02-05

### Added
- Initial release
- WordPress menu selection dropdown
- Dynamic menu names in generated code
- Component property name customization
- Menu JSON preview feature
- ETCH data integration via `etch/dynamic_data/option` filter hook
- Two implementation approaches (Direct Loop & Component)
- Customizable mobile breakpoint (320-1920px)
- Four hamburger animations (Spin, Squeeze, Collapse, Arrow)
- Four menu positions (Left, Right, Top, Full Overlay)
- Three submenu behaviors (Always Show, Accordion, Clickable)
- Accessibility features (Focus trap, Scroll lock, ARIA labels, Keyboard navigation)
- Multiple close methods (Hamburger, Outside click, ESC key)
- Fully nested CSS architecture (BEM methodology)
- Copy-to-clipboard functionality
