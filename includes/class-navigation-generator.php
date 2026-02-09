<?php
/**
 * Navigation Code Generator
 *
 * @package Etch_WP_Menus
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Etch_Navigation_Generator
 */
class Etch_Navigation_Generator {

    /**
     * The CSS class prefix for the current generation (e.g., 'global-nav', 'footer-navigation')
     *
     * @var string
     */
    private $cls = 'global-navigation';

    /**
     * Get WordPress menu name from settings
     *
     * @param array $settings User settings
     * @return string Menu name (ETCH-compatible with underscores)
     */
    private function get_menu_name( $settings ) {
        if ( isset( $settings['menu_id'] ) && ! empty( $settings['menu_id'] ) ) {
            $menu = wp_get_nav_menu_object( $settings['menu_id'] );
            if ( $menu ) {
                return $this->sanitize_for_etch( $menu->name );
            }
        }
        return 'global_navigation';
    }

    /**
     * Sanitize string for ETCH property names
     *
     * @param string $string String to sanitize
     * @return string ETCH-compatible property name
     */
    public function sanitize_for_etch( $string ) {
        $string = strtolower( $string );
        $string = str_replace( array( ' ', '-' ), '_', $string );
        $string = preg_replace( '/[^a-z0-9_]/', '', $string );
        $string = preg_replace( '/_+/', '_', $string );
        $string = trim( $string, '_' );
        return $string;
    }

    /**
     * Sanitize a component property name, preserving case
     *
     * Unlike sanitize_key() which lowercases, this keeps camelCase intact.
     * Only allows alphanumeric characters and underscores.
     *
     * @param string $name Property name to sanitize
     * @return string Sanitized property name
     */
    private function sanitize_prop_name( $name ) {
        return preg_replace( '/[^a-zA-Z0-9_]/', '', $name );
    }

    /**
     * Get the CSS class prefix from settings
     *
     * @param array $settings User settings
     * @return string CSS class prefix (e.g., 'global-nav')
     */
    private function get_css_class( $settings ) {
        if ( isset( $settings['container_class'] ) && ! empty( trim( $settings['container_class'] ) ) ) {
            return $this->sanitize_css_class( $settings['container_class'] );
        }

        // Default: derive from the selected menu name
        if ( isset( $settings['menu_id'] ) && ! empty( $settings['menu_id'] ) ) {
            $menu = wp_get_nav_menu_object( $settings['menu_id'] );
            if ( $menu ) {
                return $this->sanitize_css_class( $menu->name );
            }
        }

        return 'global-navigation';
    }

    /**
     * Sanitize a string for use as a CSS class (kebab-case)
     *
     * @param string $string Input string
     * @return string CSS-safe kebab-case class name
     */
    private function sanitize_css_class( $string ) {
        $string = strtolower( trim( $string ) );
        $string = str_replace( array( ' ', '_' ), '-', $string );
        $string = preg_replace( '/[^a-z0-9\-]/', '', $string );
        $string = preg_replace( '/-+/', '-', $string );
        $string = trim( $string, '-' );
        return $string;
    }

    /**
     * Check if mobile menu support is enabled
     *
     * @param array $settings User settings
     * @return bool
     */
    private function has_mobile_support( $settings ) {
        return ! empty( $settings['mobile_menu_support'] );
    }

    /**
     * Generate complete HTML structure
     *
     * @param array $settings User settings
     * @return string Generated HTML
     */
    public function generate_html( $settings ) {
        $this->cls = $this->get_css_class( $settings );
        $approach = isset( $settings['approach'] ) ? $settings['approach'] : 'direct';

        if ( 'component' === $approach ) {
            return $this->generate_component_html( $settings );
        }

        return $this->generate_direct_html( $settings );
    }

    /**
     * Generate submenu HTML recursively for direct loop
     *
     * @param int    $depth Current depth level
     * @param int    $max_depth Maximum depth
     * @param string $indent Current indentation
     * @param string $var_name Variable name for current item (item, child, subchild, etc.)
     * @return string HTML code
     */
    private function generate_submenu_html_direct( $depth, $max_depth, $indent, $var_name ) {
        if ( $depth > $max_depth ) {
            return '';
        }

        $cls = $this->cls;
        $next_var = $this->get_depth_var_name( $depth );
        $data_level = $depth + 1;

        $html = "\n{$indent}{#if {$var_name}.children}\n";
        $html .= "{$indent}  <ul class=\"{$cls}__sub-menu\" role=\"menu\">\n";
        $html .= "{$indent}    {#loop {$var_name}.children as {$next_var}}\n";
        $html .= "{$indent}      <li class=\"{$cls}__item {{$next_var}.state_classes}\" role=\"none\" data-level=\"{$data_level}\">\n";

        $html .= "{$indent}        <a href=\"{{$next_var}.url}\"\n";
        $html .= "{$indent}           class=\"{$cls}__link {{$next_var}.link_classes}\"\n";
        $html .= "{$indent}           role=\"menuitem\">\n";
        $html .= "{$indent}          {{$next_var}.title}\n";
        $html .= "{$indent}        </a>";

        // Toggle button for submenu items with children
        if ( $depth < $max_depth ) {
            $html .= "\n{$indent}        {#if {$next_var}.children}\n";
            $html .= "{$indent}        <button class=\"{$cls}__submenu-toggle\" aria-label=\"Toggle submenu\" tabindex=\"-1\">\n";
            $html .= "{$indent}          <span class=\"{$cls}__submenu-icon\" aria-hidden=\"true\"></span>\n";
            $html .= "{$indent}        </button>\n";
            $html .= "{$indent}        {/if}";
        }

        // Recurse for deeper levels
        $deeper = $this->generate_submenu_html_direct( $depth + 1, $max_depth, $indent . '        ', $next_var );
        $html .= $deeper;

        $html .= "\n{$indent}      </li>\n";
        $html .= "{$indent}    {/loop}\n";
        $html .= "{$indent}  </ul>\n";
        $html .= "{$indent}{/if}";

        return $html;
    }

    /**
     * Generate submenu HTML recursively for component approach
     *
     * @param int    $depth Current depth level
     * @param int    $max_depth Maximum depth
     * @param string $indent Current indentation
     * @param string $var_name Variable name for current item
     * @return string HTML code
     */
    private function generate_submenu_html_component( $depth, $max_depth, $indent, $var_name ) {
        if ( $depth > $max_depth ) {
            return '';
        }

        $cls = $this->cls;
        $next_var = $this->get_depth_var_name( $depth );
        $data_level = $depth + 1;

        $html = "\n{$indent}{#if {$var_name}.children}\n";
        $html .= "{$indent}  <ul class=\"{$cls}__sub-menu\" role=\"menu\">\n";
        $html .= "{$indent}    {#loop {$var_name}.children as {$next_var}}\n";
        $html .= "{$indent}      <li class=\"{$cls}__item {{$next_var}.state_classes}\" role=\"none\" data-level=\"{$data_level}\">\n";

        $html .= "{$indent}        <a href=\"{{$next_var}.url}\"\n";
        $html .= "{$indent}           class=\"{$cls}__link {{$next_var}.link_classes}\"\n";
        $html .= "{$indent}           role=\"menuitem\">\n";
        $html .= "{$indent}          {{$next_var}.title}\n";
        $html .= "{$indent}        </a>";

        // Toggle button for submenu items with children
        if ( $depth < $max_depth ) {
            $html .= "\n{$indent}        {#if {$next_var}.children}\n";
            $html .= "{$indent}        <button class=\"{$cls}__submenu-toggle\" aria-label=\"Toggle submenu\" tabindex=\"-1\">\n";
            $html .= "{$indent}          <span class=\"{$cls}__submenu-icon\" aria-hidden=\"true\"></span>\n";
            $html .= "{$indent}        </button>\n";
            $html .= "{$indent}        {/if}";
        }

        $deeper = $this->generate_submenu_html_component( $depth + 1, $max_depth, $indent . '        ', $next_var );
        $html .= $deeper;

        $html .= "\n{$indent}      </li>\n";
        $html .= "{$indent}    {/loop}\n";
        $html .= "{$indent}  </ul>\n";
        $html .= "{$indent}{/if}";

        return $html;
    }

    /**
     * Get variable name for a given depth level
     *
     * @param int $depth Depth level (1 = child, 2 = subchild, etc.)
     * @return string Variable name
     */
    private function get_depth_var_name( $depth ) {
        $names = array( 1 => 'child', 2 => 'subchild', 3 => 'subsubchild', 4 => 'level4child', 5 => 'level5child' );
        return isset( $names[ $depth ] ) ? $names[ $depth ] : 'level' . $depth . 'child';
    }

