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
        $games_table = $wpdb->prefix . 'ddg_games';
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
     * Display the "Add New" page content.
     * Acts as a controller, calling the form processing method if a form was submitted,
     * and then calling the method to display the form.
     */
    public static function add_new_page_content() {
        if ( isset( $_POST['ddtg_add_new_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddtg_add_new_nonce'] ) ), 'ddtg_add_new_action' ) ) {
            self::process_create_game_form();
        }
        self::create_game_page_content();
    }

    /**
     * Display the "Add New Game" form.
     */
    public static function create_game_page_content() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Add New Game', 'draglearn' ); ?></h1>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'ddtg_add_new_action', 'ddtg_add_new_nonce' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="gameName"><?php esc_html_e( 'Game Name', 'draglearn' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gameName" name="gameName" class="regular-text" required />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="gameDescription"><?php esc_html_e( 'Game Description', 'draglearn' ); ?></label>
                        </th>
                        <td>
                            <textarea id="gameDescription" name="gameDescription" class="large-text"></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="numberOfSlots"><?php esc_html_e( 'Number of Slots', 'draglearn' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="numberOfSlots" name="numberOfSlots" class="regular-text" required />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="attemptsNumber"><?php esc_html_e( 'Number of Attempts', 'draglearn' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="attemptsNumber" name="attemptsNumber" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="attemptsPeriod"><?php esc_html_e( 'Attempts Period', 'draglearn' ); ?></label>
                        </th>
                        <td>
                            <select id="attemptsPeriod" name="attemptsPeriod">
                                <option value="unlimited"><?php esc_html_e( 'Unlimited', 'draglearn' ); ?></option>
                                <option value="day"><?php esc_html_e( 'Per Day', 'draglearn' ); ?></option>
                                <option value="week"><?php esc_html_e( 'Per Week', 'draglearn' ); ?></option>
                                <option value="month"><?php esc_html_e( 'Per Month', 'draglearn' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="uploadCSV"><?php esc_html_e( 'CSV File', 'draglearn' ); ?></label>
                        </th>
                        <td>
                            <input type="file" id="uploadCSV" name="uploadCSV" accept=".csv" required />
                            <p class="description">
                                <?php esc_html_e( 'Upload a CSV file with two columns: "prompt" and "completion".', 'draglearn' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Create Game', 'draglearn' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle the "Add New Game" form submission.
     */
    private static function process_create_game_form() {
        global $wpdb;
        $games_table  = $wpdb->prefix . 'ddg_games';
        $events_table = $wpdb->prefix . 'ddg_events';

        // Map and sanitize input variables
        $game_name        = isset( $_POST['gameName'] ) ? sanitize_text_field( wp_unslash( $_POST['gameName'] ) ) : '';
        $game_description = isset( $_POST['gameDescription'] ) ? sanitize_textarea_field( wp_unslash( $_POST['gameDescription'] ) ) : '';
        $number_of_slots  = isset( $_POST['numberOfSlots'] ) ? intval( $_POST['numberOfSlots'] ) : 0;
        $attempts_number  = isset( $_POST['attemptsNumber'] ) ? intval( $_POST['attemptsNumber'] ) : 0;
        $attempts_period  = isset( $_POST['attemptsPeriod'] ) ? sanitize_text_field( wp_unslash( $_POST['attemptsPeriod'] ) ) : 'unlimited';
        $csv_file         = isset( $_FILES['uploadCSV'] ) ? $_FILES['uploadCSV'] : null;

        // Validation for required fields
        if ( ! $game_name || ! $csv_file || UPLOAD_ERR_OK !== $csv_file['error'] || $number_of_slots <= 0 ) {
            add_action( 'admin_notices', function() {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e( 'Error: Please fill in all required fields, upload a valid CSV file, and ensure the number of slots is greater than 0.', 'draglearn' ); ?></p>
                </div>
                <?php
            } );
            return;
        }

        // Process attempt logic
        $attempt_limit = -1; // Default to unlimited
        $limit_period  = 'total'; // Default period

        if ( 'unlimited' !== $attempts_period ) {
            $attempt_limit = $attempts_number;
            $limit_period  = $attempts_period;
        }

        // Create a unique shortcode slug
        $shortcode_slug = sanitize_title( $game_name );
        $existing_slug_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$games_table} WHERE shortcode_slug = %s", $shortcode_slug ) );
        if ( $existing_slug_count > 0 ) {
            $shortcode_slug .= '-' . ( $existing_slug_count + 1 );
        }

        // Insert the new game into the database
        $wpdb->insert(
            $games_table,
            [
                'user_id'            => get_current_user_id(),
                'game_name'          => $game_name,
                'description'        => $game_description,
                'shortcode_slug'     => $shortcode_slug,
                'num_events_to_show' => $number_of_slots,
                'attempt_limit'      => $attempt_limit,
                'limit_period'       => $limit_period,
                'date_created'       => current_time( 'mysql' ),
            ]
        );
        $game_id = $wpdb->insert_id;

        // Process the CSV file
        $handle = fopen( $csv_file['tmp_name'], 'r' );
        if ( false !== $handle ) {
            fgetcsv( $handle ); // Skip the header row

            while ( ( $row = fgetcsv( $handle ) ) !== false ) {
                $wpdb->insert(
                    $events_table,
                    [
                        'game_id'    => $game_id,
                        'event_type' => 'pair',
                        'event_data' => wp_json_encode( [
                            'prompt'     => $row[0],
                            'completion' => $row[1],
                        ] ),
                    ]
                );
            }
            fclose( $handle );
        }

        // Redirect to the "My Games" page
        wp_safe_redirect( admin_url( 'admin.php?page=ddtg-my-games' ) );
        exit;
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
