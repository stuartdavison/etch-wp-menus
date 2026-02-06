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

        $next_var = $this->get_depth_var_name( $depth );

        $html = "\n{$indent}{#if {$var_name}.children}\n";
        $html .= "{$indent}  <ul class=\"global-nav__submenu global-nav__submenu--level-{$depth}\">\n";
        $html .= "{$indent}    {#loop {$var_name}.children as {$next_var}}\n";

        if ( $depth < $max_depth ) {
            $html .= "{$indent}      <li class=\"global-nav__submenu-item {#if {$next_var}.children}has-submenu{/if}\" role=\"none\">\n";
        } else {
            $html .= "{$indent}      <li class=\"global-nav__submenu-item\" role=\"none\">\n";
        }

        $html .= "{$indent}        <a href=\"{{$next_var}.url}\" \n";
        $html .= "{$indent}           class=\"global-nav__submenu-link {#if {$next_var}.current}is-active{/if}\" \n";
        $html .= "{$indent}           role=\"menuitem\">\n";
        $html .= "{$indent}          {{$next_var}.title}\n";
        $html .= "{$indent}        </a>";

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

        $next_var = $this->get_depth_var_name( $depth );

        $html = "\n{$indent}{#if {$var_name}.children}\n";
        $html .= "{$indent}  <ul class=\"global-nav__submenu global-nav__submenu--level-{$depth}\">\n";
        $html .= "{$indent}    {#loop {$var_name}.children as {$next_var}}\n";

        if ( $depth < $max_depth ) {
            $html .= "{$indent}      <li class=\"global-nav__submenu-item {#if {$next_var}.children}has-submenu{/if}\" role=\"none\">\n";
        } else {
            $html .= "{$indent}      <li class=\"global-nav__submenu-item\" role=\"none\">\n";
        }

        $html .= "{$indent}        <a href=\"{{$next_var}.url}\" \n";
        $html .= "{$indent}           class=\"global-nav__submenu-link {#if {$next_var}.active}is-active{/if}\" \n";
        $html .= "{$indent}           role=\"menuitem\">\n";
        $html .= "{$indent}          {{$next_var}.label}\n";
        $html .= "{$indent}        </a>";

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
        $menu_name = $this->get_menu_name( $settings );
        $has_mobile = $this->has_mobile_support( $settings );
        $desktop_depth = isset( $settings['submenu_depth_desktop'] ) ? intval( $settings['submenu_depth_desktop'] ) : 1;

        $html = '<nav class="global-nav" role="navigation" aria-label="Main navigation">
  <div class="global-nav__container">';

        if ( $has_mobile ) {
            $html .= '
    <button class="global-nav__hamburger"
            aria-label="Toggle navigation menu"
            aria-expanded="false"
            aria-controls="main-menu">
      <span class="global-nav__hamburger-line"></span>
      <span class="global-nav__hamburger-line"></span>
      <span class="global-nav__hamburger-line"></span>
    </button>';
        }

        $html .= '

    <div class="global-nav__menu">
      <ul class="global-nav__menu-list" id="main-menu" role="menubar">
        {#loop options.menus.' . esc_html( $menu_name ) . ' as item}';

        if ( $desktop_depth > 0 ) {
            $html .= '
          <li class="global-nav__menu-item {#if item.children}has-submenu{/if}" role="none">';
        } else {
            $html .= '
          <li class="global-nav__menu-item" role="none">';
        }

        $html .= '
            <a href="{item.url}"
               class="global-nav__menu-link {#if item.current}is-active{/if}"
               role="menuitem">
              {item.title}
            </a>';

        if ( $desktop_depth > 0 ) {
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
        $prop_name = isset( $settings['component_prop_name'] ) && ! empty( $settings['component_prop_name'] )
            ? sanitize_key( $settings['component_prop_name'] )
            : 'menuItems';
        $has_mobile = $this->has_mobile_support( $settings );
        $desktop_depth = isset( $settings['submenu_depth_desktop'] ) ? intval( $settings['submenu_depth_desktop'] ) : 1;

        $html = '<nav class="global-nav" role="navigation" aria-label="Main navigation">
  <div class="global-nav__container">';

        if ( $has_mobile ) {
            $html .= '
    <button class="global-nav__hamburger"
            aria-label="Toggle navigation menu"
            aria-expanded="false"
            aria-controls="main-menu">
      <span class="global-nav__hamburger-line"></span>
      <span class="global-nav__hamburger-line"></span>
      <span class="global-nav__hamburger-line"></span>
    </button>';
        }

        $html .= '

    <div class="global-nav__menu">
      <ul class="global-nav__menu-list" id="main-menu" role="menubar">
        {#loop props.' . esc_html( $prop_name ) . ' as item}';

        if ( $desktop_depth > 0 ) {
            $html .= '
          <li class="global-nav__menu-item {#if item.children}has-submenu{/if}" role="none">';
        } else {
            $html .= '
          <li class="global-nav__menu-item" role="none">';
        }

        $html .= '
            <a href="{item.url}"
               class="global-nav__menu-link {#if item.active}is-active{/if}"
               role="menuitem">
              {item.label}
            </a>';

        if ( $desktop_depth > 0 ) {
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
        $has_mobile = $this->has_mobile_support( $settings );
        $desktop_depth = isset( $settings['submenu_depth_desktop'] ) ? intval( $settings['submenu_depth_desktop'] ) : 1;

        $css = ".global-nav {
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
    z-index: 10;
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

        if ( $desktop_depth > 0 ) {
            $css .= "

    &.has-submenu {
      &:hover > .global-nav__submenu {
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
  }";

        if ( $desktop_depth > 0 ) {
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

      &:hover > .global-nav__submenu {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
      }

      > .global-nav__submenu {
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
  }";
        }

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

        $js = "(function() {
  'use strict';

  const globalNav = {
    isOpen: false,
    scrollPosition: 0,

    init: function() {
      this.hamburger = document.querySelector('.global-nav__hamburger');
      this.menu = document.querySelector('.global-nav__menu');
      this.nav = document.querySelector('.global-nav');

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
      document.body.classList.toggle('global-nav--mobile-open', this.isOpen);
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
      const submenuToggles = this.menu.querySelectorAll('.has-submenu > a');

      submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
          if (window.innerWidth <= " . ( isset( $settings['mobile_breakpoint'] ) ? intval( $settings['mobile_breakpoint'] ) : 1200 ) . ") {
            e.preventDefault();
            const parent = toggle.parentElement;
            const submenu = parent.querySelector('.global-nav__submenu');

            parent.classList.toggle('is-open');

            if (parent.classList.contains('is-open')) {
              submenu.style.maxHeight = submenu.scrollHeight + 'px';
            } else {
              submenu.style.maxHeight = '0';
            }
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
        $animations = array(
            'spin' => '&__hamburger.is-active {
    .global-nav__hamburger-line {
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
  }',
            'squeeze' => '&__hamburger.is-active {
    .global-nav__hamburger-line {
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
  }',
            'collapse' => '&__hamburger.is-active {
    .global-nav__hamburger-line {
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
  }',
            'arrow' => '&__hamburger.is-active {
    .global-nav__hamburger-line {
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
  }',
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
        $mobile_depth = isset( $settings['submenu_depth_mobile'] ) ? intval( $settings['submenu_depth_mobile'] ) : 1;

        $submenu_mobile_css = '';
        if ( $mobile_depth > 0 ) {
            $submenu_mobile_css = "

    &__submenu {
      position: static;
      opacity: 1;
      visibility: visible;
      transform: none;
      box-shadow: none;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
      background: #f9f9f9;
      margin-top: 0.5rem;
      border-radius: 4px;
      min-width: 0;
    }

    &__menu-item.is-open > .global-nav__submenu,
    &__submenu-item.is-open > .global-nav__submenu {
      max-height: 500px;
    }";
        } else {
            $submenu_mobile_css = "

    &__submenu {
      display: none;
    }";
        }

        $positions = array(
            'left' => "@media (max-width: {$breakpoint}px) {
  .global-nav {
    &__hamburger {
      display: flex;
    }

    &__menu {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 300px;
      background: white;
      transform: translateX(-100%);
      transition: transform 0.3s ease;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
      overflow-y: auto;
      padding: 4rem 2rem;
    }

    &__menu.is-open {
      transform: translateX(0);
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
  .global-nav {
    &__hamburger {
      display: flex;
    }

    &__menu {
      position: fixed;
      top: 0;
      right: 0;
      height: 100vh;
      width: 300px;
      background: white;
      transform: translateX(100%);
      transition: transform 0.3s ease;
      box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);
      overflow-y: auto;
      padding: 4rem 2rem;
    }

    &__menu.is-open {
      transform: translateX(0);
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
  .global-nav {
    &__hamburger {
      display: flex;
    }

    &__menu {
      position: fixed;
      top: 60px;
      left: 0;
      right: 0;
      background: white;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      padding: 0 2rem;
    }

    &__menu.is-open {
      max-height: calc(100vh - 60px);
      padding: 2rem;
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
  .global-nav {
    &__hamburger {
      display: flex;
    }

    &__menu {
      position: fixed;
      inset: 0;
      background: white;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease, visibility 0.3s ease;
      padding: 4rem 2rem;
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

        $menu_tree = array();
        $menu_by_id = array();

        foreach ( $menu_items as $item ) {
            $menu_by_id[ $item->ID ] = array(
                'id'       => $item->ID,
                'title'    => $item->title,
                'url'      => $item->url,
                'current'  => false,
                'children' => array(),
            );
        }

        foreach ( $menu_items as $item ) {
            if ( $item->menu_item_parent == 0 ) {
                $menu_tree[] = &$menu_by_id[ $item->ID ];
            } else {
                if ( isset( $menu_by_id[ $item->menu_item_parent ] ) ) {
                    $menu_by_id[ $item->menu_item_parent ]['children'][] = &$menu_by_id[ $item->ID ];
                }
            }
        }

        return json_encode( $menu_tree, JSON_PRETTY_PRINT );
    }

    /**
     * Generate complete ETCH JSON structure
     *
     * @param array $settings User settings
     * @return string JSON for ETCH structure panel
     */
    public function generate_etch_json( $settings ) {
        $html = $this->generate_html( $settings );
        $css = $this->generate_css( $settings );
        $js = $this->generate_javascript( $settings );
        $has_mobile = $this->has_mobile_support( $settings );
        $menu_name = $this->get_menu_name( $settings );
        $approach = isset( $settings['approach'] ) ? $settings['approach'] : 'direct';

        // Build the loop target
        if ( 'component' === $approach ) {
            $prop_name = isset( $settings['component_prop_name'] ) && ! empty( $settings['component_prop_name'] )
                ? sanitize_key( $settings['component_prop_name'] )
                : 'menuItems';
            $loop_target = 'props.' . $prop_name;
        } else {
            $loop_target = 'options.menus.' . $menu_name;
        }

        // Create ETCH-compatible JSON structure
        $etch_structure = array(
            'type'    => 'block',
            'version' => 2,
            'gutenbergBlock' => array(
                'blockName' => 'etch/custom-html',
                'attrs' => array(
                    'metadata' => array(
                        'name' => 'Global Navigation'
                    ),
                    'html' => $html,
                    'target' => $loop_target
                ),
                'innerBlocks' => array(),
                'innerHTML' => '',
                'innerContent' => array()
            ),
            'styles' => array(
                'global-nav-styles' => array(
                    'type' => 'class',
                    'selector' => '.global-nav',
                    'collection' => 'default',
                    'css' => $css,
                    'readonly' => false
                )
            ),
        );

        // Only include scripts if mobile support is enabled
        if ( $has_mobile && ! empty( $js ) ) {
            $etch_structure['scripts'] = array(
                'global-nav-script' => array(
                    'code' => base64_encode( $js ),
                    'id' => 'global-nav-' . time()
                )
            );
        }

        return json_encode( $etch_structure, JSON_PRETTY_PRINT );
    }
}
