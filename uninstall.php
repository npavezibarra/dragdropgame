<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package DragLearn
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ddg_games" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ddg_events" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ddg_attempts" );

delete_option( 'DRAGLEARN_DB_VERSION' );
