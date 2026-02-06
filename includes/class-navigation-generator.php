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
    private $cls = 'global-nav';

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

        return 'global-nav';
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

        $html = "\n{$indent}{#if {$var_name}.children}\n";
        $html .= "{$indent}  <ul class=\"{$cls}__submenu {$cls}__submenu--level-{$depth}\">\n";
        $html .= "{$indent}    {#loop {$var_name}.children as {$next_var}}\n";

        $sub_li_classes = "{$cls}__submenu-item {#if {$next_var}.current}is-current{/if} {#if {$next_var}.current_parent}is-current-parent{/if}";
        if ( $depth < $max_depth ) {
            $sub_li_classes .= " {#if {$next_var}.children}has-submenu{/if}";
        }
        $html .= "{$indent}      <li class=\"{$sub_li_classes}\" role=\"none\">\n";

        $html .= "{$indent}        <a href=\"{{$next_var}.url}\" \n";
        $html .= "{$indent}           class=\"{$cls}__submenu-link {#if {$next_var}.current}is-active{/if}\" \n";
        $html .= "{$indent}           role=\"menuitem\">\n";
        $html .= "{$indent}          {{$next_var}.title}\n";
        $html .= "{$indent}        </a>";

        // A3: Toggle button for submenu items with children
        if ( $depth < $max_depth ) {
            $html .= "\n{$indent}        {#if {$next_var}.children}\n";
            $html .= "{$indent}        <button class=\"{$cls}__submenu-toggle\" aria-label=\"Toggle submenu\"></button>\n";
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

        $html = "\n{$indent}{#if {$var_name}.children}\n";
        $html .= "{$indent}  <ul class=\"{$cls}__submenu {$cls}__submenu--level-{$depth}\">\n";
        $html .= "{$indent}    {#loop {$var_name}.children as {$next_var}}\n";

        $sub_li_classes = "{$cls}__submenu-item {#if {$next_var}.current}is-current{/if} {#if {$next_var}.current_parent}is-current-parent{/if}";
        if ( $depth < $max_depth ) {
            $sub_li_classes .= " {#if {$next_var}.children}has-submenu{/if}";
        }
        $html .= "{$indent}      <li class=\"{$sub_li_classes}\" role=\"none\">\n";

        $html .= "{$indent}        <a href=\"{{$next_var}.url}\" \n";
        $html .= "{$indent}           class=\"{$cls}__submenu-link {#if {$next_var}.current}is-active{/if}\" \n";
        $html .= "{$indent}           role=\"menuitem\">\n";
        $html .= "{$indent}          {{$next_var}.title}\n";
        $html .= "{$indent}        </a>";

        // A3: Toggle button for submenu items with children
        if ( $depth < $max_depth ) {
            $html .= "\n{$indent}        {#if {$next_var}.children}\n";
            $html .= "{$indent}        <button class=\"{$cls}__submenu-toggle\" aria-label=\"Toggle submenu\"></button>\n";
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

        $html = '<nav class="' . $cls . '" role="navigation" aria-label="Main navigation">
  <div class="' . $cls . '__container">';

        if ( $has_mobile ) {
            $html .= '
    <button class="' . $cls . '__hamburger"
            aria-label="Toggle navigation menu"
            aria-expanded="false"
            aria-controls="main-menu">
      <span class="' . $cls . '__hamburger-line"></span>
      <span class="' . $cls . '__hamburger-line"></span>
      <span class="' . $cls . '__hamburger-line"></span>
    </button>';
        }

        $html .= '

    <div class="' . $cls . '__menu">
      <ul class="' . $cls . '__menu-list" id="main-menu" role="menubar">
        {#loop options.menus.' . esc_html( $menu_name ) . ' as item}';

        $li_classes = $cls . '__menu-item {#if item.current}is-current{/if} {#if item.current_parent}is-current-parent{/if}';
        if ( $desktop_depth > 0 ) {
            $li_classes .= ' {#if item.children}has-submenu{/if}';
        }

        $html .= '
          <li class="' . $li_classes . '" role="none">';

        $html .= '
            <a href="{item.url}"
               class="' . $cls . '__menu-link {#if item.current}is-active{/if}"
               role="menuitem">
              {item.title}
            </a>';

        if ( $desktop_depth > 0 ) {
            $html .= '
            {#if item.children}
            <button class="' . $cls . '__submenu-toggle" aria-label="Toggle submenu"></button>
            {/if}';
            $html .= $this->generate_submenu_html_direct( 1, $desktop_depth, '            ', 'item' );
        }

        $html .= '
          </li>
        {/loop}
      </ul>
    </div>
  </div>
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

        $html = '<nav class="' . $cls . '" role="navigation" aria-label="Main navigation">
  <div class="' . $cls . '__container">';

        if ( $has_mobile ) {
            $html .= '
    <button class="' . $cls . '__hamburger"
            aria-label="Toggle navigation menu"
            aria-expanded="false"
            aria-controls="main-menu">
      <span class="' . $cls . '__hamburger-line"></span>
      <span class="' . $cls . '__hamburger-line"></span>
      <span class="' . $cls . '__hamburger-line"></span>
    </button>';
        }

        $html .= '

    <div class="' . $cls . '__menu">
      <ul class="' . $cls . '__menu-list" id="main-menu" role="menubar">
        {#loop props.' . esc_html( $prop_name ) . ' as item}';

        $li_classes = $cls . '__menu-item {#if item.current}is-current{/if} {#if item.current_parent}is-current-parent{/if}';
        if ( $desktop_depth > 0 ) {
            $li_classes .= ' {#if item.children}has-submenu{/if}';
        }

        $html .= '
          <li class="' . $li_classes . '" role="none">';

        $html .= '
            <a href="{item.url}"
               class="' . $cls . '__menu-link {#if item.current}is-active{/if}"
               role="menuitem">
              {item.title}
            </a>';

        if ( $desktop_depth > 0 ) {
            $html .= '
            {#if item.children}
            <button class="' . $cls . '__submenu-toggle" aria-label="Toggle submenu"></button>
            {/if}';
            $html .= $this->generate_submenu_html_component( 1, $desktop_depth, '            ', 'item' );
        }

        $html .= '
          </li>
        {/loop}
      </ul>
    </div>
  </div>
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

        $css = ".{$cls} {
  position: relative;

  &__container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }";

        if ( $has_mobile ) {
            $animation_type = isset( $settings['hamburger_animation'] ) ? $settings['hamburger_animation'] : 'spin';
            $hamburger_animation = $this->get_hamburger_animation( $animation_type );

            $css .= "

  &__hamburger {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 2rem;
    height: 2rem;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    z-index: 1000;
    gap: 5px;

    &:focus {
      outline: 2px solid #0073aa;
      outline-offset: 4px;
    }
  }

  &__hamburger-line {
    display: block;
    width: 2rem;
    height: 3px;
    background-color: #2c3338;
    border-radius: 3px;
    transition: all 0.4s ease;
    transform-origin: center center;
  }

  {$hamburger_animation}";
        }

        $css .= "

  &__menu {
    display: flex;
    align-items: center;
  }

  &__menu-list {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 2rem;
  }

  &__menu-item {
    position: relative;";

        // Desktop: always show submenus on hover (submenu_behavior only affects mobile)
        if ( $desktop_depth > 0 ) {
            $css .= "

    &.has-submenu {
      &:hover > .{$cls}__submenu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
      }
    }";
        }

        $css .= "
  }

  &__menu-link {
    text-decoration: none;
    color: #2c3338;
    font-weight: 500;
    font-size: 1rem;
    padding: 0.5rem 0;
    display: block;
    transition: color 0.2s ease;

    &:hover,
    &:focus {
      color: #0073aa;
    }

    &.is-active {
      color: #0073aa;
      font-weight: 600;
    }
  }

  &__menu-item.is-current > .{$cls}__menu-link,
  &__menu-item.is-current-parent > .{$cls}__menu-link {
    color: #0073aa;
  }";

        if ( $desktop_depth > 0 ) {
            // Desktop submenu: always hidden by default, revealed on hover
            $css .= "

  &__submenu {
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    list-style: none;
    margin: 0;
    padding: 0.5rem 0;
    min-width: 200px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-radius: 4px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    z-index: 100;
  }

  &__submenu-item {
    margin: 0;

    &.has-submenu {
      position: relative;

      &:hover > .{$cls}__submenu {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
      }

      > .{$cls}__submenu {
        top: 0;
        left: 100%;
        transform: translateX(-10px);
      }
    }
  }

  &__submenu-link {
    display: block;
    padding: 0.75rem 1.25rem;
    color: #2c3338;
    text-decoration: none;
    font-size: 0.9375rem;
    transition: background-color 0.2s ease;

    &:hover,
    &:focus {
      background-color: #f0f0f1;
      color: #0073aa;
    }

    &.is-active {
      background-color: #e5f5fa;
      color: #0073aa;
      font-weight: 500;
    }
  }

  &__submenu-item.is-current > .{$cls}__submenu-link,
  &__submenu-item.is-current-parent > .{$cls}__submenu-link {
    color: #0073aa;
  }";
        }

        // A3: Submenu toggle button hidden on desktop (shown via mobile @media)
        $css .= "

  &__submenu-toggle {
    display: none;
  }";

        $css .= "\n}";

        if ( $has_mobile ) {
            $breakpoint = isset( $settings['mobile_breakpoint'] ) ? intval( $settings['mobile_breakpoint'] ) : 1200;
            $menu_position = isset( $settings['menu_position'] ) ? $settings['menu_position'] : 'left';
            $position_css = $this->get_menu_position( $menu_position, $breakpoint, $settings );
            $css .= "\n\n{$position_css}";
        }

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

        $close_methods = isset( $settings['close_methods'] ) ? $settings['close_methods'] : array( 'hamburger', 'outside', 'esc' );
        $accessibility = isset( $settings['accessibility'] ) ? $settings['accessibility'] : array( 'focus_trap', 'scroll_lock', 'aria', 'keyboard' );
        $submenu_behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';

        $has_focus_trap = in_array( 'focus_trap', $accessibility );
        $has_scroll_lock = in_array( 'scroll_lock', $accessibility );
        $has_outside_click = in_array( 'outside', $close_methods );
        $has_esc_key = in_array( 'esc', $close_methods );
        $has_accordion = ( 'accordion' === $submenu_behavior );

        $cls = $this->cls;

        $js = "(function() {
  'use strict';

  const globalNav = {
    isOpen: false,
    scrollPosition: 0,

    init: function() {
      this.hamburger = document.querySelector('.{$cls}__hamburger');
      this.menu = document.querySelector('.{$cls}__menu');
      this.nav = document.querySelector('.{$cls}');

      if (!this.hamburger || !this.menu) {
        return;
      }

      this.setupEventListeners();";

        if ( $has_accordion ) {
            $js .= "\n      this.setupSubmenuAccordion();";
        }

        $js .= "
    },

    setupEventListeners: function() {
      this.hamburger.addEventListener('click', this.toggleMenu.bind(this));";

        if ( $has_outside_click ) {
            $js .= "

      document.addEventListener('click', this.handleClickOutside.bind(this));";
        }

        if ( $has_esc_key ) {
            $js .= "

      document.addEventListener('keydown', this.handleEscKey.bind(this));";
        }

        $js .= "
    },

    toggleMenu: function() {
      this.isOpen = !this.isOpen;
      document.body.classList.toggle('{$cls}--mobile-open', this.isOpen);
      this.hamburger.classList.toggle('is-active', this.isOpen);
      this.menu.classList.toggle('is-open', this.isOpen);

      this.hamburger.setAttribute('aria-expanded', this.isOpen);";

        if ( $has_scroll_lock ) {
            $js .= "

      if (this.isOpen) {
        this.lockScroll();
      } else {
        this.unlockScroll();
      }";
        }

        if ( $has_focus_trap ) {
            $js .= "

      if (this.isOpen) {
        this.trapFocus();
      } else {
        this.releaseFocus();
      }";
        }

        $js .= "
    },";

        if ( $has_scroll_lock ) {
            $js .= "

    lockScroll: function() {
      this.scrollPosition = window.pageYOffset;
      document.body.style.overflow = 'hidden';
      document.body.style.position = 'fixed';
      document.body.style.top = `-\${this.scrollPosition}px`;
      document.body.style.width = '100%';
    },

    unlockScroll: function() {
      document.body.style.removeProperty('overflow');
      document.body.style.removeProperty('position');
      document.body.style.removeProperty('top');
      document.body.style.removeProperty('width');
      window.scrollTo(0, this.scrollPosition);
    },";
        }

        if ( $has_focus_trap ) {
            $js .= "

    trapFocus: function() {
      const focusableElements = this.menu.querySelectorAll(
        'a[href], button:not([disabled]), [tabindex]:not([tabindex=\"-1\"])'
      );

      this.firstFocusable = focusableElements[0];
      this.lastFocusable = focusableElements[focusableElements.length - 1];

      this.boundHandleFocusTrap = this.handleFocusTrap.bind(this);
      this.menu.addEventListener('keydown', this.boundHandleFocusTrap);

      if (this.firstFocusable) {
        this.firstFocusable.focus();
      }
    },

    releaseFocus: function() {
      if (this.boundHandleFocusTrap) {
        this.menu.removeEventListener('keydown', this.boundHandleFocusTrap);
      }
      this.hamburger.focus();
    },

    handleFocusTrap: function(e) {
      if (e.key !== 'Tab') return;

      if (e.shiftKey) {
        if (document.activeElement === this.firstFocusable) {
          e.preventDefault();
          this.lastFocusable.focus();
        }
      } else {
        if (document.activeElement === this.lastFocusable) {
          e.preventDefault();
          this.firstFocusable.focus();
        }
      }
    },";
        }

        if ( $has_outside_click ) {
            $js .= "

    handleClickOutside: function(e) {
      if (!this.isOpen) return;

      if (!this.menu.contains(e.target) && !this.hamburger.contains(e.target)) {
        this.toggleMenu();
      }
    },";
        }

        if ( $has_esc_key ) {
            $js .= "

    handleEscKey: function(e) {
      if (e.key === 'Escape' && this.isOpen) {
        this.toggleMenu();
      }
    },";
        }

        if ( $has_accordion ) {
            $js .= "

    setupSubmenuAccordion: function() {
      const submenuToggles = this.menu.querySelectorAll('.{$cls}__submenu-toggle');

      submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const parent = toggle.parentElement;
          const submenu = parent.querySelector('.{$cls}__submenu');

          parent.classList.toggle('is-open');

          if (parent.classList.contains('is-open')) {
            submenu.style.maxHeight = submenu.scrollHeight + 'px';
          } else {
            submenu.style.maxHeight = '0';
          }
        });
      });
    }";
        }

        $js .= "
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      globalNav.init();
    });
  } else {
    globalNav.init();
  }
})();";

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
            'spin' => "&__hamburger.is-active {
    .{$cls}__hamburger-line {
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
    }
  }",
            'squeeze' => "&__hamburger.is-active {
    .{$cls}__hamburger-line {
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
    }
  }",
            'collapse' => "&__hamburger.is-active {
    .{$cls}__hamburger-line {
      &:nth-child(1) {
        transform: translateY(8px) rotate(-45deg);
      }

      &:nth-child(2) {
        opacity: 0;
      }

      &:nth-child(3) {
        transform: translateY(-8px) rotate(45deg);
      }
    }
  }",
            'arrow' => "&__hamburger.is-active {
    .{$cls}__hamburger-line {
      &:nth-child(1) {
        transform: translateX(-4px) rotate(-45deg) scaleX(0.55);
      }

      &:nth-child(2) {
        transform: translateX(0);
      }

      &:nth-child(3) {
        transform: translateX(-4px) rotate(45deg) scaleX(0.55);
      }
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
        $mobile_depth = isset( $settings['submenu_depth_mobile'] ) ? intval( $settings['submenu_depth_mobile'] ) : 1;
        $submenu_behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';

        $submenu_mobile_css = '';
        // A5: Submenu link dash inset on mobile
        $submenu_dash_css = "

    &__submenu-link {
      padding-left: 1.5rem;
      position: relative;

      &::before {
        content: '—';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #2c3338;
        font-size: 0.75rem;
      }
    }";

        if ( $mobile_depth > 0 ) {
            // Base mobile submenu reset — undo desktop absolute positioning
            // A1: No background colours — leave unset for ETCH user control
            $submenu_mobile_base = "
    &__submenu {
      position: static;
      opacity: 1;
      visibility: visible;
      transform: none;
      box-shadow: none;
      margin-top: 0.5rem;
      border-radius: 4px;
      min-width: 0;";

            if ( 'always' === $submenu_behavior ) {
                // Always show — submenus expanded by default
                $submenu_mobile_css = "
{$submenu_mobile_base}
      max-height: none;
      overflow: visible;
    }{$submenu_dash_css}";
            } elseif ( 'clickable' === $submenu_behavior ) {
                // Clickable — submenus hidden on mobile (links navigate, no toggle)
                $submenu_mobile_css = "

    &__submenu {
      display: none;
    }";
            } else {
                // Accordion — collapsed by default, toggle with .is-open
                // A3: Chevron toggle button
                $submenu_mobile_css = "
{$submenu_mobile_base}
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
    }

    &__menu-item.is-open > .{$cls}__submenu,
    &__submenu-item.is-open > .{$cls}__submenu {
      max-height: 500px;
    }

    &__menu-item.has-submenu,
    &__submenu-item.has-submenu {
      position: relative;
    }

    &__menu-link {
      .has-submenu > & {
        padding-right: 3rem;
      }
    }

    &__submenu-toggle {
      appearance: none;
      background: none;
      border: none;
      padding: 1rem;
      cursor: pointer;
      position: absolute;
      right: 0;
      top: 0;
      height: 100%;
      display: flex;
      align-items: center;

      &::after {
        content: '';
        display: block;
        width: 8px;
        height: 8px;
        border-right: 2px solid #2c3338;
        border-bottom: 2px solid #2c3338;
        transform: rotate(45deg);
        transition: transform 0.3s ease;
      }
    }

    .is-open > &__submenu-toggle::after {
      transform: rotate(-135deg);
    }{$submenu_dash_css}";
            }
        } else {
            $submenu_mobile_css = "

    &__submenu {
      display: none;
    }";
        }

        $positions = array(
            'left' => "@media (max-width: {$breakpoint}px) {
  .{$cls} {
    &__hamburger {
      display: flex;
    }

    &__menu {
      position: fixed;
      top: 60px;
      left: 0;
      height: calc(100vh - 60px);
      width: 300px;
      transform: translateX(-100%);
      transition: transform 0.3s ease;
      overflow-y: auto;
      padding: 1rem 1.25rem;
    }

    &__menu.is-open {
      transform: translateX(0);
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
    }

    &__menu-list {
      flex-direction: column;
      gap: 0;
    }

    &__menu-link {
      padding: 1rem 0;
      border-bottom: 1px solid #f0f0f1;
    }{$submenu_mobile_css}
  }
}",
            'right' => "@media (max-width: {$breakpoint}px) {
  .{$cls} {
    &__hamburger {
      display: flex;
    }

    &__menu {
      position: fixed;
      top: 60px;
      right: 0;
      height: calc(100vh - 60px);
      width: 300px;
      transform: translateX(100%);
      transition: transform 0.3s ease;
      overflow-y: auto;
      padding: 1rem 1.25rem;
    }

    &__menu.is-open {
      transform: translateX(0);
      box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);
    }

    &__menu-list {
      flex-direction: column;
      gap: 0;
    }

    &__menu-link {
      padding: 1rem 0;
      border-bottom: 1px solid #f0f0f1;
    }{$submenu_mobile_css}
  }
}",
            'top' => "@media (max-width: {$breakpoint}px) {
  .{$cls} {
    &__hamburger {
      display: flex;
    }

    &__menu {
      position: fixed;
      top: 60px;
      left: 0;
      right: 0;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
      padding: 0 1.25rem;
    }

    &__menu.is-open {
      max-height: calc(100vh - 60px);
      padding: 1rem 1.25rem;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    &__menu-list {
      flex-direction: column;
      gap: 0;
    }

    &__menu-link {
      padding: 1rem 0;
      border-bottom: 1px solid #f0f0f1;
    }{$submenu_mobile_css}
  }
}",
            'full' => "@media (max-width: {$breakpoint}px) {
  .{$cls} {
    &__hamburger {
      display: flex;
    }

    &__menu {
      position: fixed;
      inset: 0;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease, visibility 0.3s ease;
      padding: 5rem 2rem 2rem;
      overflow-y: auto;
    }

    &__menu.is-open {
      opacity: 1;
      visibility: visible;
    }

    &__menu-list {
      flex-direction: column;
      gap: 2rem;
      text-align: center;
    }

    &__menu-link {
      font-size: 1.5rem;
      padding: 1rem 0;
    }{$submenu_mobile_css}
  }
}",
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

        // Compute state_classes after hierarchy is built (so children is populated)
        foreach ( $menu_by_id as &$menu_item ) {
            $state = array();
            if ( ! empty( $menu_item['current'] ) ) {
                $state[] = 'is-current';
            }
            if ( ! empty( $menu_item['current_parent'] ) ) {
                $state[] = 'is-current-parent';
            }
            if ( ! empty( $menu_item['children'] ) ) {
                $state[] = 'has-submenu';
            }
            $menu_item['state_classes'] = implode( ' ', $state );
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

        // Build the loop target
        if ( 'component' === $approach ) {
            $prop_name = isset( $settings['component_prop_name'] ) && ! empty( $settings['component_prop_name'] )
                ? $this->sanitize_prop_name( $settings['component_prop_name'] )
                : 'menuItems';
            $loop_target = 'props.' . $prop_name;
            $label_field = 'item.title';
            $active_field = 'item.current';
        } else {
            $loop_target = 'options.menus.' . $menu_name;
            $label_field = 'item.title';
            $active_field = 'item.current';
        }

        // Build styles collection
        $styles = $this->build_etch_styles( $settings );

        // Build the block tree
        $nav_children = array();

        // Hamburger button (only if mobile support enabled)
        if ( $has_mobile ) {
            $nav_children[] = $this->build_hamburger_block();
        }

        // Menu wrapper div > ul > loop > items
        $menu_item_children = array();

        $cls = $this->cls;

        // Link element with text
        $link_attrs = array(
            'href'  => '{item.url}',
            'class' => $cls . '__menu-link',
            'role'  => 'menuitem',
        );

        $link_block = $this->make_element_block(
            'Menu Link',
            'a',
            $link_attrs,
            array( $cls . '-menu-link' ),
            array(
                $this->make_text_block( 'Menu Label', '{' . $label_field . '}' ),
            )
        );
        $menu_item_children[] = $link_block;

        // Submenu condition + toggle button + nested loop (if depth > 0)
        if ( $desktop_depth > 0 ) {
            // A3: Toggle button for accordion chevron
            $toggle_button = $this->make_element_block(
                'Submenu Toggle',
                'button',
                array(
                    'class'      => $cls . '__submenu-toggle',
                    'aria-label' => 'Toggle submenu',
                ),
                array( $cls . '-submenu-toggle' )
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

        // Menu item li - use pre-computed state_classes from data
        // {#if} syntax doesn't work inside ETCH block tree attributes,
        // so we use {item.state_classes} which is pre-computed in the data layer
        $li_attrs = array(
            'class' => $cls . '__menu-item {item.state_classes}',
            'role'  => 'none',
        );

        $menu_item_block = $this->make_element_block(
            'Menu Item',
            'li',
            $li_attrs,
            array( $cls . '-menu-item' ),
            $menu_item_children
        );

        // Loop block
        $loop_block = $this->make_loop_block(
            'Menu Items',
            $loop_target,
            array( $menu_item_block )
        );

        // UL
        $ul_attrs = array(
            'class' => $cls . '__menu-list',
            'id'    => 'main-menu',
            'role'  => 'menubar',
        );
        $ul_block = $this->make_element_block(
            'Menu List',
            'ul',
            $ul_attrs,
            array( $cls . '-menu-list' ),
            array( $loop_block )
        );

        // Menu wrapper div
        $menu_div_attrs = array(
            'class' => $cls . '__menu',
        );
        $menu_div_block = $this->make_element_block(
            'Menu Wrapper',
            'div',
            $menu_div_attrs,
            array( $cls . '-menu' ),
            array( $ul_block )
        );

        $nav_children[] = $menu_div_block;

        // Container div
        $container_attrs = array(
            'class' => $cls . '__container',
        );
        $container_block = $this->make_element_block(
            'Container',
            'div',
            $container_attrs,
            array( $cls . '-container' ),
            $nav_children
        );

        // Nav element (root block)
        $nav_attrs = array(
            'class'      => $cls,
            'role'       => 'navigation',
            'aria-label' => 'Main navigation',
        );

        $nav_element = $this->make_element_block(
            'Global Navigation',
            'nav',
            $nav_attrs,
            array( $cls . '-base' ),
            array( $container_block )
        );

        // Attach script to nav element if mobile support is enabled
        if ( $has_mobile && ! empty( $js ) ) {
            $nav_element['attrs']['script'] = array(
                'code' => base64_encode( $js ),
                'id'   => $cls . '-' . time(),
            );
        }

        // Top-level ETCH structure
        $etch_structure = array(
            'type'           => 'block',
            'version'        => 2,
            'gutenbergBlock' => $nav_element,
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
        $line1 = $this->make_element_block( 'Line 1', 'span', array( 'class' => $cls . '__hamburger-line' ), array( $cls . '-hamburger-line' ) );
        $line2 = $this->make_element_block( 'Line 2', 'span', array( 'class' => $cls . '__hamburger-line' ), array( $cls . '-hamburger-line' ) );
        $line3 = $this->make_element_block( 'Line 3', 'span', array( 'class' => $cls . '__hamburger-line' ), array( $cls . '-hamburger-line' ) );

        $button_attrs = array(
            'class'         => $cls . '__hamburger',
            'aria-label'    => 'Toggle navigation menu',
            'aria-expanded' => 'false',
            'aria-controls' => 'main-menu',
        );

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

        // In the ETCH block tree, loop blocks always use 'item' as the iterator
        // variable (unlike raw HTML templates which use child/subchild).
        // Each nested loop scopes 'item' to its own level automatically.
        $var_name = 'item';

        // Submenu link with text
        $link_attrs = array(
            'href'  => '{' . $var_name . '.url}',
            'class' => $cls . '__submenu-link',
            'role'  => 'menuitem',
        );

        $link_block = $this->make_element_block(
            'Submenu Link',
            'a',
            $link_attrs,
            array( $cls . '-submenu-link' ),
            array(
                $this->make_text_block( 'Submenu Label', '{' . $var_name . '.title}' ),
            )
        );

        $li_children = array( $link_block );

        // Recurse for deeper submenus
        if ( $depth < $max_depth ) {
            // A3: Toggle button for accordion chevron on nested submenu items
            $toggle_button = $this->make_element_block(
                'Submenu Toggle',
                'button',
                array(
                    'class'      => $cls . '__submenu-toggle',
                    'aria-label' => 'Toggle submenu',
                ),
                array( $cls . '-submenu-toggle' )
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

        // Submenu item li - use pre-computed state_classes from data
        $li_attrs = array(
            'class' => $cls . '__submenu-item {' . $var_name . '.state_classes}',
            'role'  => 'none',
        );

        $li_block = $this->make_element_block(
            'Submenu Item',
            'li',
            $li_attrs,
            array( $cls . '-submenu-item' ),
            $li_children
        );

        // Loop over children — target is always 'item.children' since ETCH
        // scopes each nested loop's 'item' to its own level
        $loop_block = $this->make_loop_block(
            'Submenu Loop',
            'item.children',
            array( $li_block )
        );

        // Submenu UL
        $ul_attrs = array(
            'class' => $cls . '__submenu ' . $cls . '__submenu--level-' . $depth,
        );

        return $this->make_element_block(
            'Submenu Level ' . $depth,
            'ul',
            $ul_attrs,
            array( $cls . '-submenu' ),
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
        $submenu_behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';

        $styles = array();

        // Base nav styles
        $styles[ $cls . '-base' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls,
            'collection' => 'default',
            'css'        => 'position: relative;',
            'readonly'   => false,
        );

        // Container
        $styles[ $cls . '-container' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__container',
            'collection' => 'default',
            'css'        => "max-width: 1200px;\nmargin: 0 auto;\npadding: 1rem 1.25rem;\ndisplay: flex;\nalign-items: center;\njustify-content: space-between;",
            'readonly'   => false,
        );

        // Hamburger (only if mobile)
        if ( $has_mobile ) {
            $styles[ $cls . '-hamburger' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__hamburger',
                'collection' => 'default',
                'css'        => "display: none;\nflex-direction: column;\njustify-content: center;\nalign-items: center;\nwidth: 2rem;\nheight: 2rem;\nbackground: transparent;\nborder: none;\ncursor: pointer;\npadding: 0;\nz-index: 1000;\ngap: 5px;",
                'readonly'   => false,
            );

            $styles[ $cls . '-hamburger-line' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__hamburger-line',
                'collection' => 'default',
                'css'        => "display: block;\nwidth: 2rem;\nheight: 3px;\nbackground-color: #2c3338;\nborder-radius: 3px;\ntransition: all 0.4s ease;\ntransform-origin: center center;",
                'readonly'   => false,
            );
        }

        // Menu wrapper
        $styles[ $cls . '-menu' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__menu',
            'collection' => 'default',
            'css'        => "display: flex;\nalign-items: center;",
            'readonly'   => false,
        );

        // Menu list
        $styles[ $cls . '-menu-list' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__menu-list',
            'collection' => 'default',
            'css'        => "display: flex;\nlist-style: none;\nmargin: 0;\npadding: 0;\ngap: 2rem;",
            'readonly'   => false,
        );

        // Menu item
        $styles[ $cls . '-menu-item' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__menu-item',
            'collection' => 'default',
            'css'        => 'position: relative;',
            'readonly'   => false,
        );

        // Menu link
        $styles[ $cls . '-menu-link' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__menu-link',
            'collection' => 'default',
            'css'        => "text-decoration: none;\ncolor: #2c3338;\nfont-weight: 500;\nfont-size: 1rem;\npadding: 0.5rem 0;\ndisplay: block;\ntransition: color 0.2s ease;",
            'readonly'   => false,
        );

        // Menu link hover/focus
        $styles[ $cls . '-menu-link-hover' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__menu-link:hover, .' . $cls . '__menu-link:focus',
            'collection' => 'default',
            'css'        => 'color: #0073aa;',
            'readonly'   => false,
        );

        // Current page link
        $styles[ $cls . '-menu-item-current' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__menu-item.is-current > .' . $cls . '__menu-link, .' . $cls . '__menu-item.is-current-parent > .' . $cls . '__menu-link',
            'collection' => 'default',
            'css'        => 'color: #0073aa;',
            'readonly'   => false,
        );

        // Submenu styles (only if depth > 0)
        // Desktop: always hidden by default, revealed on hover
        // (submenu_behavior only affects mobile via the responsive @media styles)
        if ( $desktop_depth > 0 ) {
            $styles[ $cls . '-submenu' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__submenu',
                'collection' => 'default',
                'css'        => "position: absolute;\ntop: 100%;\nleft: 0;\nbackground: white;\nlist-style: none;\nmargin: 0;\npadding: 0.5rem 0;\nmin-width: 200px;\nbox-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);\nborder-radius: 4px;\nopacity: 0;\nvisibility: hidden;\ntransform: translateY(-10px);\ntransition: all 0.2s ease;\nz-index: 100;",
                'readonly'   => false,
            );

            // Show submenu on hover — top-level menu items
            $styles[ $cls . '-menu-item-hover-submenu' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__menu-item.has-submenu:hover > .' . $cls . '__submenu',
                'collection' => 'default',
                'css'        => "opacity: 1;\nvisibility: visible;\ntransform: translateY(0);",
                'readonly'   => false,
            );

            // Show nested submenu on hover — submenu items with children
            $styles[ $cls . '-submenu-item-hover-submenu' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__submenu-item.has-submenu:hover > .' . $cls . '__submenu',
                'collection' => 'default',
                'css'        => "opacity: 1;\nvisibility: visible;\ntransform: translateX(0);",
                'readonly'   => false,
            );

            // Nested submenus position (fly-out to the right)
            $styles[ $cls . '-submenu-nested' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__submenu-item.has-submenu > .' . $cls . '__submenu',
                'collection' => 'default',
                'css'        => "top: 0;\nleft: 100%;\ntransform: translateX(-10px);",
                'readonly'   => false,
            );

            $styles[ $cls . '-submenu-item' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__submenu-item',
                'collection' => 'default',
                'css'        => 'margin: 0;',
                'readonly'   => false,
            );

            $styles[ $cls . '-submenu-link' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__submenu-link',
                'collection' => 'default',
                'css'        => "display: block;\npadding: 0.75rem 1.25rem;\ncolor: #2c3338;\ntext-decoration: none;\nfont-size: 0.9375rem;\ntransition: background-color 0.2s ease;",
                'readonly'   => false,
            );

            // Submenu link hover state
            $styles[ $cls . '-submenu-link-hover' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__submenu-link:hover',
                'collection' => 'default',
                'css'        => 'background-color: #f0f0f1;',
                'readonly'   => false,
            );

            // Current parent highlight for submenu links
            $styles[ $cls . '-submenu-item-current' ] = array(
                'type'       => 'class',
                'selector'   => '.' . $cls . '__submenu-item.is-current > .' . $cls . '__submenu-link, .' . $cls . '__submenu-item.is-current-parent > .' . $cls . '__submenu-link',
                'collection' => 'default',
                'css'        => 'color: #0073aa;',
                'readonly'   => false,
            );
        }

        // A3: Submenu toggle button — hidden on desktop (shown via mobile @media)
        $styles[ $cls . '-submenu-toggle' ] = array(
            'type'       => 'class',
            'selector'   => '.' . $cls . '__submenu-toggle',
            'collection' => 'default',
            'css'        => 'display: none;',
            'readonly'   => false,
        );

        // Mobile / responsive styles
        if ( $has_mobile ) {
            $breakpoint = isset( $settings['mobile_breakpoint'] ) ? intval( $settings['mobile_breakpoint'] ) : 1200;
            $menu_position = isset( $settings['menu_position'] ) ? $settings['menu_position'] : 'left';
            $animation_type = isset( $settings['hamburger_animation'] ) ? $settings['hamburger_animation'] : 'spin';
            $mobile_depth = isset( $settings['submenu_depth_mobile'] ) ? intval( $settings['submenu_depth_mobile'] ) : 0;

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

            // Mobile responsive styles — position-specific menu CSS
            $mobile_css = $this->get_flat_mobile_css( $menu_position, $breakpoint, $mobile_depth, $submenu_behavior );
            foreach ( $mobile_css as $key => $rule ) {
                $styles[ $cls . '-' . $key ] = array(
                    'type'       => 'class',
                    'selector'   => $rule['selector'],
                    'collection' => $rule['collection'],
                    'css'        => $rule['css'],
                    'readonly'   => false,
                );
            }
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
            'arrow' => array(
                'line1' => "transform: translateX(-4px) rotate(-45deg) scaleX(0.55);",
                'line2' => "transform: translateX(0);",
                'line3' => "transform: translateX(-4px) rotate(45deg) scaleX(0.55);",
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
     * Get flat (non-nested) mobile CSS for ETCH styles
     *
     * Returns a single comprehensive @media block as a style rule,
     * using fully-qualified flat selectors (no SCSS nesting).
     *
     * @param string $position         Menu position (left, right, top, full)
     * @param int    $breakpoint       Mobile breakpoint in px
     * @param int    $mobile_depth     Mobile submenu depth
     * @param string $submenu_behavior Submenu behavior (always, accordion, clickable)
     * @return array Array with a single style rule containing the full responsive CSS
     */
    private function get_flat_mobile_css( $position, $breakpoint, $mobile_depth, $submenu_behavior = 'accordion' ) {
        $cls = $this->cls;
        $s   = '.' . $cls;

        // Position-specific menu styles
        // A1: No background colours — leave unset for ETCH user control
        // A2: No box-shadow on base state — only on .is-open to prevent bleed
        // A4: Menu aligned below hamburger with top: 60px offset
        switch ( $position ) {
            case 'right':
                $menu_css      = "position: fixed; top: 60px; right: 0; height: calc(100vh - 60px); width: 300px; transform: translateX(100%); transition: transform 0.3s ease; overflow-y: auto; padding: 1rem 1.25rem; z-index: 999;";
                $menu_open_css = "transform: translateX(0); box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);";
                break;
            case 'top':
                $menu_css      = "position: fixed; top: 60px; left: 0; right: 0; max-height: 0; overflow: hidden; transition: max-height 0.3s ease; padding: 0 1.25rem; z-index: 999;";
                $menu_open_css = "max-height: calc(100vh - 60px); padding: 1rem 1.25rem; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);";
                break;
            case 'full':
                $menu_css      = "position: fixed; inset: 0; display: flex; align-items: flex-start; justify-content: center; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0.3s ease; padding: 5rem 2rem 2rem; overflow-y: auto; z-index: 999;";
                $menu_open_css = "opacity: 1; visibility: visible;";
                break;
            case 'left':
            default:
                $menu_css      = "position: fixed; top: 60px; left: 0; height: calc(100vh - 60px); width: 300px; transform: translateX(-100%); transition: transform 0.3s ease; overflow-y: auto; padding: 1rem 1.25rem; z-index: 999;";
                $menu_open_css = "transform: translateX(0); box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);";
                break;
        }

        // Menu list styles
        $menu_list_css = "flex-direction: column; gap: 0;";
        $link_css      = "padding: 1rem 0; border-bottom: 1px solid #f0f0f1;";
        if ( 'full' === $position ) {
            $menu_list_css = "flex-direction: column; gap: 2rem; text-align: center;";
            $link_css      = "font-size: 1.5rem; padding: 1rem 0;";
        }

        // A5: Submenu link dash inset on mobile
        $submenu_dash = "\n  {$s}__submenu-link { padding-left: 1.5rem; position: relative; }"
            . "\n  {$s}__submenu-link::before { content: '\\2014'; position: absolute; left: 0; top: 50%; transform: translateY(-50%); color: #2c3338; font-size: 0.75rem; }";

        // Submenu mobile CSS — depends on both depth and behavior
        // A1: No background colours on submenus
        $submenu_mobile = '';
        if ( $mobile_depth > 0 ) {
            $submenu_base = "{$s}__submenu { position: static; opacity: 1; visibility: visible; transform: none; box-shadow: none; margin-top: 0.5rem; border-radius: 4px; min-width: 0;";

            if ( 'always' === $submenu_behavior ) {
                // Always show — submenus expanded by default
                $submenu_mobile = "\n  {$submenu_base} max-height: none; overflow: visible; }" . $submenu_dash;
            } elseif ( 'clickable' === $submenu_behavior ) {
                // Clickable — submenus hidden on mobile (links navigate, no toggle)
                $submenu_mobile = "\n  {$s}__submenu { display: none; }";
            } else {
                // Accordion — collapsed by default, toggle with .is-open
                // A3: Chevron toggle button styles
                $submenu_mobile = "\n  {$submenu_base} max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }"
                    . "\n  {$s}__menu-item.is-open > {$s}__submenu,"
                    . "\n  {$s}__submenu-item.is-open > {$s}__submenu { max-height: 500px; }"
                    . "\n  {$s}__menu-item.has-submenu,"
                    . "\n  {$s}__submenu-item.has-submenu { position: relative; }"
                    . "\n  .has-submenu > {$s}__menu-link { padding-right: 3rem; }"
                    . "\n  {$s}__submenu-toggle { appearance: none; background: none; border: none; padding: 1rem; cursor: pointer; position: absolute; right: 0; top: 0; height: 100%; display: flex; align-items: center; }"
                    . "\n  {$s}__submenu-toggle::after { content: ''; display: block; width: 8px; height: 8px; border-right: 2px solid #2c3338; border-bottom: 2px solid #2c3338; transform: rotate(45deg); transition: transform 0.3s ease; }"
                    . "\n  .is-open > {$s}__submenu-toggle::after { transform: rotate(-135deg); }"
                    . $submenu_dash;
            }
        } else {
            $submenu_mobile = "\n  {$s}__submenu { display: none; }";
        }

        // Build the complete @media block as a raw CSS string.
        // This gets injected as a standalone <style> block in the ETCH output.
        $responsive_css = "@media (max-width: {$breakpoint}px) {\n"
            . "  {$s}__hamburger { display: flex; }\n"
            . "  {$s}__menu { {$menu_css} }\n"
            . "  {$s}__menu.is-open { {$menu_open_css} }\n"
            . "  {$s}__menu-list { {$menu_list_css} }\n"
            . "  {$s}__menu-link { {$link_css} }\n"
            . $submenu_mobile . "\n"
            . "}";

        return array(
            'mobile-responsive' => array(
                'selector'   => $s,
                'collection' => 'default',
                'css'        => $responsive_css,
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
