<?php
/**
 * Admin handler for the DragLearn game.
 *
 * @package DragLearn
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * DDTG_Admin Class.
 */
class DDTG_Admin {

    /**
     * Handle the AJAX score submission.
     */
    public static function handle_score_submission() {
        // Security check
        check_ajax_referer( 'ddtg_game_nonce', 'nonce' );

        global $wpdb;
        $attempts_table = $wpdb->prefix . 'draglearn_attempts';

        $attempt_id = isset( $_POST['attempt_id'] ) ? intval( $_POST['attempt_id'] ) : 0;
        $score = isset( $_POST['score'] ) ? intval( $_POST['score'] ) : 0;

        if ( ! $attempt_id || ! get_current_user_id() ) {
            wp_send_json_error( array( 'message' => 'Invalid attempt or user.' ) );
            return;
        }

        // Verify the attempt belongs to the current user
        $attempt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$attempts_table} WHERE attempt_id = %d AND user_id = %d", $attempt_id, get_current_user_id() ) );

        if ( ! $attempt ) {
            wp_send_json_error( array( 'message' => 'Attempt not found or permission denied.' ) );
            return;
        }

        // Update the attempt record
        $wpdb->update(
            $attempts_table,
            array(
                'finish_time' => current_time( 'mysql', 1 ),
                'score'       => $score,
            ),
            array(
                'attempt_id' => $attempt_id,
            )
        );

        wp_send_json_success( array( 'message' => 'Score saved successfully.' ) );
    }
}
