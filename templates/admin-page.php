<?php
/**
 * Admin Page Template
 *
 * @package Etch_WP_Menus
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="etch-admin-wrap">
    <div class="etch-admin-header">
        <h1 class="etch-admin-title"><?php esc_html_e( 'Etch WP Menus', 'etch-wp-menus' ); ?></h1>
        <p class="etch-admin-description">
            <?php esc_html_e( 'Generate customizable navigation code for the ETCH theme builder with mobile breakpoints and nested CSS.', 'etch-wp-menus' ); ?>
        </p>
    </div>

    <div class="etch-layout">
        <div class="etch-layout__main">
            <form id="nav-builder-form">

                <!-- Section 0: WordPress Menu Selection -->
                <div class="etch-card">
                    <div class="etch-card__header">
                        <h2 class="etch-card__title"><?php esc_html_e( 'Select WordPress Menu', 'etch-wp-menus' ); ?></h2>
                    </div>
                    <div class="etch-card__body">
                        <div class="etch-field">
                            <label for="menu-select" class="etch-field__label">
                                <?php esc_html_e( 'WordPress Menu', 'etch-wp-menus' ); ?>
                            </label>
                            <div class="etch-menu-selector-wrapper">
                                <select id="menu-select" name="menu_id" class="etch-field__input etch-menu-select">
                                    <option value=""><?php esc_html_e( '-- Select a Menu --', 'etch-wp-menus' ); ?></option>
                                    <?php
                                    $menus = wp_get_nav_menus();
                                    foreach ( $menus as $menu ) {
                                        echo '<option value="' . esc_attr( $menu->term_id ) . '">' . esc_html( $menu->name ) . '</option>';
                                    }
                                    ?>
                                </select>
                                <button type="button" id="view-menu-json" class="etch-button etch-button--secondary" disabled>
                                    <?php esc_html_e( 'View JSON', 'etch-wp-menus' ); ?>
                                </button>
                            </div>
                            <p class="etch-field__help">
                                <?php esc_html_e( 'Select the WordPress menu to use for navigation. The menu slug will be used in the generated code.', 'etch-wp-menus' ); ?>
                                <br>
                                <strong><?php esc_html_e( 'No menus?', 'etch-wp-menus' ); ?></strong>
                                <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" target="_blank"><?php esc_html_e( 'Create one here', 'etch-wp-menus' ); ?></a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Section 1: Choose Approach -->
                <div class="etch-card">
                    <div class="etch-card__header">
                        <h2 class="etch-card__title"><?php esc_html_e( 'ETCH Implementation Approach', 'etch-wp-menus' ); ?></h2>
                    </div>
                    <div class="etch-card__body">
                        <div class="etch-field">
                            <div class="etch-radio-group">
                                <div class="etch-radio">
                                    <input type="radio" id="approach-direct" name="approach" value="direct" checked>
                                    <label for="approach-direct" class="etch-radio__label">Direct Loop</label>
                                </div>
                                <div class="etch-radio">
                                    <input type="radio" id="approach-component" name="approach" value="component">
                                    <label for="approach-component" class="etch-radio__label">Component</label>
                                </div>
                            </div>
                            <p class="etch-field__help">
                                <strong>Direct Loop:</strong> Binds to WordPress menus using <code>{#loop options.menus.menu_name}</code><br>
                                <strong>Component:</strong> Uses props for reusable parts with <code>{#loop props.propertyName as item}</code>
                            </p>
                        </div>

                        <div class="etch-field etch-component-settings" style="display: none;">
                            <label for="component-prop-name" class="etch-field__label">
                                <?php esc_html_e( 'Component Property Name', 'etch-wp-menus' ); ?>
                            </label>
                            <input
                                type="text"
                                id="component-prop-name"
                                name="component_prop_name"
                                class="etch-field__input"
                                value="menuItems"
                                placeholder="menuItems">
                            <p class="etch-field__help">
                                <?php esc_html_e( 'What will you call the property in your ETCH component? This generates', 'etch-wp-menus' ); ?>
                                <code>{#loop props.<span id="prop-name-preview">menuItems</span> as item}</code>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Basic Settings -->
                <div class="etch-card">
                    <div class="etch-card__header">
                        <h2 class="etch-card__title"><?php esc_html_e( 'Basic Settings', 'etch-wp-menus' ); ?></h2>
                    </div>
                    <div class="etch-card__body">
                        <div class="etch-field">
                            <label for="container-class" class="etch-field__label">
                                <?php esc_html_e( 'Container Class', 'etch-wp-menus' ); ?>
                            </label>
                            <input
                                type="text"
                                id="container-class"
                                name="container_class"
                                class="etch-field__input"
                                value=""
                                placeholder="global-nav">
                            <p class="etch-field__help">
                                <?php esc_html_e( 'The base CSS class used in the generated output. Defaults to the selected menu name in kebab-case (e.g. "Global Navigation" becomes "global-navigation"). Change this to avoid duplicate CSS when generating multiple menus.', 'etch-wp-menus' ); ?>
                            </p>
                        </div>
                        <div class="etch-field">
                            <label for="submenu-depth-desktop" class="etch-field__label">
                                <?php esc_html_e( 'Sub Menu Depth on Desktop', 'etch-wp-menus' ); ?>
                            </label>
                            <input
                                type="number"
                                id="submenu-depth-desktop"
                                name="submenu_depth_desktop"
                                class="etch-field__input"
                                value="1"
                                min="0"
                                max="5"
                                step="1">
                            <p class="etch-field__help">
                                <?php esc_html_e( 'How many levels of sub menus to render on desktop. 0 = no sub menus, 1 = one level deep, etc.', 'etch-wp-menus' ); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Mobile Menu Support (Optional) -->
                <div class="etch-card">
                    <div class="etch-card__header">
                        <h2 class="etch-card__title"><?php esc_html_e( 'Mobile Menu Support', 'etch-wp-menus' ); ?></h2>
                    </div>
                    <div class="etch-card__body">
                        <div class="etch-field">
                            <div class="etch-toggle-item">
                                <label class="etch-toggle">
                                    <input type="checkbox" id="mobile-menu-support" name="mobile_menu_support" value="1">
                                    <span class="etch-toggle__slider"></span>
                                </label>
                                <span class="etch-toggle-label"><?php esc_html_e( 'Add Mobile Menu Support', 'etch-wp-menus' ); ?></span>
                            </div>
                            <p class="etch-field__help">
                                <?php esc_html_e( 'Enable this to include hamburger menu, mobile breakpoint, and responsive navigation in the generated output.', 'etch-wp-menus' ); ?>
                            </p>
                        </div>

                        <div id="mobile-settings" class="etch-mobile-settings" style="display: none;">

                            <div class="etch-field">
                                <label for="mobile-breakpoint" class="etch-field__label">
                                    <?php esc_html_e( 'Breakpoint (px)', 'etch-wp-menus' ); ?>
                                </label>
                                <input
                                    type="number"
                                    id="mobile-breakpoint"
                                    name="mobile_breakpoint"
                                    class="etch-field__input"
                                    value="1200"
                                    min="320"
                                    max="1920"
                                    step="1">
                                <p class="etch-field__help">
                                    <?php esc_html_e( 'When should navigation switch to mobile view? Default: 1200px', 'etch-wp-menus' ); ?>
                                </p>
                            </div>

                            <div class="etch-field">
                                <label class="etch-field__label">
                                    <?php esc_html_e( 'Hamburger Style', 'etch-wp-menus' ); ?>
                                </label>
                                <div class="etch-radio-group">
                                    <div class="etch-radio">
                                        <input type="radio" id="anim-spin" name="hamburger_animation" value="spin" checked>
                                        <label for="anim-spin" class="etch-radio__label">Spin</label>
                                    </div>
                                    <div class="etch-radio">
                                        <input type="radio" id="anim-squeeze" name="hamburger_animation" value="squeeze">
                                        <label for="anim-squeeze" class="etch-radio__label">Squeeze</label>
                                    </div>
                                    <div class="etch-radio">
                                        <input type="radio" id="anim-collapse" name="hamburger_animation" value="collapse">
                                        <label for="anim-collapse" class="etch-radio__label">Collapse</label>
                                    </div>
                                    <div class="etch-radio">
                                        <input type="radio" id="anim-arrow" name="hamburger_animation" value="arrow">
                                        <label for="anim-arrow" class="etch-radio__label">Arrow</label>
                                    </div>
                                </div>

                                <div class="etch-animation-preview">
                                    <div class="etch-preview-hamburger">
                                        <span class="etch-preview-line"></span>
                                        <span class="etch-preview-line"></span>
                                        <span class="etch-preview-line"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="etch-field">
                                <label class="etch-field__label">
                                    <?php esc_html_e( 'Mobile Menu Position', 'etch-wp-menus' ); ?>
                                </label>
                                <div class="etch-radio-group">
                                    <div class="etch-radio">
                                        <input type="radio" id="pos-left" name="menu_position" value="left" checked>
                                        <label for="pos-left" class="etch-radio__label">Left</label>
                                    </div>
                                    <div class="etch-radio">
                                        <input type="radio" id="pos-right" name="menu_position" value="right">
                                        <label for="pos-right" class="etch-radio__label">Right</label>
                                    </div>
                                    <div class="etch-radio">
                                        <input type="radio" id="pos-top" name="menu_position" value="top">
                                        <label for="pos-top" class="etch-radio__label">Top</label>
                                    </div>
                                    <div class="etch-radio">
                                        <input type="radio" id="pos-full" name="menu_position" value="full">
                                        <label for="pos-full" class="etch-radio__label">Full Overlay</label>
                                    </div>
                                </div>
                            </div>

                            <div class="etch-field">
                                <label class="etch-field__label">
                                    <?php esc_html_e( 'Sub Menu Behaviour', 'etch-wp-menus' ); ?>
                                </label>
                                <div class="etch-radio-group">
                                    <div class="etch-radio">
                                        <input type="radio" id="sub-always" name="submenu_behavior" value="always">
                                        <label for="sub-always" class="etch-radio__label">Always Show</label>
                                    </div>
                                    <div class="etch-radio">
                                        <input type="radio" id="sub-accordion" name="submenu_behavior" value="accordion" checked>
                                        <label for="sub-accordion" class="etch-radio__label">Accordion</label>
                                    </div>
                                    <div class="etch-radio">
                                        <input type="radio" id="sub-clickable" name="submenu_behavior" value="clickable">
                                        <label for="sub-clickable" class="etch-radio__label">Clickable</label>
                                    </div>
                                </div>
                            </div>

                            <div class="etch-field">
                                <label for="submenu-depth-mobile" class="etch-field__label">
                                    <?php esc_html_e( 'Sub Menu Depth on Mobile', 'etch-wp-menus' ); ?>
                                </label>
                                <input
                                    type="number"
                                    id="submenu-depth-mobile"
                                    name="submenu_depth_mobile"
                                    class="etch-field__input"
                                    value="1"
                                    min="0"
                                    max="5"
                                    step="1">
                                <p class="etch-field__help">
                                    <?php esc_html_e( 'How many levels of sub menus to render on mobile. 0 = no sub menus, 1 = one level deep, etc.', 'etch-wp-menus' ); ?>
                                </p>
                            </div>

                            <div class="etch-field">
                                <label class="etch-field__label">
                                    <?php esc_html_e( 'Close Options', 'etch-wp-menus' ); ?>
                                </label>
                                <div class="etch-toggles-group">
                                    <div class="etch-toggle-item">
                                        <label class="etch-toggle">
                                            <input type="checkbox" name="close_methods[]" value="hamburger" checked>
                                            <span class="etch-toggle__slider"></span>
                                        </label>
                                        <span class="etch-toggle-label">Click hamburger again</span>
                                    </div>
                                    <div class="etch-toggle-item">
                                        <label class="etch-toggle">
                                            <input type="checkbox" name="close_methods[]" value="outside" checked>
                                            <span class="etch-toggle__slider"></span>
                                        </label>
                                        <span class="etch-toggle-label">Click outside menu</span>
                                    </div>
                                    <div class="etch-toggle-item">
                                        <label class="etch-toggle">
                                            <input type="checkbox" name="close_methods[]" value="esc" checked>
                                            <span class="etch-toggle__slider"></span>
                                        </label>
                                        <span class="etch-toggle-label">Press ESC key</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Accessibility -->
                <div class="etch-card">
                    <div class="etch-card__header">
                        <h2 class="etch-card__title"><?php esc_html_e( 'Accessibility Features', 'etch-wp-menus' ); ?></h2>
                    </div>
                    <div class="etch-card__body">
                        <div class="etch-toggles-group">
                            <div class="etch-toggle-item etch-mobile-only-setting" style="display: none;">
                                <label class="etch-toggle">
                                    <input type="checkbox" name="accessibility[]" value="focus_trap" checked>
                                    <span class="etch-toggle__slider"></span>
                                </label>
                                <span class="etch-toggle-label">Focus trap in mobile menu</span>
                            </div>
                            <div class="etch-toggle-item etch-mobile-only-setting" style="display: none;">
                                <label class="etch-toggle">
                                    <input type="checkbox" name="accessibility[]" value="scroll_lock" checked>
                                    <span class="etch-toggle__slider"></span>
                                </label>
                                <span class="etch-toggle-label">Lock body scroll when menu open</span>
                            </div>
                            <div class="etch-toggle-item">
                                <label class="etch-toggle">
                                    <input type="checkbox" name="accessibility[]" value="aria" checked>
                                    <span class="etch-toggle__slider"></span>
                                </label>
                                <span class="etch-toggle-label">ARIA labels</span>
                            </div>
                            <div class="etch-toggle-item">
                                <label class="etch-toggle">
                                    <input type="checkbox" name="accessibility[]" value="keyboard" checked>
                                    <span class="etch-toggle__slider"></span>
                                </label>
                                <span class="etch-toggle-label">Keyboard navigation</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generate Button -->
                <div class="etch-actions">
                    <button type="button" id="generate-code" class="etch-button etch-button--primary etch-button--large">
                        <?php esc_html_e( 'Generate Navigation Code', 'etch-wp-menus' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="etch-layout__sidebar">
            <div class="etch-card">
                <div class="etch-card__header">
                    <h3 class="etch-card__title"><?php esc_html_e( 'Loop Properties', 'etch-wp-menus' ); ?></h3>
                </div>
                <div class="etch-card__body">
                    <div class="etch-properties-reference">
                        <div class="etch-property-section">
                            <h4 class="etch-property-section-title"><?php esc_html_e( 'Available Properties', 'etch-wp-menus' ); ?></h4>
                            <ul class="etch-property-list">
                                <li><code>item.title</code> <span class="etch-property-desc">Menu item label</span></li>
                                <li><code>item.url</code> <span class="etch-property-desc">Link URL</span></li>
                                <li><code>item.current</code> <span class="etch-property-desc">Is current page</span></li>
                                <li><code>item.current_parent</code> <span class="etch-property-desc">Is ancestor of current page</span></li>
                                <li><code>item.state_classes</code> <span class="etch-property-desc">Pre-computed BEM state classes (is-current, is-current-parent, has-submenu)</span></li>
                                <li><code>item.children</code> <span class="etch-property-desc">Submenu items</span></li>
                                <li><code>item.classes</code> <span class="etch-property-desc">WordPress CSS classes</span></li>
                                <li><code>item.target</code> <span class="etch-property-desc">Link target</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Output Section (Hidden by default) -->
    <div id="output-section" class="etch-output-section" style="display: none;">
        <div class="etch-tabs">
            <button class="etch-tab is-active" data-tab="etch-json">
                <span>ETCH JSON</span>
                <span class="etch-tab__badge">RECOMMENDED</span>
            </button>
            <button class="etch-tab" data-tab="html">HTML</button>
            <button class="etch-tab" data-tab="css">CSS</button>
            <button class="etch-tab etch-tab--js" data-tab="javascript" style="display: none;">JavaScript</button>
        </div>

        <div class="etch-tab-content" data-content="html">
            <div class="etch-code-block">
                <div class="etch-code-block__header">
                    <span class="etch-code-block__label">HTML</span>
                    <button class="etch-code-block__copy" data-copy="html">Copy Code</button>
                </div>
                <pre><code id="html-output"></code></pre>
            </div>
        </div>

        <div class="etch-tab-content" data-content="css">
            <div class="etch-code-block">
                <div class="etch-code-block__header">
                    <span class="etch-code-block__label">CSS</span>
                    <button class="etch-code-block__copy" data-copy="css">Copy Code</button>
                </div>
                <pre><code id="css-output"></code></pre>
            </div>
        </div>

        <div class="etch-tab-content etch-tab-content--js" data-content="javascript" style="display: none;">
            <div class="etch-code-block">
                <div class="etch-code-block__header">
                    <span class="etch-code-block__label">JavaScript</span>
                    <button class="etch-code-block__copy" data-copy="javascript">Copy Code</button>
                </div>
                <pre><code id="js-output"></code></pre>
            </div>
        </div>

        <div class="etch-tab-content is-active" data-content="etch-json">
            <div class="etch-code-block">
                <div class="etch-code-block__header">
                    <span class="etch-code-block__label">ETCH Structure JSON</span>
                    <button class="etch-code-block__copy" data-copy="etch-json">Copy JSON</button>
                </div>
                <pre><code id="etch-json-output"></code></pre>
            </div>
        </div>
    </div>
</div>

<!-- Menu JSON Preview Modal -->
<div id="menu-json-modal" class="etch-modal" style="display: none;">
    <div class="etch-modal__overlay"></div>
    <div class="etch-modal__content">
        <div class="etch-modal__header">
            <h3 class="etch-modal__title"><?php esc_html_e( 'Menu JSON Structure', 'etch-wp-menus' ); ?></h3>
            <button class="etch-modal__close" aria-label="Close">&times;</button>
        </div>
        <div class="etch-modal__body">
            <p class="etch-modal__description">
                <?php esc_html_e( 'This is how ETCH will see your WordPress menu data. Use this for reference when building custom components.', 'etch-wp-menus' ); ?>
            </p>
            <div class="etch-code-block">
                <div class="etch-code-block__header">
                    <span class="etch-code-block__label">Menu Data</span>
                    <button class="etch-code-block__copy" data-copy="menu-json">Copy JSON</button>
                </div>
                <pre><code id="menu-json-output"></code></pre>
            </div>
        </div>
    </div>
</div>
