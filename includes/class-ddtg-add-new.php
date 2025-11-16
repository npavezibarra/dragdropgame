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
            <h1><?php esc_html_e( 'Add New Game', 'draglearndtg' ); ?></h1>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'ddtg_add_new_action', 'ddtg_add_new_nonce' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_game_name"><?php esc_html_e( 'Game Name', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="ddtg_game_name" name="ddtg_game_name" class="regular-text" required />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_game_description"><?php esc_html_e( 'Game Description', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <textarea id="ddtg_game_description" name="ddtg_game_description" class="large-text"></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_number_of_slots"><?php esc_html_e( 'Number of Slots', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="ddtg_number_of_slots" name="ddtg_number_of_slots" class="regular-text" required />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_attempts_number"><?php esc_html_e( 'Attempts Number', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="ddtg_attempts_number" name="ddtg_attempts_number" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_attempts_period"><?php esc_html_e( 'Attempts Period', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <select id="ddtg_attempts_period" name="ddtg_attempts_period">
                                <option value="unlimited"><?php esc_html_e( 'Unlimited', 'draglearndtg' ); ?></option>
                                <option value="daily"><?php esc_html_e( 'Daily', 'draglearndtg' ); ?></option>
                                <option value="weekly"><?php esc_html_e( 'Weekly', 'draglearndtg' ); ?></option>
                                <option value="monthly"><?php esc_html_e( 'Monthly', 'draglearndtg' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_csv_file"><?php esc_html_e( 'CSV File', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <input type="file" id="ddtg_csv_file" name="ddtg_csv_file" accept=".csv" required />
                            <p class="description">
                                <?php esc_html_e( 'Upload a CSV file with two columns: "item_title" and "sort_value".', 'draglearndtg' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Create Game', 'draglearndtg' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle the form submission.
     */
    private static function handle_form_submission() {
        global $wpdb;
        $games_table = $wpdb->prefix . 'ddg_games';
        $items_table = $wpdb->prefix . 'ddg_items';

        $game_name         = isset( $_POST['ddtg_game_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ddtg_game_name'] ) ) : '';
        $game_description  = isset( $_POST['ddtg_game_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ddtg_game_description'] ) ) : '';
        $num_items_to_show = isset( $_POST['ddtg_number_of_slots'] ) ? intval( $_POST['ddtg_number_of_slots'] ) : 0;
        $max_attempts      = isset( $_POST['ddtg_attempts_number'] ) ? intval( $_POST['ddtg_attempts_number'] ) : 0;
        $attempts_period   = isset( $_POST['ddtg_attempts_period'] ) ? sanitize_text_field( wp_unslash( $_POST['ddtg_attempts_period'] ) ) : 'unlimited';
        $csv_file          = isset( $_FILES['ddtg_csv_file'] ) ? $_FILES['ddtg_csv_file'] : null;

        if ( ! $game_name || ! $csv_file || UPLOAD_ERR_OK !== $csv_file['error'] || ! $num_items_to_show ) {
            // Handle error: Missing fields or file upload error.
            add_action( 'admin_notices', function() {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e( 'Error: Please fill in all required fields and upload a valid CSV file.', 'draglearndtg' ); ?></p>
                </div>
                <?php
            } );
            return;
        }

        // Insert the new game into the database
        $wpdb->insert(
            $games_table,
            array(
                'name'              => $game_name,
                'description'       => $game_description,
                'num_items_to_show' => $num_items_to_show,
                'max_attempts'      => $max_attempts,
                'attempts_period'   => $attempts_period,
            )
        );
        $game_id = $wpdb->insert_id;

        // Process the CSV file
        $handle = fopen( $csv_file['tmp_name'], 'r' );
        if ( false !== $handle ) {
            fgetcsv( $handle ); // Skip the header row

            while ( ( $row = fgetcsv( $handle ) ) !== false ) {
                $wpdb->insert(
                    $items_table,
                    array(
                        'game_id'    => $game_id,
                        'item_title' => $row[0],
                        'sort_value' => $row[1],
                    )
                );
            }
            fclose( $handle );
        }

        // Redirect to the "My Games" page
        wp_safe_redirect( admin_url( 'admin.php?page=ddtg-my-games' ) );
        exit;
    }
}
