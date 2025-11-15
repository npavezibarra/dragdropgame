<?php
/**
 * Installation related functions and actions.
 *
 * @package DragLearn
 */

defined( 'ABSPATH' ) || exit;

/**
 * DDTG_Installer Class.
 */
class DDTG_Installer {

    /**
     * Hook in tabs.
     */
    public function __construct() {
        // No direct instantiation.
    }

    /**
     * Install DragLearn.
     */
    public static function install() {
        if ( ! is_blog_installed() ) {
            return;
        }

        // Check if we are not already installing.
        if ( 'yes' === get_transient( 'ddtg_installing' ) ) {
            return;
        }

        // If we are installing, tell WordPress we are doing so.
        set_transient( 'ddtg_installing', 'yes', MINUTE_IN_SECONDS * 10 );

        self::create_tables();
        self::update_version();

        delete_transient( 'ddtg_installing' );
    }

    /**
     * Get Schema.
     *
     * @return string
     */
    private static function get_schema() {
        global $wpdb;

        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }

        $tables = "
CREATE TABLE {$wpdb->prefix}ddg_games (
  game_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (game_id)
) $collate;

CREATE TABLE {$wpdb->prefix}ddg_events (
  event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  game_id BIGINT UNSIGNED NOT NULL,
  event_type VARCHAR(50) NOT NULL,
  event_data TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id),
  KEY game_id (game_id)
) $collate;

CREATE TABLE {$wpdb->prefix}ddg_attempts (
  attempt_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  game_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  finish_time TIMESTAMP NULL,
  score INT,
  total INT,
  PRIMARY KEY (attempt_id),
  KEY game_id (game_id),
  KEY user_id (user_id)
) $collate;
        ";

        return $tables;
    }

    /**
     * Create tables.
     */
    private static function create_tables() {
        global $wpdb;

        $wpdb->hide_errors();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( self::get_schema() );
    }

    /**
     * Update DragLearn version to current.
     */
    private static function update_version() {
        update_option( 'draglearn_version', DRAGLEARN_VERSION );
    }
}
