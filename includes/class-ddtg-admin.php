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
                <?php esc_html_e( 'My Games', 'draglearndtg' ); ?>
                <a href="<?php echo admin_url( 'admin.php?page=ddtg-create-game' ); ?>" class="page-title-action">
                    <?php esc_html_e( 'Add New', 'draglearndtg' ); ?>
                </a>
            </h1>
            <?php self::render_admin_notices(); ?>
            <?php $games_list_table->display(); ?>
        </div>
        <?php
    }

    /**
     * Display the results page for a specific game.
     */
    public static function results_page_content() {
        global $wpdb;
        $games_table = $wpdb->prefix . 'ddg_games';
        $game_id     = isset( $_GET['game_id'] ) ? absint( $_GET['game_id'] ) : 0;

        if ( ! $game_id ) {
            wp_die( esc_html__( 'Invalid game ID.', 'draglearndtg' ) );
        }

        $game = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT game_id, game_name FROM {$games_table} WHERE game_id = %d AND user_id = %d",
                $game_id,
                get_current_user_id()
            )
        );

        if ( ! $game ) {
            wp_die( esc_html__( 'Game not found or you do not have permission to view it.', 'draglearndtg' ) );
        }

        $results_list_table = new DDTG_Results_List_Table( $game_id );
        $results_list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html( sprintf( __( 'Results for %s', 'draglearndtg' ), $game->game_name ) ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ddtg-my-games' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to My Games', 'draglearndtg' ); ?></a>
            <hr class="wp-header-end" />
            <nav class="ddtg-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumbs', 'draglearndtg' ); ?>">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ddtg-my-games' ) ); ?>"><?php esc_html_e( 'My Games', 'draglearndtg' ); ?></a>
                <span class="separator" aria-hidden="true">&#8250;</span>
                <span class="current"><?php echo esc_html( $game->game_name ); ?></span>
            </nav>
            <?php $results_list_table->display(); ?>
        </div>
        <?php
    }

    /**
     * Display the "Add New" page content.
     */
    public static function add_new_page_content() {
        DDTG_Add_New::add_new_page_content();
    }

    /**
     * Handle the AJAX score submission.
     */
    public static function handle_score_submission() {
        // Security check
        check_ajax_referer( 'ddtg_game_nonce', 'nonce' );

        global $wpdb;
        $attempts_table = $wpdb->prefix . 'ddg_attempts';

        $attempt_id = isset( $_POST['attempt_id'] ) ? intval( $_POST['attempt_id'] ) : 0;
        $score      = isset( $_POST['score'] ) ? intval( $_POST['score'] ) : 0;

        if ( ! $attempt_id || ! get_current_user_id() ) {
            wp_send_json_error( [ 'message' => __( 'Invalid attempt or user.', 'draglearndtg' ) ] );
            return;
        }

        // Verify the attempt belongs to the current user
        $attempt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$attempts_table} WHERE attempt_id = %d AND user_id = %d", $attempt_id, get_current_user_id() ) );

        if ( ! $attempt ) {
            wp_send_json_error( [ 'message' => __( 'Attempt not found or permission denied.', 'draglearndtg' ) ] );
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

        wp_send_json_success( [ 'message' => __( 'Score saved successfully.', 'draglearndtg' ) ] );
    }

    /**
     * Handle a game deletion request.
     */
    public static function handle_delete_game() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to delete this game.', 'draglearndtg' ) );
        }

        $game_id = isset( $_GET['game_id'] ) ? absint( $_GET['game_id'] ) : 0;

        if ( ! $game_id ) {
            wp_die( esc_html__( 'Invalid game ID.', 'draglearndtg' ) );
        }

        check_admin_referer( 'ddtg_delete_game_' . $game_id );

        global $wpdb;
        $games_table    = $wpdb->prefix . 'ddg_games';
        $events_table   = $wpdb->prefix . 'ddg_events';
        $attempts_table = $wpdb->prefix . 'ddg_attempts';

        $game_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT game_id FROM {$games_table} WHERE game_id = %d AND user_id = %d",
                $game_id,
                get_current_user_id()
            )
        );

        if ( ! $game_exists ) {
            wp_die( esc_html__( 'Game not found or permission denied.', 'draglearndtg' ) );
        }

        $wpdb->delete( $attempts_table, [ 'game_id' => $game_id ] );
        $wpdb->delete( $events_table, [ 'game_id' => $game_id ] );
        $wpdb->delete( $games_table, [ 'game_id' => $game_id ] );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'        => 'ddtg-my-games',
                    'ddtg_notice' => 'deleted',
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Output admin notices.
     */
    private static function render_admin_notices() {
        if ( isset( $_GET['ddtg_notice'] ) && 'deleted' === sanitize_key( wp_unslash( $_GET['ddtg_notice'] ) ) ) {
            printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'Game deleted successfully.', 'draglearndtg' ) );
        }
    }
}
