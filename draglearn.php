<?php
/**
 * Plugin Name: DragLearn
 * Plugin URI: http://example.com/
 * Description: A basic WordPress plugin.
 * Version: 1.0
 * Author: Nicolas Pavez
 * Author URI: http://example.com/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Define DRAGLEARN_VERSION.
 */
define( 'DRAGLEARN_VERSION', '1.0' );

/**
 * Include the installer class.
 */
include_once dirname( __FILE__ ) . '/includes/class-ddtg-installer.php';

/**
 * Activation hook.
 */
function draglearn_activate() {
    DDTG_Installer::install();
}
register_activation_hook( __FILE__, 'draglearn_activate' );

/**
 * Plugin update logic.
 */
function draglearn_update_check() {
    if ( get_option( 'draglearn_version' ) !== DRAGLEARN_VERSION ) {
        DDTG_Installer::install();
    }
}
add_action( 'plugins_loaded', 'draglearn_update_check' );

/**
 * Add admin menu.
 */
function draglearn_admin_menu() {
    add_menu_page(
        __( 'DragLearn Courses', 'draglearn' ),
        __( 'DragLearn', 'draglearn' ),
        'manage_options',
        'draglearn-courses',
        'draglearn_courses_page_html',
        'dashicons-welcome-learn-more'
    );
}
add_action( 'admin_menu', 'draglearn_admin_menu' );

/**
 * Admin page content.
 */
function draglearn_courses_page_html() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p><?php esc_html_e( 'Welcome to the DragLearn courses page.', 'draglearn' ); ?></p>
    </div>
    <?php
}
