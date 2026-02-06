<?php
/**
 * Admin Page Handler
 *
 * @package Etch_WP_Menus
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Etch_Admin_Page
 */
class Etch_Admin_Page {
    
    /**
     * Render the admin page
     */
    public static function render() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'etch-wp-menus' ) );
        }
        
        // Get default settings
        $defaults = self::get_default_settings();
        
        include ETCH_WP_MENUS_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    /**
     * Get default settings
     *
     * @return array Default settings
     */
    public static function get_default_settings() {
        return array(
            'approach'              => 'direct',
            'container_class'       => '',
            'submenu_depth_desktop' => 1,
            'mobile_menu_support'   => false,
            'mobile_breakpoint'     => 1200,
            'hamburger_animation'   => 'spin',
            'menu_position'         => 'left',
            'submenu_behavior'      => 'accordion',
            'submenu_depth_mobile'  => 1,
            'close_methods'         => array( 'hamburger', 'outside', 'esc' ),
            'accessibility'         => array( 'focus_trap', 'scroll_lock', 'aria', 'keyboard' ),
        );
    }
}