    /**
     * Generate Direct Loop HTML
     *
     * @param array $settings User settings
     * @return string HTML code
     */
    private function generate_direct_html( $settings ) {
        $cls = $this->cls;
        $menu_name = $this->get_menu_name( $settings );
        $has_mobile = $this->has_mobile_support( $settings );
        $desktop_depth = isset( $settings['submenu_depth_desktop'] ) ? intval( $settings['submenu_depth_desktop'] ) : 1;

        $html = '';

        // Hamburger button — outside <nav>, linked via aria-controls
        if ( $has_mobile ) {
            $html .= '<button class="' . $cls . '__hamburger" type="button"
        aria-controls="' . $cls . '-nav"
        aria-expanded="false"
        aria-label="Toggle navigation menu">
  <span class="' . $cls . '__hamburger-line"></span>
  <span class="' . $cls . '__hamburger-line"></span>
  <span class="' . $cls . '__hamburger-line"></span>
</button>

';
        }

        // Nav element — modifier classes for position + behaviour
        $nav_classes = $cls;
        if ( $has_mobile ) {
            $position = isset( $settings['menu_position'] ) ? $settings['menu_position'] : 'left';
            $behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';
            $nav_classes .= ' ' . $cls . '--' . $position . ' ' . $cls . '--' . $behavior;
        }

        $html .= '<nav class="' . $nav_classes . '" id="' . $cls . '-nav" role="navigation" aria-label="Main navigation">
  <ul class="' . $cls . '__list" role="menubar">
    {#loop options.menus.' . esc_html( $menu_name ) . ' as item}
      <li class="' . $cls . '__item {item.state_classes}" role="none" data-level="1">';

        $html .= '
        <a href="{item.url}"
           class="' . $cls . '__link {item.link_classes}"
           role="menuitem">
          {item.title}
        </a>';

        if ( $desktop_depth > 0 ) {
            $html .= '
        {#if item.children}
        <button class="' . $cls . '__submenu-toggle" aria-label="Toggle submenu" tabindex="-1">
          <span class="' . $cls . '__submenu-icon" aria-hidden="true"></span>
        </button>
        {/if}';
            $html .= $this->generate_submenu_html_direct( 1, $desktop_depth, '        ', 'item' );
        }

        $html .= '
      </li>
    {/loop}
  </ul>
</nav>';

        return $html;
    }

    /**
     * Generate Component HTML
     *
     * @param array $settings User settings
     * @return string HTML code
     */
    private function generate_component_html( $settings ) {
        $cls = $this->cls;
        $prop_name = isset( $settings['component_prop_name'] ) && ! empty( $settings['component_prop_name'] )
            ? $this->sanitize_prop_name( $settings['component_prop_name'] )
            : 'menuItems';
        $has_mobile = $this->has_mobile_support( $settings );
        $desktop_depth = isset( $settings['submenu_depth_desktop'] ) ? intval( $settings['submenu_depth_desktop'] ) : 1;

        $html = '';

        // Hamburger button — outside <nav>, linked via aria-controls
        if ( $has_mobile ) {
            $html .= '<button class="' . $cls . '__hamburger" type="button"
        aria-controls="' . $cls . '-nav"
        aria-expanded="false"
        aria-label="Toggle navigation menu">
  <span class="' . $cls . '__hamburger-line"></span>
  <span class="' . $cls . '__hamburger-line"></span>
  <span class="' . $cls . '__hamburger-line"></span>
</button>

';
        }

        // Nav element — modifier classes for position + behaviour
        $nav_classes = $cls;
        if ( $has_mobile ) {
            $position = isset( $settings['menu_position'] ) ? $settings['menu_position'] : 'left';
            $behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';
            $nav_classes .= ' ' . $cls . '--' . $position . ' ' . $cls . '--' . $behavior;
        }

        $html .= '<nav class="' . $nav_classes . '" id="' . $cls . '-nav" role="navigation" aria-label="Main navigation">
  <ul class="' . $cls . '__list" role="menubar">
    {#loop props.' . esc_html( $prop_name ) . ' as item}
      <li class="' . $cls . '__item {item.state_classes}" role="none" data-level="1">';

        $html .= '
        <a href="{item.url}"
           class="' . $cls . '__link {item.link_classes}"
           role="menuitem">
          {item.title}
        </a>';

        if ( $desktop_depth > 0 ) {
            $html .= '
        {#if item.children}
        <button class="' . $cls . '__submenu-toggle" aria-label="Toggle submenu" tabindex="-1">
          <span class="' . $cls . '__submenu-icon" aria-hidden="true"></span>
        </button>
        {/if}';
            $html .= $this->generate_submenu_html_component( 1, $desktop_depth, '        ', 'item' );
        }

        $html .= '
      </li>
    {/loop}
  </ul>
</nav>';

        return $html;
    }

    /**
     * Generate nested CSS
     *
     * @param array $settings User settings
     * @return string CSS code
     */
    public function generate_css( $settings ) {
        $this->cls = $this->get_css_class( $settings );
        $has_mobile = $this->has_mobile_support( $settings );
        $desktop_depth = isset( $settings['submenu_depth_desktop'] ) ? intval( $settings['submenu_depth_desktop'] ) : 1;

        $cls = $this->cls;
        $breakpoint = isset( $settings['mobile_breakpoint'] ) ? intval( $settings['mobile_breakpoint'] ) : 1200;
        $breakpoint_up = $breakpoint + 1;

        // CSS Custom Properties
        $css = "/* ----------------------------------------
   CSS Custom Properties (Tokens)
   ---------------------------------------- */
:root {
  /* Colors */
  --menu-clr-text: #2c3338;
  --menu-clr-text-accent: #0073aa;
  --menu-clr-bg: #ffffff;
  --menu-clr-bg-accent: #f0f0f1;
  --menu-clr-bg-hover: #f9f9f9;
  --menu-box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  --menu-border-radius: 0px;

  /* Spacing */
  --menu-padding-x: 1.25rem;
  --menu-padding-y: 0.75rem;
  --menu-gap: 0.5rem;
  --menu-padding-top: 80px;

  /* Mobile dimensions */
  --menu-mobile-width: 100%;
  --menu-mobile-breakpoint: {$breakpoint}px;

  /* Z-index layers */
  --menu-z-hamburger: 1000;
  --menu-z-nav: 999;
  --menu-z-submenu: 1;

  /* Transitions */
  --menu-transition-duration: 0.2s;
  --menu-transition-easing: ease-in-out;
  --menu-hover-delay: 0.15s;

  /* Submenu toggle */
  --menu-toggle-size: 50px;
}\n\n";

        // Hamburger (outside <nav>)
        if ( $has_mobile ) {
            $animation_type = isset( $settings['hamburger_animation'] ) ? $settings['hamburger_animation'] : 'spin';
            $hamburger_animation = $this->get_hamburger_animation( $animation_type );

            $css .= "/* ----------------------------------------
   Hamburger Button
   ---------------------------------------- */
.{$cls}__hamburger {
  display: none;
  background: var(--menu-clr-bg);
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  z-index: var(--menu-z-hamburger);
  flex-direction: column;
  justify-content: center;
  align-items: center;
  width: 2rem;
  height: 2rem;
  gap: 5px;

  @media (max-width: {$breakpoint}px) {
    display: flex;
  }

  &:focus-visible {
    outline: 2px solid var(--menu-clr-text-accent);
    outline-offset: 2px;
  }
}

.{$cls}__hamburger-line {
  display: block;
  width: 2rem;
  height: 3px;
  background-color: var(--menu-clr-text);
  border-radius: 3px;
  transition: all 0.4s ease;
  transform-origin: center center;
}

{$hamburger_animation}\n\n";
        }

        // Base navigation — nav stays in normal flow
        $css .= "/* ----------------------------------------
   Base Navigation
   ---------------------------------------- */
.{$cls} {
  background: var(--menu-clr-bg);
  color: var(--menu-clr-text);

  @media (min-width: {$breakpoint_up}px) {
    position: relative;
    width: 100%;
  }
}\n\n";

        // Menu panel wrapper — slides on/off screen on mobile.
        // Hamburger is a sibling, so it stays visible.
        if ( $has_mobile ) {
            $css .= "/* ----------------------------------------
   Menu Panel (slides on mobile)
   ---------------------------------------- */
.{$cls}__menu {
  @media (max-width: {$breakpoint}px) {
    position: fixed;
    z-index: var(--menu-z-nav);
    overflow-y: auto;
    padding-top: var(--menu-padding-top);
    background: var(--menu-clr-bg);
    top: 0;
    left: 0;
    width: var(--menu-mobile-width);
    height: 100dvh;
    transform: translateX(-100%);
    transition: transform var(--menu-transition-duration) var(--menu-transition-easing);

    &.is-open {
      transform: translateX(0);
    }
  }
}\n\n";
        }

        // Navigation list
        $css .= "/* ----------------------------------------
   Navigation List
   ---------------------------------------- */
.{$cls}__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;

  @media (min-width: {$breakpoint_up}px) {
    flex-direction: row;
    gap: var(--menu-gap);
    align-items: center;
  }";

        if ( $has_mobile ) {
            $css .= "

  @media (max-width: {$breakpoint}px) {
    flex-direction: column;
  }";
        }

        $css .= "
}\n\n";

        // Navigation items
        $css .= "/* ----------------------------------------
   Navigation Items
   ---------------------------------------- */
.{$cls}__item {
  position: relative;";

        if ( $has_mobile ) {
            $css .= "

  @media (max-width: {$breakpoint}px) {
    width: 100%;
    border-bottom: 1px solid var(--menu-clr-bg-accent);
  }";
        }

        $css .= "
}\n\n";

        // Navigation links
        $css .= "/* ----------------------------------------
   Navigation Links
   ---------------------------------------- */
.{$cls}__link {
  display: block;
  padding: var(--menu-padding-y) var(--menu-padding-x);
  color: var(--menu-clr-text);
  text-decoration: none;
  transition:
    background-color var(--menu-transition-duration) var(--menu-transition-easing),
    color var(--menu-transition-duration) var(--menu-transition-easing);

  &:hover {
    background-color: var(--menu-clr-bg-hover);
    color: var(--menu-clr-text-accent);
  }

  &:focus-visible {
    outline: 2px solid var(--menu-clr-text-accent);
    outline-offset: -2px;
  }

  &.current-page {
    color: var(--menu-clr-text-accent);
  }";

        if ( $has_mobile ) {
            $css .= "

  .{$cls}__item.has-submenu > & {
    @media (max-width: {$breakpoint}px) {
      padding-right: calc(var(--menu-toggle-size) + var(--menu-padding-x));
    }
  }";
        }

        $css .= "
}

.{$cls}__item.current-parent > .{$cls}__link {
  color: var(--menu-clr-text-accent);
}\n\n";

        // Submenus
        if ( $desktop_depth > 0 ) {
            $css .= "/* ----------------------------------------
   Sub-menus
   ---------------------------------------- */
.{$cls}__sub-menu {
  list-style: none;
  margin: 0;
  padding: 0;

  @media (min-width: {$breakpoint_up}px) {
    position: absolute;
    top: 100%;
    left: 0;
    min-width: 200px;
    background: var(--menu-clr-bg);
    box-shadow: var(--menu-box-shadow);
    border-radius: var(--menu-border-radius);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition:
      opacity var(--menu-transition-duration) var(--menu-transition-easing),
      visibility var(--menu-transition-duration) var(--menu-transition-easing),
      transform var(--menu-transition-duration) var(--menu-transition-easing);
    z-index: var(--menu-z-submenu);

    /* Show submenu on parent hover/focus */
    .{$cls}__item:hover > &,
    .{$cls}__item:focus-within > & {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
      transition-delay: var(--menu-hover-delay);
    }

    /* Nested sub-menus cascade right */
    .{$cls}__sub-menu & {
      top: 0;
      left: 100%;
      transform: translateX(1px);

      .{$cls}__item:hover > &,
      .{$cls}__item:focus-within > & {
        transform: translateX(1px);
      }
    }

    /* Cascade left when near edge (added via JS) */
    .{$cls}__sub-menu.cascade-left & {
      left: auto;
      right: 100%;
      transform: translateX(1px);

      .{$cls}__item:hover > &,
      .{$cls}__item:focus-within > & {
        transform: translateX(0);
      }
    }
  }";

            if ( $has_mobile ) {
                $css .= "

  @media (max-width: {$breakpoint}px) {
    border-top: 1px solid var(--menu-clr-bg-accent);

    .{$cls}__item:last-of-type {
      border-bottom: 0;
    }

    .{$cls}__item a {
      padding-left: 40px;
    }

    .{$cls}__sub-menu .{$cls}__item a {
      padding-left: 70px;
    }
  }";
            }

            $css .= "
}\n\n";
        }

        // Submenu toggle button
        $css .= "/* ----------------------------------------
   Submenu Toggle Button (Mobile)
   ---------------------------------------- */
.{$cls}__submenu-toggle {
  display: none;";

        if ( $has_mobile ) {
            $css .= "

  @media (max-width: {$breakpoint}px) {
    display: flex;
    position: absolute;
    top: 0;
    right: 0;
    width: var(--menu-toggle-size);
    height: var(--menu-toggle-size);
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    z-index: 1;
    align-items: center;
    justify-content: center;

    &:focus-visible {
      outline: 2px solid var(--menu-clr-text-accent);
      outline-offset: -2px;
    }
  }";
        }

        $css .= "
}\n\n";

        // Submenu icon (chevron)
        $css .= "/* Submenu toggle icon (chevron) */
.{$cls}__submenu-icon {";

        if ( $has_mobile ) {
            $submenu_behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';

            $css .= "
  @media (max-width: {$breakpoint}px) {
    display: block;
    width: 8px;
    height: 8px;
    border-right: 2px solid var(--menu-clr-text);
    border-bottom: 2px solid var(--menu-clr-text);
    transform: rotate(45deg);
    transition: transform var(--menu-transition-duration) var(--menu-transition-easing);";

            if ( 'accordion' === $submenu_behavior ) {
                $css .= "

    .{$cls}__item--submenu-open > .{$cls}__submenu-toggle & {
      transform: rotate(-135deg);
    }";
            } elseif ( 'slide' === $submenu_behavior ) {
                $css .= "

    /* Slide mode: point right */
    transform: rotate(-45deg);";
            }

            $css .= "
  }";
        }

        $css .= "
}\n\n";

        // Mobile-specific behaviour modes
        if ( $has_mobile ) {
            $submenu_behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';

            if ( 'accordion' === $submenu_behavior ) {
                $css .= "/* ----------------------------------------
   Mobile: Accordion Mode
   ---------------------------------------- */
@media (max-width: {$breakpoint}px) {
  .{$cls}--accordion {
    .{$cls}__sub-menu {
      display: none;
      max-height: 0;
      overflow: hidden;
      transition: max-height var(--menu-transition-duration) var(--menu-transition-easing);
    }

    .{$cls}__item--submenu-open > .{$cls}__sub-menu {
      display: block;
      max-height: 1000px;
    }
  }
}\n\n";
            } elseif ( 'slide' === $submenu_behavior ) {
                $css .= "/* ----------------------------------------
   Mobile: Slide Mode
   ---------------------------------------- */
@media (max-width: {$breakpoint}px) {
  .{$cls}--slide {
    overflow: hidden;

    > .{$cls}__list {
      display: none;
    }

    .sliding-nav-panels {
      position: absolute;
      top: var(--menu-padding-top);
      left: 0;
      width: 100%;
      height: calc(100% - var(--menu-padding-top));
    }

    .sliding-panel {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      transform: translateX(100%);
      opacity: 0;
      transition:
        transform var(--menu-transition-duration) var(--menu-transition-easing),
        opacity var(--menu-transition-duration) var(--menu-transition-easing);
      pointer-events: none;
      overflow-y: auto;
    }

    .sliding-panel.active {
      transform: translateX(0);
      opacity: 1;
      pointer-events: auto;
    }

    .sliding-panel.previous {
      transform: translateX(-100%);
      opacity: 0;
      pointer-events: none;
    }
  }
}

/* Back button (slide mode) */
.{$cls}__back {
  display: none;

  @media (max-width: {$breakpoint}px) {
    .{$cls}--slide & {
      display: block;
      border-bottom: 1px solid var(--menu-clr-bg-accent);
    }
  }
}

.{$cls}__back-button {
  width: 100%;
  padding: var(--menu-padding-y) var(--menu-padding-x);
  background: var(--menu-clr-bg);
  border: none;
  text-align: left;
  font-size: 1rem;
  color: var(--menu-clr-text);
  cursor: pointer;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;

  &:hover {
    background-color: var(--menu-clr-bg-hover);
    color: var(--menu-clr-text-accent);
  }

  &:focus-visible {
    outline: 2px solid var(--menu-clr-text-accent);
    outline-offset: -2px;
  }
}

.{$cls}__back-icon {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-left: 2px solid var(--menu-clr-text);
  border-bottom: 2px solid var(--menu-clr-text);
  transform: rotate(45deg);

  .{$cls}__back-button:hover &,
  .{$cls}__back-button:focus & {
    border-color: var(--menu-clr-text-accent);
  }
}\n\n";
            }

            // Position modifiers
            $menu_position = isset( $settings['menu_position'] ) ? $settings['menu_position'] : 'left';
            $css .= $this->get_menu_position( $menu_position, $breakpoint, $settings );
        }

        // Utility classes
        $css .= "
/* ----------------------------------------
   Utility Classes
   ---------------------------------------- */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

body.menu-open {
  @media (max-width: {$breakpoint}px) {
    overflow: hidden;
  }
}";

        return $css;
    }

    /**
     * Generate JavaScript with selected features
     *
     * @param array $settings User settings
     * @return string JavaScript code (empty if no mobile support)
     */
    public function generate_javascript( $settings ) {
        $this->cls = $this->get_css_class( $settings );
        if ( ! $this->has_mobile_support( $settings ) ) {
            return '';
        }

        $cls = $this->cls;
        $breakpoint = isset( $settings['mobile_breakpoint'] ) ? intval( $settings['mobile_breakpoint'] ) : 1200;
        $close_methods = isset( $settings['close_methods'] ) ? $settings['close_methods'] : array( 'hamburger', 'outside', 'esc' );
        $accessibility = isset( $settings['accessibility'] ) ? $settings['accessibility'] : array( 'focus_trap', 'scroll_lock', 'aria', 'keyboard' );
        $submenu_behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';
        $has_outside_click = in_array( 'outside', $close_methods );
        $has_scroll_lock = in_array( 'scroll_lock', $accessibility );
        $has_keyboard = in_array( 'keyboard', $accessibility );
        $has_accordion = ( 'accordion' === $submenu_behavior );
        $has_slide = ( 'slide' === $submenu_behavior );

        $js = "/**
 * Accessible Navigation System
 * Handles mobile menu, accordion/slide modes, edge detection, and keyboard navigation
 */
class AccessibleNavigation {
  constructor(navElement) {
    this.nav = navElement;
    this.menu = navElement.querySelector('.{$cls}__menu');
    this.hamburger = document.querySelector('[aria-controls=\"' + navElement.id + '\"]');
    this.isOpen = false;
    this.isMobile = false;
    this.closeTimeout = null;";

        if ( $has_slide ) {
            $js .= "
    this.slidingPanelsContainer = null;
    this.panelCounter = 0;";
        }

        $js .= "

    this.init();
  }

  init() {
    this.checkMobile();";

        if ( $has_slide ) {
            $js .= "
    this.setupSlideMode();";
        }

        $js .= "
    this.setupEventListeners();";

        if ( $has_keyboard ) {
            $js .= "
    this.setupKeyboardNavigation();";
        }

        $js .= "
    this.checkSubMenuEdges();

    window.addEventListener('resize', () => {
      this.checkMobile();
      this.checkSubMenuEdges();";

        if ( $has_slide ) {
            $js .= "
      if (this.nav.dataset.behavior === 'slide') {
        this.setupSlideMode();
      }";
        }

        $js .= "
    });
  }

  checkMobile() {
    this.isMobile = window.innerWidth <= {$breakpoint};
  }";

        // Slide mode
        if ( $has_slide ) {
            $js .= "

  setupSlideMode() {
    if (this.nav.dataset.behavior !== 'slide') return;

    if (!this.isMobile) {
      if (this.slidingPanelsContainer) {
        this.slidingPanelsContainer.remove();
        this.slidingPanelsContainer = null;
        this.panelCounter = 0;
      }
      return;
    }

    if (this.slidingPanelsContainer) return;

    this.panelCounter = 0;
    this.slidingPanelsContainer = document.createElement('div');
    this.slidingPanelsContainer.className = 'sliding-nav-panels';

    const originalList = this.nav.querySelector('.{$cls}__list');

    const createPanel = (menuList, parentItem) => {
      this.panelCounter++;
      const panelId = 'panel-' + this.panelCounter;

      const panel = document.createElement('div');
      panel.className = 'sliding-panel';
      panel.setAttribute('data-panel-id', panelId);

      const newList = document.createElement('ul');
      newList.className = '{$cls}__list';
      newList.setAttribute('role', 'menubar');

      if (panelId !== 'panel-1' && parentItem) {
        const parentLink = parentItem.querySelector('.{$cls}__link');
        const parentText = parentLink.textContent;

        const backItem = document.createElement('li');
        backItem.className = '{$cls}__back';
        backItem.setAttribute('role', 'none');

        const backButton = document.createElement('button');
        backButton.className = '{$cls}__back-button';
        backButton.setAttribute('aria-label', 'Go back from ' + parentText);
        backButton.innerHTML = '<span class=\"{$cls}__back-icon\" aria-hidden=\"true\"></span> Back';

        backItem.appendChild(backButton);
        newList.appendChild(backItem);

        const titleItem = document.createElement('li');
        titleItem.className = '{$cls}__item';
        if (parentItem.classList.contains('current-page')) {
          titleItem.classList.add('current-page');
        }
        if (parentItem.classList.contains('current-parent')) {
          titleItem.classList.add('current-parent');
        }
        titleItem.setAttribute('role', 'none');

        const titleLink = parentLink.cloneNode(true);
        titleItem.appendChild(titleLink);
        newList.appendChild(titleItem);
      }

      const items = menuList.querySelectorAll(':scope > .{$cls}__item');
      items.forEach((item) => {
        const itemClone = item.cloneNode(false);
        const link = item.querySelector('.{$cls}__link').cloneNode(true);
        const hasSubmenu = item.classList.contains('has-submenu');

        itemClone.className = item.className;
        itemClone.setAttribute('role', 'none');

        if (item.hasAttribute('data-level')) {
          itemClone.setAttribute('data-level', item.getAttribute('data-level'));
        }

        itemClone.appendChild(link);

        if (hasSubmenu) {
          const submenu = item.querySelector('.{$cls}__sub-menu');
          const childPanelId = createPanel(submenu, item);

          itemClone.setAttribute('data-panel-target', childPanelId);

          const toggle = document.createElement('button');
          toggle.className = '{$cls}__submenu-toggle';
          toggle.setAttribute('aria-label', 'Open ' + link.textContent.trim() + ' submenu');
          toggle.setAttribute('tabindex', '-1');
          toggle.innerHTML = '<span class=\"{$cls}__submenu-icon\" aria-hidden=\"true\"></span>';

          itemClone.appendChild(toggle);
        }

        newList.appendChild(itemClone);
      });

      panel.appendChild(newList);
      this.slidingPanelsContainer.appendChild(panel);

      return panelId;
    };

    createPanel(originalList, null);

    const rootPanel = this.slidingPanelsContainer.querySelector('[data-panel-id=\"panel-1\"]');
    if (rootPanel) {
      rootPanel.classList.add('active');
    }

    this.menu.appendChild(this.slidingPanelsContainer);
    this.setupSlidePanelListeners();
  }

  setupSlidePanelListeners() {
    if (!this.slidingPanelsContainer) return;

    this.slidingPanelsContainer.addEventListener('click', (e) => {
      const toggle = e.target.closest('.{$cls}__submenu-toggle');
      if (toggle) {
        e.preventDefault();
        e.stopPropagation();

        const item = toggle.closest('.{$cls}__item');
        const currentPanel = item.closest('.sliding-panel');
        const targetPanelId = item.getAttribute('data-panel-target');
        const targetPanel = this.slidingPanelsContainer.querySelector('[data-panel-id=\"' + targetPanelId + '\"]');

        if (targetPanel) {
          currentPanel.classList.remove('active');
          currentPanel.classList.add('previous');
          targetPanel.classList.add('active');
          targetPanel.setAttribute('data-previous-panel', currentPanel.getAttribute('data-panel-id'));

          const firstLink = targetPanel.querySelector('.{$cls}__link');
          if (firstLink) {
            setTimeout(() => firstLink.focus(), 50);
          }
        }
      }

      const backButton = e.target.closest('.{$cls}__back-button');
      if (backButton) {
        e.preventDefault();
        e.stopPropagation();

        const currentPanel = backButton.closest('.sliding-panel');
        const previousPanelId = currentPanel.getAttribute('data-previous-panel');
        const previousPanel = this.slidingPanelsContainer.querySelector('[data-panel-id=\"' + previousPanelId + '\"]');

        if (previousPanel) {
          currentPanel.classList.remove('active');
          previousPanel.classList.remove('previous');
          previousPanel.classList.add('active');

          const triggeringItem = previousPanel.querySelector('[data-panel-target=\"' + currentPanel.getAttribute('data-panel-id') + '\"]');
          if (triggeringItem) {
            const link = triggeringItem.querySelector('.{$cls}__link');
            if (link) {
              setTimeout(() => link.focus(), 50);
            }
          }
        }
      }
    });
  }";
        }

        // Event listeners
        $js .= "

  setupEventListeners() {
    if (this.hamburger) {
      this.hamburger.addEventListener('click', () => this.toggleMenu());
    }";

        if ( $has_outside_click ) {
            $js .= "

    document.addEventListener('click', (e) => {
      if (this.isMobile && this.isOpen) {
        if (!this.nav.contains(e.target) && this.hamburger && !this.hamburger.contains(e.target)) {
          this.closeMenu();
        }
      }
    });";
        }

        if ( $has_accordion ) {
            $js .= "

    if (this.nav.dataset.behavior === 'accordion') {
      const toggles = this.nav.querySelectorAll('.{$cls}__submenu-toggle');
      toggles.forEach((toggle) => {
        toggle.addEventListener('click', (e) => this.handleAccordionToggle(e));
      });
    }";
        }

        $js .= "

    if (!this.isMobile) {
      this.setupDesktopHover();
    }
  }";

        // Accordion toggle
        if ( $has_accordion ) {
            $js .= "

  handleAccordionToggle(e) {
    e.preventDefault();
    e.stopPropagation();

    const toggle = e.currentTarget;
    const parentItem = toggle.closest('.{$cls}__item');
    const link = parentItem.querySelector('.{$cls}__link');
    const isOpen = parentItem.classList.contains('{$cls}__item--submenu-open');

    if (isOpen) {
      parentItem.classList.remove('{$cls}__item--submenu-open');
      link.setAttribute('aria-expanded', 'false');
      const label = toggle.getAttribute('aria-label').replace('Close', 'Open');
      toggle.setAttribute('aria-label', label);
    } else {
      parentItem.classList.add('{$cls}__item--submenu-open');
      link.setAttribute('aria-expanded', 'true');
      const label = toggle.getAttribute('aria-label').replace('Open', 'Close');
      toggle.setAttribute('aria-label', label);
    }
  }";
        }

        // Toggle / Open / Close menu
        $js .= "

  toggleMenu() {
    if (this.isOpen) {
      this.closeMenu();
    } else {
      this.openMenu();
    }
  }

  openMenu() {
    this.isOpen = true;
    this.menu.classList.add('is-open');
    if (this.hamburger) {
      this.hamburger.classList.add('is-active');
      this.hamburger.setAttribute('aria-expanded', 'true');
    }
    document.body.classList.add('menu-open');
    document.body.style.overflow = 'hidden';

    const firstLink = this.menu.querySelector('.{$cls}__link');
    if (firstLink) {
      firstLink.focus();
    }
  }

  closeMenu() {
    this.isOpen = false;
    this.menu.classList.remove('is-open');
    if (this.hamburger) {
      this.hamburger.classList.remove('is-active');
      this.hamburger.setAttribute('aria-expanded', 'false');
    }
    document.body.classList.remove('menu-open');
    document.body.style.overflow = '';";

        if ( $has_slide ) {
            $js .= "

    if (this.slidingPanelsContainer) {
      setTimeout(() => {
        const panels = this.slidingPanelsContainer.querySelectorAll('.sliding-panel');
        panels.forEach((panel) => {
          panel.classList.remove('active', 'previous');
        });
        const rootPanel = this.slidingPanelsContainer.querySelector('[data-panel-id=\"panel-1\"]');
        if (rootPanel) {
          rootPanel.classList.add('active');
        }
      }, 300);
    }";
        }

        if ( $has_accordion ) {
            $js .= "

    this.closeAllAccordionSubmenus();";
        }

        $js .= "

    if (this.hamburger) {
      this.hamburger.focus();
    }
  }";

        // Close all accordion submenus
        if ( $has_accordion ) {
            $js .= "

  closeAllAccordionSubmenus() {
    const openItems = this.nav.querySelectorAll('.{$cls}__item--submenu-open');
    openItems.forEach((item) => {
      item.classList.remove('{$cls}__item--submenu-open');
      const link = item.querySelector('.{$cls}__link');
      if (link) {
        link.setAttribute('aria-expanded', 'false');
      }
      const toggle = item.querySelector('.{$cls}__submenu-toggle');
      if (toggle) {
        const label = toggle.getAttribute('aria-label').replace('Close', 'Open');
        toggle.setAttribute('aria-label', label);
      }
    });
  }";
        }

        // Desktop hover
        $js .= "

  setupDesktopHover() {
    const itemsWithSubmenus = this.nav.querySelectorAll('.{$cls}__item.has-submenu');

    itemsWithSubmenus.forEach((item) => {
      const link = item.querySelector('.{$cls}__link');

      item.addEventListener('mouseenter', () => {
        if (!this.isMobile) {
          clearTimeout(this.closeTimeout);

          const siblings = item.parentElement.querySelectorAll(':scope > .{$cls}__item.has-submenu');
          siblings.forEach((sibling) => {
            if (sibling !== item) {
              sibling.classList.remove('{$cls}__item--submenu-open');
              const siblingLink = sibling.querySelector('.{$cls}__link');
              if (siblingLink) {
                siblingLink.setAttribute('aria-expanded', 'false');
              }
            }
          });

          item.classList.add('{$cls}__item--submenu-open');
          link.setAttribute('aria-expanded', 'true');
        }
      });

      item.addEventListener('mouseleave', () => {
        if (!this.isMobile) {
          this.closeTimeout = setTimeout(() => {
            item.classList.remove('{$cls}__item--submenu-open');
            link.setAttribute('aria-expanded', 'false');
          }, 200);
        }
      });
    });
  }

  checkSubMenuEdges() {
    if (this.isMobile) return;

    const subMenus = this.nav.querySelectorAll('.{$cls}__sub-menu .{$cls}__sub-menu');
    subMenus.forEach((submenu) => {
      const parentItem = submenu.closest('.{$cls}__item');
      const wasOpen = parentItem.classList.contains('{$cls}__item--submenu-open');
      if (!wasOpen) {
        parentItem.classList.add('{$cls}__item--submenu-open');
      }

      const rect = submenu.getBoundingClientRect();
      if (rect.right > window.innerWidth - 20) {
        parentItem.classList.add('cascade-left');
      } else {
        parentItem.classList.remove('cascade-left');
      }

      if (!wasOpen) {
        parentItem.classList.remove('{$cls}__item--submenu-open');
      }
    });
  }";

        // Keyboard navigation
        if ( $has_keyboard ) {
            $js .= "

  setupKeyboardNavigation() {
    const menuItems = this.nav.querySelectorAll('[role=\"menuitem\"]');

    menuItems.forEach((item) => {
      item.addEventListener('keydown', (e) => {
        const parentItem = item.closest('.{$cls}__item');
        const parentList = item.closest('ul');
        const isTopLevel = parentList.classList.contains('{$cls}__list');
        const hasSubmenu = parentItem.classList.contains('has-submenu');
        const submenu = parentItem.querySelector('.{$cls}__sub-menu');

        switch (e.key) {
          case 'ArrowDown':
            e.preventDefault();
            this.focusNextItem(item, parentList);
            break;

          case 'ArrowUp':
            e.preventDefault();
            this.focusPreviousItem(item, parentList);
            break;

          case 'ArrowRight':
            e.preventDefault();
            if (hasSubmenu && !this.isMobile) {
              parentItem.classList.add('{$cls}__item--submenu-open');
              item.setAttribute('aria-expanded', 'true');
              const firstSubmenuItem = submenu.querySelector('[role=\"menuitem\"]');
              if (firstSubmenuItem) {
                firstSubmenuItem.focus();
              }
            } else if (isTopLevel && !this.isMobile) {
              this.focusNextItem(item, parentList);
            }
            break;

          case 'ArrowLeft':
            e.preventDefault();
            if (!isTopLevel && !this.isMobile) {
              const parentMenuItem = parentList.closest('.{$cls}__item');
              const parentLink = parentMenuItem.querySelector('.{$cls}__link');
              parentMenuItem.classList.remove('{$cls}__item--submenu-open');
              parentLink.setAttribute('aria-expanded', 'false');
              parentLink.focus();
            } else if (isTopLevel && !this.isMobile) {
              this.focusPreviousItem(item, parentList);
            }
            break;

          case 'Escape':
            e.preventDefault();
            if (this.isMobile && this.isOpen) {
              this.closeMenu();
            } else if (!isTopLevel) {
              const parentMenuItem = parentList.closest('.{$cls}__item');
              const parentLink = parentMenuItem.querySelector('.{$cls}__link');
              parentMenuItem.classList.remove('{$cls}__item--submenu-open');
              parentLink.setAttribute('aria-expanded', 'false');
              parentLink.focus();
            }
            break;
        }
      });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.isMobile && this.isOpen) {
        this.closeMenu();
      }
    });
  }

  focusNextItem(currentItem, parentList) {
    const items = Array.from(parentList.querySelectorAll(':scope > .{$cls}__item > [role=\"menuitem\"]'));
    const currentIndex = items.indexOf(currentItem);
    const nextIndex = (currentIndex + 1) % items.length;
    items[nextIndex].focus();
  }

  focusPreviousItem(currentItem, parentList) {
    const items = Array.from(parentList.querySelectorAll(':scope > .{$cls}__item > [role=\"menuitem\"]'));
    const currentIndex = items.indexOf(currentItem);
    const previousIndex = currentIndex === 0 ? items.length - 1 : currentIndex - 1;
    items[previousIndex].focus();
  }";
        }

        $js .= "
}

document.addEventListener('DOMContentLoaded', () => {
  const navs = document.querySelectorAll('.{$cls}');
  navs.forEach((nav) => {
    new AccessibleNavigation(nav);
  });
});";

        return $js;
    }

    /**
     * Get hamburger animation CSS
     *
     * The hamburger uses 3 span lines with flexbox gap: 5px, each 3px tall.
     * Total hamburger height: 2rem (32px). Lines at ~9.5px, ~16px, ~22.5px.
     * The gap between lines is 5px, plus line height 3px = 8px center-to-center.
     *
     * @param string $type Animation type
     * @return string CSS code
     */
    private function get_hamburger_animation( $type ) {
        $cls = $this->cls;

        $animations = array(
            'spin' => ".{$cls}__hamburger.is-active .{$cls}__hamburger-line {
  &:nth-child(1) {
    transform: translateY(8px) rotate(225deg);
  }
  &:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
  }
  &:nth-child(3) {
    transform: translateY(-8px) rotate(-225deg);
  }
}",
            'squeeze' => ".{$cls}__hamburger.is-active .{$cls}__hamburger-line {
  &:nth-child(1) {
    transform: translateY(8px) rotate(45deg);
  }
  &:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
  }
  &:nth-child(3) {
    transform: translateY(-8px) rotate(-45deg);
  }
}",
            'collapse' => ".{$cls}__hamburger.is-active .{$cls}__hamburger-line {
  &:nth-child(1) {
    transform: translateY(8px) rotate(-45deg);
  }
  &:nth-child(2) {
    opacity: 0;
  }
  &:nth-child(3) {
    transform: translateY(-8px) rotate(45deg);
  }
}",
        );

        return isset( $animations[ $type ] ) ? $animations[ $type ] : $animations['spin'];
    }

    /**
     * Get menu position CSS
     *
     * @param string $position Menu position
     * @param int    $breakpoint Mobile breakpoint
     * @param array  $settings Full settings array
     * @return string CSS code
     */
    private function get_menu_position( $position, $breakpoint, $settings = array() ) {
        $cls = $this->cls;
        $submenu_behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';

        $positions = array(
            'left' => "/* ----------------------------------------
   Mobile Position: Left
   ---------------------------------------- */
@media (max-width: {$breakpoint}px) {
  .{$cls}--left {
    top: 0;
    left: 0;
    width: var(--menu-mobile-width);
    height: 100dvh;
    transform: translateX(-100%);

    &.is-open {
      transform: translateX(0);
    }
  }
}\n\n",
            'right' => "/* ----------------------------------------
   Mobile Position: Right
   ---------------------------------------- */
@media (max-width: {$breakpoint}px) {
  .{$cls}--right {
    top: 0;
    right: 0;
    left: auto;
    width: var(--menu-mobile-width);
    height: 100dvh;
    transform: translateX(100%);

    &.is-open {
      transform: translateX(0);
    }
  }
}\n\n",
            'top' => "/* ----------------------------------------
   Mobile Position: Top
   ---------------------------------------- */
@media (max-width: {$breakpoint}px) {
  .{$cls}--top {
    top: 0;
    left: 0;
    width: 100%;
    max-height: 100dvh;
    transform: translateY(-100%);" . ( 'accordion' === $submenu_behavior ? "

    &.{$cls}--accordion {
      height: auto;
    }" : '' ) . ( 'slide' === $submenu_behavior ? "

    &.{$cls}--slide {
      height: 100dvh;
    }" : '' ) . "

    &.is-open {
      transform: translateY(0);
    }
  }
}\n\n",
        );

        return isset( $positions[ $position ] ) ? $positions[ $position ] : $positions['left'];
    }

    /**
     * Get menu JSON for preview
     *
     * @param int $menu_id WordPress menu ID
     * @return string JSON representation of menu
     */
    public function get_menu_json( $menu_id ) {
        $menu_items = wp_get_nav_menu_items( $menu_id );

        if ( ! $menu_items ) {
            return json_encode( array( 'error' => 'Menu not found or empty' ), JSON_PRETTY_PRINT );
        }

        // Let WordPress compute current-menu-item, current-menu-parent,
        // current-menu-ancestor classes on each menu item.
        // This only works on the frontend where $wp_query is populated.
        if ( ! is_admin() ) {
            _wp_menu_item_classes_by_context( $menu_items );
        }

        $menu_tree = array();
        $menu_by_id = array();

        // Use WordPress's native current/parent/ancestor detection from the classes array
        foreach ( $menu_items as $item ) {
            $classes = is_array( $item->classes ) ? $item->classes : array();
            $menu_by_id[ $item->ID ] = array(
                'id'             => $item->ID,
                'title'          => $item->title,
                'url'            => $item->url,
                'target'         => $item->target,
                'classes'        => implode( ' ', array_filter( $classes ) ),
                'current'        => in_array( 'current-menu-item', $classes, true ),
                'current_parent' => in_array( 'current-menu-parent', $classes, true )
                                 || in_array( 'current-menu-ancestor', $classes, true ),
                'children'       => array(),
            );
        }

        // Build hierarchy
        foreach ( $menu_items as $item ) {
            if ( $item->menu_item_parent == 0 ) {
                $menu_tree[] = &$menu_by_id[ $item->ID ];
            } else {
                if ( isset( $menu_by_id[ $item->menu_item_parent ] ) ) {
                    $menu_by_id[ $item->menu_item_parent ]['children'][] = &$menu_by_id[ $item->ID ];
                }
            }
        }

        // Compute state_classes and link_classes after hierarchy is built
        foreach ( $menu_by_id as &$menu_item ) {
            $state      = array();
            $link_state = array();
            if ( ! empty( $menu_item['current'] ) ) {
                $link_state[] = 'current-page';
            }
            if ( ! empty( $menu_item['current_parent'] ) ) {
                $state[] = 'current-parent';
            }
            if ( ! empty( $menu_item['children'] ) ) {
                $state[] = 'has-submenu';
            }
            $menu_item['state_classes'] = implode( ' ', $state );
            $menu_item['link_classes']  = implode( ' ', $link_state );
        }
        unset( $menu_item );

        return json_encode( $menu_tree, JSON_PRETTY_PRINT );
    }

    /**
     * Generate complete ETCH JSON structure using proper editable block tree
     *
     * Produces etch/element, etch/loop, etch/condition, etch/text blocks
     * that are fully editable in the ETCH builder Structure Panel.
     *
     * @param array $settings User settings
     * @return string JSON for ETCH structure panel
     */
    public function generate_etch_json( $settings ) {
        $this->cls = $this->get_css_class( $settings );
        $css = $this->generate_css( $settings );
        $js = $this->generate_javascript( $settings );
        $has_mobile = $this->has_mobile_support( $settings );
        $menu_name = $this->get_menu_name( $settings );
        $approach = isset( $settings['approach'] ) ? $settings['approach'] : 'direct';
        $desktop_depth = isset( $settings['submenu_depth_desktop'] ) ? intval( $settings['submenu_depth_desktop'] ) : 1;
        $cls = $this->cls;

        // Build the loop target
        if ( 'component' === $approach ) {
            $prop_name = isset( $settings['component_prop_name'] ) && ! empty( $settings['component_prop_name'] )
                ? $this->sanitize_prop_name( $settings['component_prop_name'] )
                : 'menuItems';
            $loop_target = 'props.' . $prop_name;
        } else {
            $loop_target = 'options.menus.' . $menu_name;
        }

        // Build styles collection
        $styles = $this->build_etch_styles( $settings );

        // --- Build the block tree ---
        $menu_item_children = array();

        // Link element with text — uses {item.link_classes} for current-page
        $link_block = $this->make_element_block(
            'Menu Link',
            'a',
            array(
                'href'  => '{item.url}',
                'class' => $cls . '__link {item.link_classes}',
                'role'  => 'menuitem',
            ),
            array( $cls . '-link' ),
            array(
                $this->make_text_block( 'Menu Label', '{item.title}' ),
            )
        );
        $menu_item_children[] = $link_block;

        // Submenu condition + toggle button + icon span + nested loop
        if ( $desktop_depth > 0 ) {
            // Toggle button with icon span child
            $icon_span = $this->make_element_block(
                'Submenu Icon',
                'span',
                array(
                    'class'       => $cls . '__submenu-icon',
                    'aria-hidden' => 'true',
                ),
                array( $cls . '-submenu-icon' )
            );

            $toggle_button = $this->make_element_block(
                'Submenu Toggle',
                'button',
                array(
                    'class'      => $cls . '__submenu-toggle',
                    'aria-label' => 'Toggle submenu',
                    'tabindex'   => '-1',
                ),
                array( $cls . '-submenu-toggle' ),
                array( $icon_span )
            );

            $submenu_block = $this->build_submenu_blocks( 1, $desktop_depth, $approach );
            $condition_block = $this->make_condition_block(
                'If Has Children',
                'item.children',
                'isTruthy',
                null,
                array( $toggle_button, $submenu_block )
            );
            $menu_item_children[] = $condition_block;
        }

        // Menu item li — uses {item.state_classes} for has-submenu, current-parent
        $menu_item_block = $this->make_element_block(
            'Menu Item',
            'li',
            array(
                'class'      => $cls . '__item {item.state_classes}',
                'role'       => 'none',
                'data-level' => '1',
            ),
            array( $cls . '-item' ),
            $menu_item_children
        );

        // Loop block
        $loop_block = $this->make_loop_block(
            'Menu Items',
            $loop_target,
            array( $menu_item_block )
        );

        // UL — __list
        $ul_block = $this->make_element_block(
            'Menu List',
            'ul',
            array(
                'class' => $cls . '__list',
                'role'  => 'menubar',
            ),
            array( $cls . '-list' ),
            array( $loop_block )
        );

        // Nav element — no modifier classes (ETCH controls class attribute via styles).
        // Position and behaviour are stored as data attributes for JS to read.
        $nav_attrs = array(
            'class'      => $cls,
            'id'         => $cls . '-nav',
            'role'       => 'navigation',
            'aria-label' => 'Main navigation',
        );

        // Build nav children.
        // ARCHITECTURE: The hamburger must be a SIBLING of the __menu wrapper,
        // NOT inside it. On mobile, __menu gets position:fixed + transform to
        // slide off-screen. If the hamburger were inside __menu, it would slide
        // off-screen too and be invisible. As a sibling, it stays in flow.
        //
        // Structure: nav > [hamburger, __menu > ul > loop > items]
        $nav_inner_blocks = array();

        if ( $has_mobile ) {
            $position = isset( $settings['menu_position'] ) ? $settings['menu_position'] : 'left';
            $behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';
            $nav_attrs['data-position'] = $position;
            $nav_attrs['data-behavior'] = $behavior;

            // Hamburger as first child of nav (sibling of __menu)
            $nav_inner_blocks[] = $this->build_hamburger_block();

            // __menu wrapper div — THIS element gets position:fixed on mobile.
            // The hamburger stays outside it, remaining visible.
            $menu_wrapper = $this->make_element_block(
                'Menu Panel',
                'div',
                array(
                    'class' => $cls . '__menu',
                ),
                array( $cls . '-menu-all' ),
                array( $ul_block )
            );
            $nav_inner_blocks[] = $menu_wrapper;
        } else {
            // No mobile — no wrapper needed, UL goes directly in nav
            $nav_inner_blocks[] = $ul_block;
        }

        // Build nav style keys.
        // CRITICAL: ETCH deduplicates styles by selector — only the LAST style with a
        // given selector survives. ALL .{cls} CSS is combined into one 'nav-all' style.
        $nav_style_keys = array( $cls . '-nav-all' );

        $nav_element = $this->make_element_block(
            'Navigation',
            'nav',
            $nav_attrs,
            $nav_style_keys,
            $nav_inner_blocks
        );

        // Attach script to nav element if mobile support is enabled
        if ( $has_mobile && ! empty( $js ) ) {
            $nav_element['attrs']['script'] = array(
                'code' => base64_encode( $js ),
                'id'   => $cls . '-' . time(),
            );
        }

        // Nav is the root block — no wrapper div needed
        $root_block = $nav_element;

        // Top-level ETCH structure
        $etch_structure = array(
            'type'           => 'block',
            'version'        => 2,
            'gutenbergBlock' => $root_block,
            'styles'         => $styles,
        );

        return json_encode( $etch_structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }

    /**
     * Build hamburger button block with three line spans
     *
     * @return array Hamburger button element block
     */
    private function build_hamburger_block() {
        $cls = $this->cls;

        // Line spans get only the base hamburger-line style.
        // Animation styles (hamburger-active-line1/2/3) are left unreferenced
        // in the styles collection — ETCH outputs all styles in the collection
        // regardless of block references. We must NOT attach them here because
        // ETCH adds the style selector's class to referencing blocks.
        $line1 = $this->make_element_block( 'Line 1', 'span', array( 'class' => $cls . '__hamburger-line' ), array( $cls . '-hamburger-line' ) );
        $line2 = $this->make_element_block( 'Line 2', 'span', array( 'class' => $cls . '__hamburger-line' ), array( $cls . '-hamburger-line' ) );
        $line3 = $this->make_element_block( 'Line 3', 'span', array( 'class' => $cls . '__hamburger-line' ), array( $cls . '-hamburger-line' ) );

        $button_attrs = array(
            'class'         => $cls . '__hamburger',
            'type'          => 'button',
            'aria-controls' => $cls . '-nav',
            'aria-expanded' => 'false',
            'aria-label'    => 'Toggle navigation menu',
        );

        // Hamburger button gets base style only.
        // Mobile display: flex comes from the mobile-responsive @media block
        // (which targets {$s}__hamburger as a descendant of the nav element).
        // ETCH outputs all styles in the collection regardless of block refs.
        return $this->make_element_block(
            'Hamburger Button',
            'button',
            $button_attrs,
            array( $cls . '-hamburger' ),
            array( $line1, $line2, $line3 )
        );
    }

    /**
     * Build submenu blocks recursively for the ETCH block tree
     *
     * @param int    $depth     Current depth level
     * @param int    $max_depth Maximum depth
     * @param string $approach  direct or component
     * @return array Submenu UL element block
     */
    private function build_submenu_blocks( $depth, $max_depth, $approach = 'direct' ) {
        $cls = $this->cls;
        $data_level = $depth + 1;

        // In the ETCH block tree, loop blocks always use 'item' as the iterator
        // variable. Each nested loop scopes 'item' to its own level automatically.
        $var_name = 'item';

        // Submenu link with text — shared __link class, uses {item.link_classes}
        $link_block = $this->make_element_block(
            'Submenu Link',
            'a',
            array(
                'href'  => '{' . $var_name . '.url}',
                'class' => $cls . '__link {' . $var_name . '.link_classes}',
                'role'  => 'menuitem',
            ),
            array( $cls . '-link' ),
            array(
                $this->make_text_block( 'Submenu Label', '{' . $var_name . '.title}' ),
            )
        );

        $li_children = array( $link_block );

        // Recurse for deeper submenus
        if ( $depth < $max_depth ) {
            // Toggle button with icon span
            $icon_span = $this->make_element_block(
                'Submenu Icon',
                'span',
                array(
                    'class'       => $cls . '__submenu-icon',
                    'aria-hidden' => 'true',
                ),
                array( $cls . '-submenu-icon' )
            );

            $toggle_button = $this->make_element_block(
                'Submenu Toggle',
                'button',
                array(
                    'class'      => $cls . '__submenu-toggle',
                    'aria-label' => 'Toggle submenu',
                    'tabindex'   => '-1',
                ),
                array( $cls . '-submenu-toggle' ),
                array( $icon_span )
            );

            $deeper_submenu = $this->build_submenu_blocks( $depth + 1, $max_depth, $approach );
            $condition_block = $this->make_condition_block(
                'If Has Children',
                $var_name . '.children',
                'isTruthy',
                null,
                array( $toggle_button, $deeper_submenu )
            );
            $li_children[] = $condition_block;
        }

        // Submenu item li — shared __item class, uses {item.state_classes}
        $li_block = $this->make_element_block(
            'Submenu Item',
            'li',
            array(
                'class'      => $cls . '__item {' . $var_name . '.state_classes}',
                'role'       => 'none',
                'data-level' => (string) $data_level,
            ),
            array( $cls . '-item' ),
            $li_children
        );

        // Loop over children
        $loop_block = $this->make_loop_block(
            'Submenu Loop',
            'item.children',
            array( $li_block )
        );

        // Submenu UL — __sub-menu with role="menu"
        // Attach desktop positioning, hover reveal, and nested cascade styles
        return $this->make_element_block(
            'Sub-menu Level ' . $depth,
            'ul',
            array(
                'class' => $cls . '__sub-menu',
                'role'  => 'menu',
            ),
            array( $cls . '-sub-menu' ),
            array( $loop_block )
        );
    }

    /**
     * Build the styles collection for the ETCH JSON
     *
     * Each style is decomposed into its own key with flat CSS properties.
     *
     * @param array $settings User settings
     * @return array Styles collection keyed by identifier
     */
    private function build_etch_styles( $settings ) {
        $cls = $this->cls;
        $has_mobile = $this->has_mobile_support( $settings );
        $desktop_depth = isset( $settings['submenu_depth_desktop'] ) ? intval( $settings['submenu_depth_desktop'] ) : 1;
        $breakpoint = isset( $settings['mobile_breakpoint'] ) ? intval( $settings['mobile_breakpoint'] ) : 1200;
        $breakpoint_up = $breakpoint + 1;

        $styles = array();

        // NOTE: CSS custom properties (:root tokens) are only in the CSS tab output.
        // ETCH flat styles use hardcoded values because ETCH style objects with
        // selector ':root' and type 'class' may not render correctly.

        // IMPORTANT: ETCH deduplicates/merges styles that share the same selector.
        // Only the LAST style with a given selector value survives in the rendered CSS.
        // Therefore, ALL CSS for selector '.{cls}' must be combined into ONE style entry.
        // The base nav styles are merged into the combined 'nav-all' style below
        // (inside get_flat_mobile_css), NOT declared as a separate style here.

        // Hamburger (only if mobile)
        if ( $has_mobile ) {
            $styles[ $cls . '-hamburger' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__hamburger',
                'collection' => 'default',
                'css'        => "display: none;\nposition: absolute;\ntop: 0;\nleft: 0;\nbackground: #ffffff;\nborder: none;\ncursor: pointer;\npadding: 0;\nz-index: 1001;\nflex-direction: column;\njustify-content: center;\nalign-items: center;\nwidth: 30px;\nheight: 30px;\ngap: 5px;",
                'readonly'   => false,
            );

            $styles[ $cls . '-hamburger-line' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__hamburger-line',
                'collection' => 'default',
                'css'        => "display: block;\nwidth: 30px;\nheight: 3px;\nbackground-color: #2c3338;\nborder-radius: 3px;\ntransition: all 0.4s ease;\ntransform-origin: center center;",
                'readonly'   => false,
            );

            // Menu wrapper base style removed — its CSS is provided by the
            // 'menu-all' style from get_flat_mobile_css() which has the same
            // selector. ETCH deduplicates by selector, so only one can exist.
        }

        // Navigation list
        $styles[ $cls . '-list' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__list',
            'collection' => 'default',
            'css'        => "list-style: none;\nmargin: 0;\npadding: 0;\ndisplay: flex;",
            'readonly'   => false,
        );

        // Navigation item
        $styles[ $cls . '-item' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__item',
            'collection' => 'default',
            'css'        => 'position: relative;',
            'readonly'   => false,
        );

        // Navigation link — shared across all levels
        $styles[ $cls . '-link' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__link',
            'collection' => 'default',
            'css'        => "display: block;\npadding: 0.75rem 1.25rem;\ncolor: #2c3338;\ntext-decoration: none;\nfont-weight: 500;\ntransition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;",
            'readonly'   => false,
        );

        // Link hover
        $styles[ $cls . '-link-hover' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__link:hover',
            'collection' => 'default',
            'css'        => "background-color: #f9f9f9;\ncolor: #0073aa;",
            'readonly'   => false,
        );

        // Current page link
        $styles[ $cls . '-link-current' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__link.current-page',
            'collection' => 'default',
            'css'        => 'color: #0073aa;',
            'readonly'   => false,
        );

        // Current parent indicator
        $styles[ $cls . '-item-current-parent' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__item.current-parent > .' . $cls . '__link',
            'collection' => 'default',
            'css'        => 'color: #0073aa;',
            'readonly'   => false,
        );

        // Sub-menu styles (only if depth > 0)
        // IMPORTANT: Desktop dropdown positioning (position:absolute, opacity:0,
        // visibility:hidden) MUST be inside @media desktop-only. On mobile, submenus
        // are handled by accordion/slide JS — the desktop hover/float behaviour must
        // NOT leak into mobile, or submenus appear as floating blocks on hover.
        if ( $desktop_depth > 0 ) {
            $styles[ $cls . '-sub-menu' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__sub-menu',
                'collection' => 'default',
                'css'        => "list-style: none;\nmargin: 0;\npadding: 0;\n@media (min-width: {$breakpoint_up}px) {\n  position: absolute;\n  top: 100%;\n  left: 0;\n  min-width: 200px;\n  background: #ffffff;\n  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);\n  border-radius: 0px;\n  opacity: 0;\n  visibility: hidden;\n  transform: translateY(-10px);\n  transition: all 0.2s ease-in-out;\n  z-index: 1;\n}",
                'readonly'   => false,
            );

            // Show on hover/focus — desktop only
            $styles[ $cls . '-sub-menu-hover' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__item:hover > .' . $cls . '__sub-menu, .' . $cls . '__item:focus-within > .' . $cls . '__sub-menu',
                'collection' => 'default',
                'css'        => "@media (min-width: {$breakpoint_up}px) {\n  opacity: 1;\n  visibility: visible;\n  transform: translateY(0);\n  transition-delay: 0.15s;\n}",
                'readonly'   => false,
            );

            // Nested sub-menus cascade right — desktop only
            $styles[ $cls . '-sub-menu-nested' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__sub-menu .' . $cls . '__sub-menu',
                'collection' => 'default',
                'css'        => "@media (min-width: {$breakpoint_up}px) {\n  top: 0;\n  left: 100%;\n  transform: translateX(1px);\n}",
                'readonly'   => false,
            );
        }

        // Submenu toggle button — hidden on desktop
        $styles[ $cls . '-submenu-toggle' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__submenu-toggle',
            'collection' => 'default',
            'css'        => 'display: none;',
            'readonly'   => false,
        );

        // Submenu icon
        $styles[ $cls . '-submenu-icon' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__submenu-icon',
            'collection' => 'default',
            'css'        => '',
            'readonly'   => false,
        );

        // Mobile / responsive styles
        if ( $has_mobile ) {
            $menu_position = isset( $settings['menu_position'] ) ? $settings['menu_position'] : 'left';
            $animation_type = isset( $settings['hamburger_animation'] ) ? $settings['hamburger_animation'] : 'spin';
            $submenu_behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';

            // Hamburger animation — flat selectors for is-active state
            $hamburger_animations = $this->get_flat_hamburger_animation( $animation_type );
            foreach ( $hamburger_animations as $key => $anim ) {
                $styles[ $cls . '-' . $key ] = array(
                    'type'       => 'class',
                    'selector'   => $anim['selector'],
                    'collection' => 'default',
                    'css'        => $anim['css'],
                    'readonly'   => false,
                );
            }

            // Mobile responsive styles
            $mobile_css = $this->get_flat_mobile_css( $menu_position, $breakpoint, $submenu_behavior );
            foreach ( $mobile_css as $key => $rule ) {
                $styles[ $cls . '-' . $key ] = array(
                    'type'       => 'class',
                    'selector'   => $rule['selector'],
                    'collection' => $rule['collection'],
                    'css'        => $rule['css'],
                    'readonly'   => false,
                );
            }

            // Body scroll lock is handled by JS inline (document.body.style.overflow).
            // Cannot use a CSS style here because body is not inside .{cls} and ETCH
            // wrapping would create an invalid descendant selector.
        }

        return $styles;
    }

    /**
     * Get flat (non-nested) hamburger animation CSS for ETCH styles
     *
     * @param string $type Animation type
     * @return array Array of style rules with selector and css
     */
    private function get_flat_hamburger_animation( $type ) {
        $cls = $this->cls;
        $base = '.' . $cls . '__hamburger.is-active .' . $cls . '__hamburger-line';

        // Hamburger geometry: 3 lines, each 3px tall, with 5px gap (flexbox).
        // Center-to-center distance between adjacent lines = gap(5) + lineHeight(3) = 8px.
        $animations = array(
            'spin' => array(
                'line1' => "transform: translateY(8px) rotate(225deg);",
                'line2' => "opacity: 0;\ntransform: scaleX(0);",
                'line3' => "transform: translateY(-8px) rotate(-225deg);",
            ),
            'squeeze' => array(
                'line1' => "transform: translateY(8px) rotate(45deg);",
                'line2' => "opacity: 0;\ntransform: scaleX(0);",
                'line3' => "transform: translateY(-8px) rotate(-45deg);",
            ),
            'collapse' => array(
                'line1' => "transform: translateY(8px) rotate(-45deg);",
                'line2' => "opacity: 0;",
                'line3' => "transform: translateY(-8px) rotate(45deg);",
            ),
        );

        $anim = isset( $animations[ $type ] ) ? $animations[ $type ] : $animations['spin'];

        return array(
            'hamburger-active-line1' => array(
                'selector' => $base . ':nth-child(1)',
                'css'      => $anim['line1'],
            ),
            'hamburger-active-line2' => array(
                'selector' => $base . ':nth-child(2)',
                'css'      => $anim['line2'],
            ),
            'hamburger-active-line3' => array(
                'selector' => $base . ':nth-child(3)',
                'css'      => $anim['line3'],
            ),
        );
    }

    /**
     * Get flat mobile CSS for ETCH styles
     *
     * CRITICAL ARCHITECTURE:
     * The <nav> stays in normal document flow at all times.
     * A __menu wrapper div INSIDE the nav gets position:fixed + transform to
     * slide off-screen on mobile. The hamburger is a SIBLING of __menu (both
     * inside nav), so it stays visible when __menu slides away.
     *
     * Structure: nav > [hamburger (visible), __menu (slides) > ul > items]
     *
     * ETCH CONSTRAINTS:
     * 1. ETCH deduplicates styles by selector — only the LAST style with a
     *    given selector survives. Each selector must appear exactly ONCE.
     * 2. ETCH wraps 'css' inside 'selector', so nested selectors become
     *    descendant selectors (e.g. .nav { .nav__list { } } → .nav .nav__list).
     *
     * @param string $position         Menu position (left, right, top)
     * @param int    $breakpoint       Mobile breakpoint in px
     * @param string $submenu_behavior Submenu behavior (accordion, slide)
     * @return array Array of style rules containing responsive CSS
     */
    private function get_flat_mobile_css( $position, $breakpoint, $submenu_behavior = 'accordion' ) {
        $cls = $this->cls;
        $s   = '.' . $cls;
        $breakpoint_up = $breakpoint + 1;

        // --- Position-specific __menu values (for mobile @media) ---
        // These apply to __menu, NOT the nav. The menu panel slides; nav stays put.
        switch ( $position ) {
            case 'right':
                $menu_position_props = "position: fixed;\n  z-index: 999;\n  overflow-y: auto;\n  padding-top: 80px;\n  background: #ffffff;\n  top: 0;\n  right: 0;\n  left: auto;\n  width: 100%;\n  height: 100dvh;\n  transform: translateX(100%);\n  transition: transform 0.2s ease-in-out;";
                $menu_open_css       = 'transform: translateX(0);';
                break;
            case 'top':
                $menu_position_props = "position: fixed;\n  z-index: 999;\n  overflow-y: auto;\n  padding-top: 80px;\n  background: #ffffff;\n  top: 0;\n  left: 0;\n  width: 100%;\n  max-height: 100dvh;\n  transform: translateY(-100%);\n  transition: transform 0.2s ease-in-out;";
                $menu_open_css       = 'transform: translateY(0);';
                if ( 'accordion' === $submenu_behavior ) {
                    $menu_position_props .= "\n  height: auto;";
                } elseif ( 'slide' === $submenu_behavior ) {
                    $menu_position_props .= "\n  height: 100dvh;";
                }
                break;
            case 'left':
            default:
                $menu_position_props = "position: fixed;\n  z-index: 999;\n  overflow-y: auto;\n  padding-top: 80px;\n  background: #ffffff;\n  top: 0;\n  left: 0;\n  width: 100%;\n  height: 100dvh;\n  transform: translateX(-100%);\n  transition: transform 0.2s ease-in-out;";
                $menu_open_css       = 'transform: translateX(0);';
                break;
        }

        // Slide mode adds overflow: hidden on __menu
        if ( 'slide' === $submenu_behavior ) {
            $menu_position_props .= "\n  overflow: hidden;";
        }

        // =====================================================================
        // STYLE 1: 'nav-all' — selector '.{cls}' (the ONLY style with this selector)
        //
        // Contains: base nav properties + mobile child-element overrides + desktop layout.
        // The nav stays in normal flow. Position:fixed is on __menu, not here.
        // =====================================================================

        // Base nav properties (all widths)
        $nav_css = "background: #ffffff;\ncolor: #2c3338;\n";

        // Mobile @media — child elements only (all are descendants of nav)
        // Nav needs position:relative so the absolutely-positioned hamburger
        // is positioned relative to the nav, not the viewport.
        $nav_css .= "@media (max-width: {$breakpoint}px) {\n"
            . "  position: relative;\n  z-index: 1000;\n"
            . "  {$s}__hamburger { display: flex; }\n"
            . "  {$s}__list { flex-direction: column; border-top: 1px solid #f0f0f1; }\n"
            . "  {$s}__item { width: 100%; border-bottom: 1px solid #f0f0f1; }\n"
            . "  .has-submenu > {$s}__link { padding-right: calc(50px + 1.25rem); }\n"
            . "  {$s}__submenu-toggle { display: flex; position: absolute; top: 0; right: 0; width: 50px; height: 50px; background: transparent; border: none; cursor: pointer; padding: 0; z-index: 1; align-items: center; justify-content: center; }\n"
            . "  {$s}__submenu-icon { display: block; width: 8px; height: 8px; border-right: 2px solid #2c3338; border-bottom: 2px solid #2c3338; transform: rotate(45deg); transition: transform 0.2s ease-in-out; }\n"
            . "  {$s}__sub-menu { border-top: 1px solid #f0f0f1; }\n"
            . "  {$s}__sub-menu {$s}__item:last-of-type { border-bottom: 0; }\n"
            . "  {$s}__sub-menu {$s}__item a { padding-left: 40px; }\n"
            . "  {$s}__sub-menu {$s}__sub-menu {$s}__item a { padding-left: 70px; }\n";

        // Behaviour-specific child-element styles (still inside mobile @media)
        if ( 'accordion' === $submenu_behavior ) {
            $nav_css .= "  {$s}__sub-menu { display: none; max-height: 0; overflow: hidden; transition: max-height 0.2s ease-in-out; }\n"
                . "  {$s}__item--submenu-open > {$s}__sub-menu { display: block; max-height: 1000px; }\n"
                . "  {$s}__item--submenu-open > {$s}__submenu-toggle {$s}__submenu-icon { transform: rotate(-135deg); }\n";
        } elseif ( 'slide' === $submenu_behavior ) {
            // Hide ONLY the original __list (direct child of __menu), not the
            // cloned __list elements inside sliding panels.
            $nav_css .= "  {$s}__menu > {$s}__list { display: none; }\n"
                // Sliding panel lists must be visible
                . "  .sliding-panel {$s}__list { display: flex; flex-direction: column; }\n"
                . "  .sliding-nav-panels { position: absolute; top: 80px; left: 0; width: 100%; height: calc(100% - 80px); border-top: 1px solid #f0f0f1; }\n"
                . "  .sliding-panel { position: absolute; top: 0; left: 0; width: 100%; height: 100%; transform: translateX(100%); opacity: 0; transition: transform 0.2s ease-in-out, opacity 0.2s ease-in-out; pointer-events: none; overflow-y: auto; }\n"
                . "  .sliding-panel.active { transform: translateX(0); opacity: 1; pointer-events: auto; }\n"
                . "  .sliding-panel.previous { transform: translateX(-100%); opacity: 0; pointer-events: none; }\n"
                . "  {$s}__submenu-icon { transform: rotate(-45deg); }\n"
                . "  {$s}__back { display: block; border-bottom: 1px solid #f0f0f1; }\n"
                . "  {$s}__back-button { width: 100%; padding: 0.75rem 1.25rem; background: #ffffff; border: none; text-align: left; font-size: 1rem; color: #2c3338; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }\n"
                . "  {$s}__back-icon { display: inline-block; width: 8px; height: 8px; border-left: 2px solid #2c3338; border-bottom: 2px solid #2c3338; transform: rotate(45deg); }\n";
        }

        $nav_css .= "}\n";

        // Desktop @media — nav layout + child elements
        $nav_css .= "@media (min-width: {$breakpoint_up}px) {\n"
            . "  position: relative;\n  width: 100%;\n"
            . "  {$s}__list { flex-direction: row; gap: 0.5rem; align-items: center; }\n"
            . "}";

        // =====================================================================
        // STYLE 2: 'menu-all' — selector '.{cls}__menu' (unique selector)
        //
        // The __menu wrapper div — gets position:fixed + transform on mobile.
        // The hamburger is a sibling, so it stays visible.
        // =====================================================================
        $menu_css = "@media (max-width: {$breakpoint}px) {\n"
            . "  {$menu_position_props}\n"
            . "}";

        // =====================================================================
        // STYLE 3: 'menu-mobile-open' — selector '.{cls}__menu.is-open' (unique)
        //
        // When JS adds .is-open to __menu, it slides into view.
        // =====================================================================
        $menu_open_state_css = "@media (max-width: {$breakpoint}px) {\n"
            . "  {$menu_open_css}\n"
            . "}";

        return array(
            // Nav base + child element responsive CSS (only style with .{cls} selector)
            'nav-all' => array(
                'selector'   => $s,
                'collection' => 'default',
                'css'        => $nav_css,
            ),
            // Menu panel positioning (unique selector .{cls}__menu)
            'menu-all' => array(
                'selector'   => $s . '__menu',
                'collection' => 'default',
                'css'        => $menu_css,
            ),
            // Menu panel open state (unique selector .{cls}__menu.is-open)
            'menu-mobile-open' => array(
                'selector'   => $s . '__menu.is-open',
                'collection' => 'default',
                'css'        => $menu_open_state_css,
            ),
        );
    }

    /**
     * Create an etch/element block
     *
     * @param string $name        Display name for the Structure Panel
     * @param string $tag         HTML tag
     * @param array  $attributes  HTML attributes (class, href, aria-*, etc.)
     * @param array  $style_keys  Style keys referencing top-level styles
     * @param array  $inner_blocks Child blocks (optional)
     * @return array Block structure
     */
    private function make_element_block( $name, $tag, $attributes = array(), $style_keys = array(), $inner_blocks = array() ) {
        $attrs = array(
            'metadata'   => array( 'name' => $name ),
            'tag'        => $tag,
            'attributes' => ! empty( $attributes ) ? $attributes : new \stdClass(),
        );

        if ( ! empty( $style_keys ) ) {
            $attrs['styles'] = $style_keys;
        }

        $block = array(
            'blockName'    => 'etch/element',
            'attrs'        => $attrs,
            'innerBlocks'  => $inner_blocks,
            'innerHTML'    => $this->make_inner_html( count( $inner_blocks ) ),
            'innerContent' => $this->make_inner_content( count( $inner_blocks ) ),
        );

        return $block;
    }

    /**
     * Create an etch/text block
     *
     * @param string $name    Display name
     * @param string $content Text content (can include ETCH template vars)
     * @return array Block structure
     */
    private function make_text_block( $name, $content ) {
        return array(
            'blockName'    => 'etch/text',
            'attrs'        => array(
                'metadata' => array( 'name' => $name ),
                'content'  => $content,
            ),
            'innerBlocks'  => array(),
            'innerHTML'    => '',
            'innerContent' => array(),
        );
    }

    /**
     * Create an etch/loop block
     *
     * @param string $name         Display name
     * @param string $target       Data source path (e.g., options.menus.primary_menu)
     * @param array  $inner_blocks Child blocks rendered per iteration
     * @return array Block structure
     */
    private function make_loop_block( $name, $target, $inner_blocks = array() ) {
        return array(
            'blockName'    => 'etch/loop',
            'attrs'        => array(
                'metadata' => array( 'name' => $name ),
                'target'   => $target,
            ),
            'innerBlocks'  => $inner_blocks,
            'innerHTML'    => $this->make_inner_html( count( $inner_blocks ) ),
            'innerContent' => $this->make_inner_content( count( $inner_blocks ) ),
        );
    }

    /**
     * Create an etch/condition block
     *
     * @param string $name       Display name
     * @param string $left_hand  Left operand (e.g., item.children)
     * @param string $operator   Comparison operator (e.g., isTruthy)
     * @param mixed  $right_hand Right operand (null for truthy checks)
     * @param array  $inner_blocks Child blocks rendered when condition is true
     * @return array Block structure
     */
    private function make_condition_block( $name, $left_hand, $operator, $right_hand = null, $inner_blocks = array() ) {
        $attrs = array(
            'metadata'        => array( 'name' => $name ),
            'conditionString' => $left_hand,
            'condition'       => array(
                'leftHand'  => $left_hand,
                'operator'  => $operator,
                'rightHand' => $right_hand,
            ),
        );

        return array(
            'blockName'    => 'etch/condition',
            'attrs'        => $attrs,
            'innerBlocks'  => $inner_blocks,
            'innerHTML'    => $this->make_inner_html( count( $inner_blocks ) ),
            'innerContent' => $this->make_inner_content( count( $inner_blocks ) ),
        );
    }

    /**
     * Generate innerHTML for a block based on number of children
     *
     * Follows Gutenberg serialization convention:
     * - 0 children: "\n\n"
     * - N children: "\n" + ("\n\n" × N-1 separators) + "\n"
     *
     * @param int $child_count Number of inner blocks
     * @return string innerHTML value
     */
    private function make_inner_html( $child_count ) {
        if ( 0 === $child_count ) {
            return "\n\n";
        }

        $html = "\n";
        for ( $i = 0; $i < $child_count; $i++ ) {
            if ( $i < $child_count - 1 ) {
                $html .= "\n\n";
            } else {
                $html .= "\n";
            }
        }

        return $html;
    }

    /**
     * Generate innerContent array for a block based on number of children
     *
     * Follows Gutenberg serialization convention:
     * - 0 children: ["\n", "\n"]
     * - 1 child: ["\n", null, "\n"]
     * - 2 children: ["\n", null, "\n\n", null, "\n"]
     * - N children: ["\n", null, "\n\n", null, ... null, "\n"]
     *
     * @param int $child_count Number of inner blocks
     * @return array innerContent value
     */
    private function make_inner_content( $child_count ) {
        if ( 0 === $child_count ) {
            return array( "\n", "\n" );
        }

        $content = array( "\n" );

        for ( $i = 0; $i < $child_count; $i++ ) {
            $content[] = null;
            if ( $i < $child_count - 1 ) {
                $content[] = "\n\n";
            } else {
                $content[] = "\n";
            }
        }

        return $content;
    }
}
