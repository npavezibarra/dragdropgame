<?php
/**
 * Plugin Name: DragLearn
 * Plugin URI: http://example.com/
 * Description: A basic WordPress plugin.
 * Version: 1.5.0
 * Author: Nicolas Pavez
 * Author URI: http://example.com/
 * Text Domain: draglearndtg
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Define DRAGLEARN_VERSION.
 */
define( 'DRAGLEARN_VERSION', '1.5.0' );

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
 * Load plugin textdomain.
 */
function draglearn_load_textdomain() {
    load_plugin_textdomain( 'draglearndtg', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'draglearn_load_textdomain' );

/**
 * Register frontend assets for the game.
 */
function ddg_register_assets() {
    error_log( 'DDG_ASSETS: Registering/Loading game script...' );

    wp_register_script(
        'ddg-game-script',
        plugins_url( '/assets/js/ddg-game.js', __FILE__ ),
        array(),
        '1.0',
        true
    );

    error_log( 'DDG_ASSETS: Script path = ' . plugins_url( '/assets/js/ddg-game.js', __FILE__ ) );

    wp_register_style(
        'ddg-game-style',
        plugins_url( 'assets/css/new-game.css', __FILE__ ),
        array(),
        '1.0'
    );
}
add_action( 'wp_enqueue_scripts', 'ddg_register_assets' );

/**
 * Add admin menu.
 */
function draglearn_admin_menu() {
    add_menu_page(
        __( 'DragLearn', 'draglearndtg' ),
        __( 'DragLearn', 'draglearndtg' ),
        'manage_options',
        'ddtg-my-games',
        array( 'DDTG_Admin', 'my_games_page_content' ),
        'dashicons-welcome-learn-more'
    );

    add_submenu_page(
        'ddtg-my-games',
        __( 'My Games', 'draglearndtg' ),
        __( 'My Games', 'draglearndtg' ),
        'manage_options',
        'ddtg-my-games',
        array( 'DDTG_Admin', 'my_games_page_content' )
    );

    $add_new_hook = add_submenu_page(
        'ddtg-my-games',
        __( 'Add New', 'draglearndtg' ),
        __( 'Add New', 'draglearndtg' ),
        'manage_options',
        'ddtg-create-game',
        array( 'DDTG_Admin', 'add_new_page_content' )
    );
    add_action( 'load-' . $add_new_hook, array( 'DDTG_Add_New', 'handle_form_submission_action' ) );

    add_submenu_page(
        'ddtg-my-games',
        __( 'Game Results', 'draglearndtg' ),
        __( 'Game Results', 'draglearndtg' ),
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
include_once dirname( __FILE__ ) . '/includes/class-ddtg-add-new.php';

/**
 * Include the admin class.
 */
include_once dirname( __FILE__ ) . '/includes/class-ddtg-admin.php';

/**
 * Include attempt handling.
 */
include_once dirname( __FILE__ ) . '/includes/class-ddtg-attempts.php';

add_action( 'admin_post_ddtg_delete_game', array( 'DDTG_Admin', 'handle_delete_game' ) );

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
