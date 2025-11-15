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
CREATE TABLE {$wpdb->prefix}draglearn_courses (
  course_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_name VARCHAR(255) NOT NULL,
  course_description TEXT,
  PRIMARY KEY (course_id)
) $collate;

CREATE TABLE {$wpdb->prefix}draglearn_lessons (
  lesson_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id BIGINT UNSIGNED NOT NULL,
  lesson_title VARCHAR(255) NOT NULL,
  lesson_content LONGTEXT,
  lesson_order INT DEFAULT 0,
  PRIMARY KEY (lesson_id),
  KEY course_id (course_id)
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
