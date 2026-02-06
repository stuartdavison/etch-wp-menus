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
                // Convert to ETCH-compatible slug (underscores, no hyphens)
                return $this->sanitize_for_etch( $menu->name );
            }
        }
        return 'global_navigation';
    }
    
    /**
     * Sanitize string for ETCH property names
     * ETCH only allows: letters, digits, underscores
     * No hyphens, no spaces, no special characters
     *
     * @param string $string String to sanitize
     * @return string ETCH-compatible property name
     */
    public function sanitize_for_etch( $string ) {
        // Convert to lowercase
        $string = strtolower( $string );
        
        // Replace spaces and hyphens with underscores
        $string = str_replace( array( ' ', '-' ), '_', $string );
        
        // Remove any characters that aren't letters, digits, or underscores
        $string = preg_replace( '/[^a-z0-9_]/', '', $string );
        
        // Remove multiple consecutive underscores
        $string = preg_replace( '/_+/', '_', $string );
        
        // Remove leading/trailing underscores
        $string = trim( $string, '_' );
        
        return $string;
    }
    
    /**
     * Generate complete HTML structure
     *
     * @param array $settings User settings including approach type
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
     * Generate Direct Loop HTML (uses options.menus)
     *
     * @param array $settings User settings
     * @return string HTML code
     */
    private function generate_direct_html( $settings ) {
        $menu_name = $this->get_menu_name( $settings );
        
        $html = '<!-- Copy this to ETCH HTML Panel -->
<nav class="global-nav" role="navigation" aria-label="Main navigation">
  <div class="global-nav__container">
    <button class="global-nav__hamburger" 
            aria-label="Toggle navigation menu"
            aria-expanded="false"
            aria-controls="main-menu">
      <span class="global-nav__hamburger-line"></span>
      <span class="global-nav__hamburger-line"></span>
      <span class="global-nav__hamburger-line"></span>
    </button>
    
    <div class="global-nav__menu">
      <ul class="global-nav__menu-list" id="main-menu" role="menubar">
        {#loop options.menus.' . esc_html( $menu_name ) . ' as item}
          <li class="global-nav__menu-item {#if item.children}has-submenu{/if}" role="none">
            <a href="{item.url}" 
               class="global-nav__menu-link {#if item.current}is-active{/if}" 
               role="menuitem">
              {item.title}
            </a>
            
            {#if item.children}
              <ul class="global-nav__submenu">
                {#loop item.children as child}
                  <li class="global-nav__submenu-item" role="none">
                    <a href="{child.url}" 
                       class="global-nav__submenu-link {#if child.current}is-active{/if}" 
                       role="menuitem">
                      {child.title}
                    </a>
                  </li>
                {/loop}
              </ul>
            {/if}
          </li>
        {/loop}
      </ul>
    </div>
  </div>
</nav>';
        
        return $html;
    }
    
    /**
     * Generate Component HTML (uses props.menuItems)
     *
     * @param array $settings User settings
     * @return string HTML code
     */
    private function generate_component_html( $settings ) {
        $prop_name = isset( $settings['component_prop_name'] ) && ! empty( $settings['component_prop_name'] ) 
            ? sanitize_key( $settings['component_prop_name'] ) 
            : 'menuItems';
            
        $html = '<!-- Copy this to ETCH Component HTML -->
<nav class="global-nav" role="navigation" aria-label="Main navigation">
  <div class="global-nav__container">
    <button class="global-nav__hamburger" 
            aria-label="Toggle navigation menu"
            aria-expanded="false"
            aria-controls="main-menu">
      <span class="global-nav__hamburger-line"></span>
      <span class="global-nav__hamburger-line"></span>
      <span class="global-nav__hamburger-line"></span>
    </button>
    
    <div class="global-nav__menu">
      <ul class="global-nav__menu-list" id="main-menu" role="menubar">
        {#loop props.' . esc_html( $prop_name ) . ' as item}
          <li class="global-nav__menu-item {#if item.children}has-submenu{/if}" role="none">
            <a href="{item.url}" 
               class="global-nav__menu-link {#if item.active}is-active{/if}" 
               role="menuitem">
              {item.label}
            </a>
            
            {#if item.children}
              <ul class="global-nav__submenu">
                {#loop item.children as child}
                  <li class="global-nav__submenu-item" role="none">
                    <a href="{child.url}" 
                       class="global-nav__submenu-link {#if child.active}is-active{/if}" 
                       role="menuitem">
                      {child.label}
                    </a>
                  </li>
                {/loop}
              </ul>
            {/if}
          </li>
        {/loop}
      </ul>
    </div>
  </div>
</nav>';
        
        return $html;
    }
    
    /**
     * Generate nested CSS with custom breakpoint
     *
     * @param array $settings User settings
     * @return string CSS code
     */
    public function generate_css( $settings ) {
        $breakpoint = isset( $settings['mobile_breakpoint'] ) ? intval( $settings['mobile_breakpoint'] ) : 1200;
        $animation_type = isset( $settings['hamburger_animation'] ) ? $settings['hamburger_animation'] : 'spin';
        $menu_position = isset( $settings['menu_position'] ) ? $settings['menu_position'] : 'left';
        
        $hamburger_animation = $this->get_hamburger_animation( $animation_type );
        $position_css = $this->get_menu_position( $menu_position, $breakpoint );
        
        $css = "/* Copy this to ETCH CSS Panel */
.global-nav {
  position: relative;
  
  &__container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  &__hamburger {
    display: none;
    flex-direction: column;
    justify-content: space-around;
    width: 2rem;
    height: 2rem;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    z-index: 10;
    
    &:focus {
      outline: 2px solid #0073aa;
      outline-offset: 4px;
    }
  }
  
  &__hamburger-line {
    width: 2rem;
    height: 0.25rem;
    background-color: #2c3338;
    border-radius: 10px;
    transition: all 0.3s linear;
    position: relative;
    transform-origin: 1px;
  }
  
  {$hamburger_animation}
  
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
    position: relative;
    
    &.has-submenu {
      &:hover .global-nav__submenu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
      }
    }
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
}

{$position_css}";
        
        return $css;
    }
    
    /**
     * Generate JavaScript with selected features
     *
     * @param array $settings User settings
     * @return string JavaScript code
     */
    public function generate_javascript( $settings ) {
        $close_methods = isset( $settings['close_methods'] ) ? $settings['close_methods'] : array( 'hamburger', 'outside', 'esc' );
        $accessibility = isset( $settings['accessibility'] ) ? $settings['accessibility'] : array( 'focus_trap', 'scroll_lock', 'aria', 'keyboard' );
        $submenu_behavior = isset( $settings['submenu_behavior'] ) ? $settings['submenu_behavior'] : 'accordion';
        
        $has_focus_trap = in_array( 'focus_trap', $accessibility );
        $has_scroll_lock = in_array( 'scroll_lock', $accessibility );
        $has_outside_click = in_array( 'outside', $close_methods );
        $has_esc_key = in_array( 'esc', $close_methods );
        $has_accordion = ( 'accordion' === $submenu_behavior );
        
        $js = "// Copy this to ETCH JavaScript Panel
(function() {
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
      // Hamburger toggle
      this.hamburger.addEventListener('click', this.toggleMenu.bind(this));";
        
        if ( $has_outside_click ) {
            $js .= "
      
      // Click outside to close
      document.addEventListener('click', this.handleClickOutside.bind(this));";
        }
        
        if ( $has_esc_key ) {
            $js .= "
      
      // ESC key to close
      document.addEventListener('keydown', this.handleEscKey.bind(this));";
        }
        
        $js .= "
    },
    
    toggleMenu: function() {
      this.isOpen = !this.isOpen;
      document.body.classList.toggle('global-nav--mobile-open', this.isOpen);
      this.hamburger.classList.toggle('is-active', this.isOpen);
      this.menu.classList.toggle('is-open', this.isOpen);
      
      // Update ARIA
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
          // Only prevent default on mobile
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
  
  // Initialize when DOM is ready
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
     * @param string $type Animation type
     * @return string CSS code
     */
    private function get_hamburger_animation( $type ) {
        $animations = array(
            'spin' => '&__hamburger.is-active {
    .global-nav__hamburger-line {
      &:nth-child(1) {
        transform: translateY(8px) rotate(45deg);
      }
      
      &:nth-child(2) {
        transform: scaleX(0);
        opacity: 0;
      }
      
      &:nth-child(3) {
        transform: translateY(-8px) rotate(-45deg);
      }
    }
  }',
            'squeeze' => '&__hamburger.is-active {
    .global-nav__hamburger-line {
      &:nth-child(1) {
        transform: translateY(8px) rotate(45deg);
        width: 50%;
      }
      
      &:nth-child(2) {
        transform: scaleX(0);
      }
      
      &:nth-child(3) {
        transform: translateY(-8px) rotate(-45deg);
        width: 50%;
      }
    }
  }',
            'collapse' => '&__hamburger.is-active {
    .global-nav__hamburger-line {
      &:nth-child(1),
      &:nth-child(2),
      &:nth-child(3) {
        transform: translateY(0);
      }
      
      &:nth-child(2) {
        opacity: 0;
      }
    }
  }',
            'arrow' => '&__hamburger.is-active {
    .global-nav__hamburger-line {
      &:nth-child(1) {
        transform: translateY(4px) rotate(-45deg);
        width: 70%;
      }
      
      &:nth-child(2) {
        transform: translateX(10px);
      }
      
      &:nth-child(3) {
        transform: translateY(-4px) rotate(45deg);
        width: 70%;
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
     * @return string CSS code
     */
    private function get_menu_position( $position, $breakpoint ) {
        $positions = array(
            'left' => "/* Mobile Navigation - Left Slide */
@media (max-width: {$breakpoint}px) {
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
    }
    
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
    }
    
    &__menu-item.is-open .global-nav__submenu {
      max-height: 500px;
    }
  }
}",
            'right' => "/* Mobile Navigation - Right Slide */
@media (max-width: {$breakpoint}px) {
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
    }
    
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
    }
    
    &__menu-item.is-open .global-nav__submenu {
      max-height: 500px;
    }
  }
}",
            'top' => "/* Mobile Navigation - Top Dropdown */
@media (max-width: {$breakpoint}px) {
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
    }
    
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
    }
    
    &__menu-item.is-open .global-nav__submenu {
      max-height: 500px;
    }
  }
}",
            'full' => "/* Mobile Navigation - Full Overlay */
@media (max-width: {$breakpoint}px) {
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
    }
    
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
      margin-top: 1rem;
      border-radius: 4px;
    }
    
    &__menu-item.is-open .global-nav__submenu {
      max-height: 500px;
    }
  }
}",
        );
        
        return isset( $positions[ $position ] ) ? $positions[ $position ] : $positions['left'];
    }
    
    /**
     * Generate Quick Start Guide
     *
     * @param array $settings User settings
     * @return string Quick start markdown
     */
    public function generate_quickstart( $settings ) {
        $approach = isset( $settings['approach'] ) ? $settings['approach'] : 'direct';
        $breakpoint = isset( $settings['mobile_breakpoint'] ) ? intval( $settings['mobile_breakpoint'] ) : 1200;
        $animation = isset( $settings['hamburger_animation'] ) ? $settings['hamburger_animation'] : 'spin';
        $position = isset( $settings['menu_position'] ) ? $settings['menu_position'] : 'left';
        $menu_name = $this->get_menu_name( $settings );
        $prop_name = isset( $settings['component_prop_name'] ) && ! empty( $settings['component_prop_name'] ) 
            ? sanitize_key( $settings['component_prop_name'] ) 
            : 'menuItems';
        
        $approach_name = ( 'component' === $approach ) ? 'Component' : 'Direct Loop';
        
        $guide = "## How to Use in ETCH\n\n";
        $guide .= "### Your Selected Approach: {$approach_name}\n\n";
        
        // Explain how the plugin provides data
        $guide .= "### How Menu Data Works\n\n";
        $guide .= "This plugin automatically makes your WordPress menus available in ETCH:\n\n";
        $guide .= "1. **Plugin provides data**: Uses `etch/dynamic_data/option` filter to inject menu data\n";
        $guide .= "2. **Available in ETCH as**: `options.menus.{$menu_name}`\n";
        $guide .= "3. **Your selected menu**: All items and submenus from your WordPress menu\n\n";
        $guide .= "---\n\n";
        
        if ( 'direct' === $approach ) {
            $guide .= "## Direct Loop Setup\n\n";
            $guide .= "**Best for**: Traditional WordPress sites with WordPress-managed menus\n\n";
            
            $guide .= "### Step 1: Verify WordPress Menu Exists\n\n";
            $guide .= "1. Go to WordPress Admin → Appearance → Menus\n";
            $guide .= "2. Verify your selected menu has items added\n";
            $guide .= "3. Note that menu will be available as `options.menus.{$menu_name}`\n\n";
            
            $guide .= "### Step 2: Add HTML to ETCH\n\n";
            $guide .= "1. Go to ETCH → Edit your page/template\n";
            $guide .= "2. Add an HTML element or custom block\n";
            $guide .= "3. Copy the HTML code from the **HTML tab** above\n";
            $guide .= "4. Paste it into ETCH's HTML panel\n\n";
            $guide .= "**What this does**: The loop `{#loop options.menus.{$menu_name} as item}` will automatically pull menu items from WordPress and update when you change the menu in WordPress Admin.\n\n";
            
            $guide .= "### Step 3: Add CSS to ETCH\n\n";
            $guide .= "1. Go to ETCH → CSS Panel\n";
            $guide .= "2. Copy the CSS code from the **CSS tab** above\n";
            $guide .= "3. Paste it into ETCH's CSS panel\n";
            $guide .= "4. Save your changes\n\n";
            
            $guide .= "### Step 4: Add JavaScript to ETCH\n\n";
            $guide .= "1. Go to ETCH → Settings → Custom Code\n";
            $guide .= "2. Find the JavaScript section\n";
            $guide .= "3. Copy the JavaScript code from the **JavaScript tab** above\n";
            $guide .= "4. Paste it into the JavaScript field\n";
            $guide .= "5. Save your settings\n\n";
            
            $guide .= "### Step 5: Test Your Navigation\n\n";
            $guide .= "1. Preview your page in ETCH\n";
            $guide .= "2. Test desktop navigation (hover over items with submenus)\n";
            $guide .= "3. Resize browser to {$breakpoint}px to test mobile breakpoint\n";
            $guide .= "4. Click hamburger menu to open/close mobile navigation\n";
            $guide .= "5. Test keyboard navigation (Tab, Enter, Esc keys)\n\n";
        } else {
            $guide .= "## Component Setup\n\n";
            $guide .= "**Best for**: Reusable components, headless setups, design systems\n\n";
            
            $guide .= "### Step 1: Create ETCH Component\n\n";
            $guide .= "1. Go to ETCH → Components → Add New\n";
            $guide .= "2. Name it \"Navigation\" (or your preferred name)\n";
            $guide .= "3. Copy the HTML code from the **HTML tab** above\n";
            $guide .= "4. Paste it into the component's HTML area\n";
            $guide .= "5. Save the component\n\n";
            
            $guide .= "### Step 2: Configure Component Property\n\n";
            $guide .= "1. In the component's property panel, click **Add New Property**\n";
            $guide .= "2. Set **Name**: `{$prop_name}`\n";
            $guide .= "3. Set **Type**: `Array` (or `Any`)\n";
            $guide .= "4. Set **Default Value**: `{options.menus.{$menu_name}}`\n";
            $guide .= "5. Save the property configuration\n\n";
            
            $guide .= "**What this does**: Tells ETCH to pull menu data from WordPress when the component is used.\n\n";
            
            $guide .= "### Step 3: Understand the Data Flow\n\n";
            $guide .= "Here's how WordPress menu data reaches your component:\n\n";
            $guide .= "```\n";
            $guide .= "WordPress Menu (in WordPress Admin)\n";
            $guide .= "    ↓\n";
            $guide .= "Plugin Filter: etch/dynamic_data/option\n";
            $guide .= "    ↓\n";
            $guide .= "ETCH Receives: options.menus.{$menu_name}\n";
            $guide .= "    ↓\n";
            $guide .= "Component Property: {$prop_name} = {{options.menus.{$menu_name}}}\n";
            $guide .= "    ↓\n";
            $guide .= "Component HTML: {{#loop props.{$prop_name} as item}}\n";
            $guide .= "    ↓\n";
            $guide .= "Rendered Navigation!\n";
            $guide .= "```\n\n";
            
            $guide .= "### Step 4: Use the Component\n\n";
            $guide .= "**Simple Usage** (uses WordPress menu):\n\n";
            $guide .= "```html\n";
            $guide .= "<Navigation />\n";
            $guide .= "```\n\n";
            $guide .= "The `{$prop_name}` property automatically uses `options.menus.{$menu_name}`\n\n";
            
            $guide .= "**Custom Data** (override with different source):\n\n";
            $guide .= "```html\n";
            $guide .= "<Navigation {$prop_name}={{customMenuData}} />\n";
            $guide .= "```\n\n";
            
            $guide .= "**Use Different WordPress Menu**:\n\n";
            $guide .= "```html\n";
            $guide .= "<Navigation {$prop_name}={{options.menus.footer_navigation}} />\n";
            $guide .= "```\n\n";
            
            $guide .= "### Step 5: Add CSS to ETCH\n\n";
            $guide .= "1. Go to ETCH → CSS Panel\n";
            $guide .= "2. Copy the CSS code from the **CSS tab** above\n";
            $guide .= "3. Paste it into ETCH's CSS panel\n";
            $guide .= "4. Save your changes\n\n";
            
            $guide .= "### Step 6: Add JavaScript to ETCH\n\n";
            $guide .= "1. Go to ETCH → Settings → Custom Code\n";
            $guide .= "2. Find the JavaScript section\n";
            $guide .= "3. Copy the JavaScript code from the **JavaScript tab** above\n";
            $guide .= "4. Paste it into the JavaScript field\n";
            $guide .= "5. Save your settings\n\n";
            
            $guide .= "### Step 7: Test Your Component\n\n";
            $guide .= "1. Preview your page in ETCH\n";
            $guide .= "2. Test desktop navigation (hover over items with submenus)\n";
            $guide .= "3. Resize browser to {$breakpoint}px to test mobile breakpoint\n";
            $guide .= "4. Click hamburger menu to open/close mobile navigation\n";
            $guide .= "5. Test keyboard navigation (Tab, Enter, Esc keys)\n\n";
            
            $guide .= "---\n\n";
            $guide .= "## Custom Data Structure\n\n";
            $guide .= "If using custom data (not WordPress menus), your `{$prop_name}` array should follow this structure:\n\n";
            $guide .= "```json\n";
            $guide .= "[\n";
            $guide .= "  {\n";
            $guide .= "    \"label\": \"Home\",\n";
            $guide .= "    \"url\": \"/\",\n";
            $guide .= "    \"active\": true,\n";
            $guide .= "    \"children\": []\n";
            $guide .= "  },\n";
            $guide .= "  {\n";
            $guide .= "    \"label\": \"About\",\n";
            $guide .= "    \"url\": \"/about\",\n";
            $guide .= "    \"active\": false,\n";
            $guide .= "    \"children\": [\n";
            $guide .= "      {\n";
            $guide .= "        \"label\": \"Team\",\n";
            $guide .= "        \"url\": \"/about/team\",\n";
            $guide .= "        \"active\": false\n";
            $guide .= "      }\n";
            $guide .= "    ]\n";
            $guide .= "  }\n";
            $guide .= "]\n";
            $guide .= "```\n\n";
        }
        
        $guide .= "---\n\n";
        $guide .= "## Your Current Settings\n\n";
        $guide .= "- **Approach**: {$approach_name}\n";
        if ( 'component' === $approach ) {
            $guide .= "- **Component Property**: {$prop_name}\n";
        }
        $guide .= "- **WordPress Menu**: Available as `options.menus.{$menu_name}`\n";
        $guide .= "- **Mobile Breakpoint**: {$breakpoint}px\n";
        $guide .= "- **Hamburger Animation**: {$animation}\n";
        $guide .= "- **Menu Position**: {$position}\n\n";
        
        $guide .= "---\n\n";
        $guide .= "## How This Plugin Works\n\n";
        $guide .= "**Data Provider**: This plugin uses ETCH's `etch/dynamic_data/option` filter hook to inject WordPress menu data into ETCH. When ETCH loads, all your WordPress menus become available as `options.menus.{menu_slug}`.\n\n";
        $guide .= "**Code Generator**: The plugin generates clean, production-ready HTML, CSS, and JavaScript that works with ETCH's loop system.\n\n";
        $guide .= "**Result**: Copy the generated code, paste into ETCH, and your navigation works immediately with live WordPress menu data!\n\n";
        $guide .= "---\n\n";
        $guide .= "## Troubleshooting\n\n";
        $guide .= "**Navigation shows no items:**\n";
        $guide .= "- Verify WordPress menu exists and has items (Appearance → Menus)\n";
        $guide .= "- Check that this plugin is activated\n";
        $guide .= "- Clear ETCH cache if needed\n\n";
        $guide .= "**Menu items appear but styling is wrong:**\n";
        $guide .= "- Ensure CSS is pasted in ETCH's CSS panel\n";
        $guide .= "- Check for CSS conflicts with other styles\n\n";
        $guide .= "**Hamburger menu doesn't work:**\n";
        $guide .= "- Ensure JavaScript is pasted in ETCH → Settings → Custom Code → JavaScript\n";
        $guide .= "- Check browser console for errors (F12)\n\n";
        $guide .= "**Component shows no data:**\n";
        $guide .= "- Verify component property `{$prop_name}` exists\n";
        $guide .= "- Check property default value is `{{options.menus.{$menu_name}}}`\n";
        $guide .= "- Ensure property type is `Array` or `Any`\n\n";
        
        return $guide;
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
        
        // Build hierarchical structure
        $menu_tree = array();
        $menu_by_id = array();
        
        foreach ( $menu_items as $item ) {
            $menu_by_id[ $item->ID ] = array(
                'id'       => $item->ID,
                'title'    => $item->title,
                'url'      => $item->url,
                'current'  => false, // This would be dynamic in ETCH
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
                    'html' => $html
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
            'scripts' => array(
                'global-nav-script' => array(
                    'code' => base64_encode( $js ),
                    'id' => 'global-nav-' . time()
                )
            )
        );
        
        return json_encode( $etch_structure, JSON_PRETTY_PRINT );
    }
}
