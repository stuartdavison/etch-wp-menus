# Changelog

All notable changes to this project will be documented in this file.

## [2.0.0] - 2026-02-06

### Added
- **ETCH JSON Block Tree**: Complete rewrite from `etch/custom-html` to proper `etch/element`, `etch/loop`, `etch/condition`, `etch/text` blocks — fully editable in ETCH Structure Panel
- **Pre-computed `state_classes`**: BEM modifier classes (`is-current`, `is-current-parent`, `has-submenu`) computed in PHP data layer because `{#if}` does not work inside ETCH block tree attributes
- **`is-current-parent` highlighting**: Current page ancestors now highlighted with the same colour as current page items, on both desktop and mobile
- **Dynamic `container_class`**: Customizable CSS class prefix field (default: `global-nav`)
- **Accordion chevron toggle**: Dedicated `<button class="__submenu-toggle">` with animated CSS chevron (rotates on open/close), replaces previous link-hijacking approach
- **Submenu dash inset**: Mobile submenu links prefixed with em-dash (`—`) via `::before` pseudo-element for visual hierarchy
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
