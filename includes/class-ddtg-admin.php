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
     * Display the "My Games" page content.
     */
    public static function my_games_page_content() {
        $games_list_table = new DDTG_Games_List_Table();
        $games_list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e( 'My Games', 'draglearn' ); ?>
                <a href="<?php echo admin_url( 'admin.php?page=ddtg-create-game' ); ?>" class="page-title-action">
                    <?php esc_html_e( 'Add New', 'draglearn' ); ?>
                </a>
            </h1>
            <?php $games_list_table->display(); ?>
        </div>
        <?php
    }

    /**
     * Display the results page for a specific game.
     */
    public static function results_page_content() {
        global $wpdb;
        $games_table = $wpdb->prefix . 'draglearn_games';
        $game_id     = isset( $_GET['game_id'] ) ? intval( $_GET['game_id'] ) : 0;

        if ( ! $game_id ) {
            wp_die( esc_html__( 'Invalid game ID.', 'draglearn' ) );
        }

        $game = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM {$games_table} WHERE game_id = %d", $game_id ) );

        if ( ! $game ) {
            wp_die( esc_html__( 'Game not found.', 'draglearn' ) );
        }

        $results_list_table = new DDTG_Results_List_Table( $game_id );
        $results_list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( sprintf( __( 'Results for %s', 'draglearn' ), $game->name ) ); ?></h1>
            <?php $results_list_table->display(); ?>
        </div>
        <?php
    }

    /**
     * Handle the AJAX score submission.
     */
    public static function handle_score_submission() {
        // Security check
        check_ajax_referer( 'ddtg_game_nonce', 'nonce' );

        global $wpdb;
        $attempts_table = $wpdb->prefix . 'draglearn_attempts';

        $attempt_id = isset( $_POST['attempt_id'] ) ? intval( $_POST['attempt_id'] ) : 0;
        $score      = isset( $_POST['score'] ) ? intval( $_POST['score'] ) : 0;

        if ( ! $attempt_id || ! get_current_user_id() ) {
            wp_send_json_error( [ 'message' => 'Invalid attempt or user.' ] );
            return;
        }

        // Verify the attempt belongs to the current user
        $attempt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$attempts_table} WHERE attempt_id = %d AND user_id = %d", $attempt_id, get_current_user_id() ) );

        if ( ! $attempt ) {
            wp_send_json_error( [ 'message' => 'Attempt not found or permission denied.' ] );
            return;
        }

        // Update the attempt record
        $wpdb->update(
            $attempts_table,
            [
                'finish_time' => current_time( 'mysql', 1 ),
                'score'       => $score,
            ],
            [
                'attempt_id' => $attempt_id,
            ]
        );

        wp_send_json_success( [ 'message' => 'Score saved successfully.' ] );
    }
}
