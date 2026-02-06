<?php
/**
 * Plugin Name: Etch WP Menus
 * Plugin URI: https://bbg.digital
 * Description: Generate customizable navigation code for the ETCH theme builder with mobile breakpoints and nested CSS.
 * Version: 1.4.0
 * Author: Stuart Davison
 * Author URI: https://bbg.digital
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: etch-wp-menus
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'ETCH_WP_MENUS_VERSION', '1.4.0' );
define( 'ETCH_WP_MENUS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ETCH_WP_MENUS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Plugin Class
 */
class Etch_WP_Menus {
    
    /**
     * Single instance of the class
     *
     * @var Etch_WP_Menus
     */
    private static $instance = null;
    
    /**
     * Get single instance
     *
     * @return Etch_WP_Menus
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once ETCH_WP_MENUS_PLUGIN_DIR . 'includes/class-navigation-generator.php';
        require_once ETCH_WP_MENUS_PLUGIN_DIR . 'includes/class-admin-page.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_ajax_generate_navigation_code', array( $this, 'ajax_generate_code' ) );
        add_action( 'wp_ajax_get_menu_json', array( $this, 'ajax_get_menu_json' ) );
        
        // Add menu data to ETCH's loop manager
        add_filter( 'etch/dynamic_data/option', array( $this, 'add_menus_to_etch' ) );
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_management_page(
            __( 'Etch WP Menus', 'etch-wp-menus' ),
            __( 'Etch WP Menus', 'etch-wp-menus' ),
            'manage_options',
            'etch-wp-menus',
            array( 'Etch_Admin_Page', 'render' )
        );
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on our admin page
        if ( 'tools_page_etch-wp-menus' !== $hook ) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'etch-admin-builder',
            ETCH_WP_MENUS_PLUGIN_URL . 'assets/css/admin-builder.css',
            array(),
            ETCH_WP_MENUS_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'etch-admin-builder',
            ETCH_WP_MENUS_PLUGIN_URL . 'assets/js/admin-builder.js',
            array( 'jquery' ),
            ETCH_WP_MENUS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script(
            'etch-admin-builder',
            'navBuilderData',
            array(
                'nonce'  => wp_create_nonce( 'nav_builder_nonce' ),
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
            )
        );
    }
    
    /**
     * AJAX handler for code generation
     */
    public function ajax_generate_code() {
        // Verify nonce
        check_ajax_referer( 'nav_builder_nonce', 'nonce' );
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'etch-wp-menus' ) ) );
        }
        
        // Get settings from POST
        $settings_json = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '{}';
        $settings = json_decode( $settings_json, true );
        
        if ( ! is_array( $settings ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid settings', 'etch-wp-menus' ) ) );
        }
        
        // Generate code
        $generator = new Etch_Navigation_Generator();
        
        $response = array(
            'html'       => $generator->generate_html( $settings ),
            'css'        => $generator->generate_css( $settings ),
            'javascript' => $generator->generate_javascript( $settings ),
            'etch_json'  => $generator->generate_etch_json( $settings ),
        );
        
        wp_send_json_success( $response );
    }
    
    /**
     * AJAX handler for getting menu JSON
     */
    public function ajax_get_menu_json() {
        // Verify nonce
        check_ajax_referer( 'nav_builder_nonce', 'nonce' );
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'etch-wp-menus' ) ) );
        }
        
        // Get menu ID from POST
        $menu_id = isset( $_POST['menu_id'] ) ? intval( $_POST['menu_id'] ) : 0;
        
        if ( ! $menu_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid menu ID', 'etch-wp-menus' ) ) );
        }
        
        // Generate JSON
        $generator = new Etch_Navigation_Generator();
        $menu_json = $generator->get_menu_json( $menu_id );
        
        wp_send_json_success( array( 'json' => $menu_json ) );
    }
    
    /**
     * Add WordPress menus to ETCH's dynamic data (options.menus)
     *
     * @param array $data Existing options data
     * @return array Modified options data with menus
     */
    public function add_menus_to_etch( $data ) {
        // Get all registered WordPress menus
        $menus = wp_get_nav_menus();
        
        if ( empty( $menus ) ) {
            return $data;
        }
        
        // Initialize menus array
        $data['menus'] = array();
        
        // Get generator instance to access sanitize_for_etch method
        $generator = new Etch_Navigation_Generator();
        
        // Loop through each menu and build hierarchical structure
        foreach ( $menus as $menu ) {
            // Use ETCH-compatible slug (underscores only, no hyphens)
            $menu_slug = $generator->sanitize_for_etch( $menu->name );
            $menu_items = wp_get_nav_menu_items( $menu->term_id );
            
            if ( ! $menu_items ) {
                continue;
            }
            
            // Build hierarchical menu structure
            $menu_tree = array();
            $menu_by_id = array();
            
            // First pass: Create flat array indexed by ID
            foreach ( $menu_items as $item ) {
                $menu_by_id[ $item->ID ] = array(
                    'id'       => $item->ID,
                    'title'    => $item->title,
                    'url'      => $item->url,
                    'target'   => $item->target,
                    'classes'  => implode( ' ', $item->classes ),
                    'current'  => false, // Will be set dynamically by ETCH
                    'children' => array(),
                );
            }
            
            // Second pass: Build hierarchy
            foreach ( $menu_items as $item ) {
                if ( $item->menu_item_parent == 0 ) {
                    // Top-level item
                    $menu_tree[] = &$menu_by_id[ $item->ID ];
                } else {
                    // Child item
                    if ( isset( $menu_by_id[ $item->menu_item_parent ] ) ) {
                        $menu_by_id[ $item->menu_item_parent ]['children'][] = &$menu_by_id[ $item->ID ];
                    }
                }
            }
            
            // Add this menu to the menus array
            $data['menus'][ $menu_slug ] = $menu_tree;
        }
        
        return $data;
    }
}

/**
 * Initialize the plugin
 */
function etch_wp_menus_init() {
    return Etch_WP_Menus::get_instance();
}

// Start the plugin
etch_wp_menus_init();
