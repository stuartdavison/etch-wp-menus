# Changelog

All notable changes to this project will be documented in this file.

## [1.3.0] - 2026-02-05

### Changed
- **ETCH JSON Tab**: Now the first tab (recommended method) with "RECOMMENDED" badge
- **Improved ETCH JSON Instructions**: Enhanced visual design with step-by-step guide and benefits explanation
- **Tab Highlighting**: First tab has subtle blue background to indicate recommended approach
- **User Experience**: Streamlined workflow by defaulting to the fastest setup method

### Visual Enhancements
- Added "RECOMMENDED" badge to ETCH JSON tab
- Blue highlighted card with clear instructions
- Numbered steps for easier following
- Benefits callout explaining why ETCH JSON is faster

## [1.2.0] - 2025-02-05

### Fixed
- **Quick Start Guide Code Blocks**: All code blocks now properly close with closing backticks
- **Component Guide Step 4**: Separated code examples with proper spacing and explanations
- **Custom Data Structure**: Moved to its own section after steps with proper heading
- **Step 7 Added**: Testing step added to Component guide for consistency with Direct Loop guide
- **Data Flow Diagram**: Added introductory text before code block
- **JSON Structure**: Fixed double braces {{}} to single braces {} in JSON examples

### Improved
- All steps now consistently use ordered lists (1, 2, 3, 4, 5)
- Code examples properly separated from instructional text
- Better visual hierarchy with clear section breaks
- Consistent formatting between Direct Loop and Component guides

## [1.1.0] - 2025-02-05

### Improved
- **Quick Start Guide Formatting**: Complete restructure with clear step headings and consistent numbered lists
  - Each step now has its own `### Step N:` heading
  - All steps use consistent numbered list formatting (1, 2, 3, 4, 5)
  - Better visual hierarchy for easier scanning
  - Improved readability and user experience
  - Clear separation between Direct Loop and Component approaches

### Maintained
- All features from version 1.0.0
- WordPress menu selection dropdown
- Dynamic menu names
- Component property name customization
- Menu JSON preview
- All navigation features and animations

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
- Quick Start Guide
