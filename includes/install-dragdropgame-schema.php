<?php
/**
 * Manual installer script for DragDropGame database tables.
 *
 * Usage with WP-CLI:
 *   wp eval-file wp-content/plugins/dragdropgame/includes/install-dragdropgame-schema.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( "This script must be run within the WordPress context.\n" );
}

require_once __DIR__ . '/class-ddtg-installer.php';

DDTG_Installer::install();

echo "DragDropGame schema installed successfully.\n";
