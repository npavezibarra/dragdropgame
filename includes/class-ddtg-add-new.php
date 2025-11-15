<?php
/**
 * Add New Game page for the DragLearn game.
 *
 * @package DragLearn
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * DDTG_Add_New Class.
 */
class DDTG_Add_New {

    /**
     * Display the "Add New" page content.
     */
    public static function add_new_page_content() {
        if ( isset( $_POST['ddtg_add_new_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddtg_add_new_nonce'] ) ), 'ddtg_add_new_action' ) ) {
            self::handle_form_submission();
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Add New Game', 'draglearn' ); ?></h1>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'ddtg_add_new_action', 'ddtg_add_new_nonce' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_game_name"><?php esc_html_e( 'Game Name', 'draglearn' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="ddtg_game_name" name="ddtg_game_name" class="regular-text" required />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_game_description"><?php esc_html_e( 'Game Description', 'draglearn' ); ?></label>
                        </th>
                        <td>
                            <textarea id="ddtg_game_description" name="ddtg_game_description" class="large-text"></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_csv_file"><?php esc_html_e( 'CSV File', 'draglearn' ); ?></label>
                        </th>
                        <td>
                            <input type="file" id="ddtg_csv_file" name="ddtg_csv_file" accept=".csv" required />
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
     * Handle the form submission.
     */
    private static function handle_form_submission() {
        global $wpdb;
        $games_table  = $wpdb->prefix . 'ddg_games';
        $events_table = $wpdb->prefix . 'ddg_events';

        $game_name        = isset( $_POST['ddtg_game_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ddtg_game_name'] ) ) : '';
        $game_description = isset( $_POST['ddtg_game_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ddtg_game_description'] ) ) : '';
        $csv_file         = isset( $_FILES['ddtg_csv_file'] ) ? $_FILES['ddtg_csv_file'] : null;

        if ( ! $game_name || ! $csv_file || UPLOAD_ERR_OK !== $csv_file['error'] ) {
            // Handle error: Missing fields or file upload error.
            add_action( 'admin_notices', function() {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e( 'Error: Please fill in all required fields and upload a valid CSV file.', 'draglearn' ); ?></p>
                </div>
                <?php
            } );
            return;
        }

        // Insert the new game into the database
        $wpdb->insert(
            $games_table,
            [
                'name'        => $game_name,
                'description' => $game_description,
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
}
