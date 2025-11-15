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
        __( 'DragLearn', 'draglearn' ),
        __( 'DragLearn', 'draglearn' ),
        'manage_options',
        'ddtg-my-games',
        array( 'DDTG_Admin', 'my_games_page_content' ),
        'dashicons-welcome-learn-more'
    );

    add_submenu_page(
        'ddtg-my-games',
        __( 'My Games', 'draglearn' ),
        __( 'My Games', 'draglearn' ),
        'manage_options',
        'ddtg-my-games',
        array( 'DDTG_Admin', 'my_games_page_content' )
    );

    add_submenu_page(
        'ddtg-my-games',
        __( 'Add New', 'draglearn' ),
        __( 'Add New', 'draglearn' ),
        'manage_options',
        'ddtg-create-game',
        array( 'DDTG_Admin', 'add_new_page_content' )
    );

    add_submenu_page(
        'ddtg-my-games',
        __( 'Game Results', 'draglearn' ),
        __( 'Game Results', 'draglearn' ),
        'manage_options',
        'ddtg-game-results',
        array( 'DDTG_Admin', 'results_page_content' )
    );

    // This hides the "Game Results" page from the menu, but it's still accessible.
    remove_submenu_page( 'ddtg-my-games', 'ddtg-game-results' );
}
add_action( 'admin_menu', 'draglearn_admin_menu' );

/**
 * Include the list table classes.
 */
include_once dirname( __FILE__ ) . '/includes/class-ddtg-games-list-table.php';
include_once dirname( __FILE__ ) . '/includes/class-ddtg-results-list-table.php';

/**
 * Include the admin class.
 */
include_once dirname( __FILE__ ) . '/includes/class-ddtg-admin.php';

/**
 * AJAX hooks for score submission.
 */
add_action( 'wp_ajax_record_score', array( 'DDTG_Admin', 'handle_score_submission' ) );
add_action( 'wp_ajax_nopriv_record_score', array( 'DDTG_Admin', 'handle_score_submission' ) );

/**
 * Include the shortcode class.
 */
include_once dirname( __FILE__ ) . '/includes/class-ddtg-shortcode.php';
add_action( 'init', array( 'DDTG_Shortcode', 'init' ) );
