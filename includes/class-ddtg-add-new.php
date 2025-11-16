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
        $game_id = isset( $_GET['game_id'] ) ? intval( $_GET['game_id'] ) : 0;
        $is_edit = $game_id > 0;
        $game    = null;

        if ( $is_edit ) {
            global $wpdb;
            $games_table = $wpdb->prefix . 'ddg_games';
            $game        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$games_table} WHERE game_id = %d", $game_id ) );
        }
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? esc_html__( 'Edit Game', 'draglearndtg' ) : esc_html__( 'Add New Game', 'draglearndtg' ); ?></h1>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'ddtg_add_new_action', 'ddtg_add_new_nonce' ); ?>
                <?php if ( $is_edit ) : ?>
                    <input type="hidden" name="ddtg_game_id" value="<?php echo esc_attr( $game_id ); ?>" />
                <?php endif; ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_game_name"><?php esc_html_e( 'Game Name', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="ddtg_game_name" name="ddtg_game_name" class="regular-text" value="<?php echo $is_edit ? esc_attr( $game->name ) : ''; ?>" required />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_game_description"><?php esc_html_e( 'Game Description', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <textarea id="ddtg_game_description" name="ddtg_game_description" class="large-text"><?php echo $is_edit ? esc_textarea( $game->description ) : ''; ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_number_of_slots"><?php esc_html_e( 'Number of Items', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="ddtg_number_of_slots" name="ddtg_number_of_slots" class="regular-text" value="<?php echo $is_edit ? esc_attr( $game->num_items_to_show ) : ''; ?>" required />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_attempts_number"><?php esc_html_e( 'Attempts Number', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="ddtg_attempts_number" name="ddtg_attempts_number" class="regular-text" value="<?php echo $is_edit ? esc_attr( $game->max_attempts ) : ''; ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_attempts_period"><?php esc_html_e( 'Attempts Period', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <select id="ddtg_attempts_period" name="ddtg_attempts_period">
                                <option value="unlimited" <?php selected( $is_edit ? $game->attempts_period : '', 'unlimited' ); ?>><?php esc_html_e( 'Unlimited', 'draglearndtg' ); ?></option>
                                <option value="daily" <?php selected( $is_edit ? $game->attempts_period : '', 'daily' ); ?>><?php esc_html_e( 'Daily', 'draglearndtg' ); ?></option>
                                <option value="weekly" <?php selected( $is_edit ? $game->attempts_period : '', 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'draglearndtg' ); ?></option>
                                <option value="monthly" <?php selected( $is_edit ? $game->attempts_period : '', 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'draglearndtg' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_csv_file"><?php esc_html_e( 'CSV File', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <input type="file" id="ddtg_csv_file" name="ddtg_csv_file" accept=".csv" <?php echo $is_edit ? '' : 'required'; ?> />
                            <p class="description">
                                <?php esc_html_e( 'Upload a CSV file with two columns: "item_title" and "sort_value".', 'draglearndtg' ); ?>
                                <?php if ( $is_edit ) : ?>
                                    <br />
                                    <em><?php esc_html_e( 'Leave this field empty to keep the existing items.', 'draglearndtg' ); ?></em>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( $is_edit ? __( 'Update Game', 'draglearndtg' ) : __( 'Create Game', 'draglearndtg' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle the form submission action.
     */
    public static function handle_form_submission_action() {
        if ( isset( $_POST['ddtg_add_new_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddtg_add_new_nonce'] ) ), 'ddtg_add_new_action' ) ) {
            self::process_form_submission();
        }
    }

    /**
     * Process the form submission.
     */
    private static function process_form_submission() {
        global $wpdb;
        $games_table = $wpdb->prefix . 'ddg_games';
        $items_table = $wpdb->prefix . 'ddg_items';

        $game_id           = isset( $_POST['ddtg_game_id'] ) ? intval( $_POST['ddtg_game_id'] ) : 0;
        $is_edit           = $game_id > 0;
        $game_name         = isset( $_POST['ddtg_game_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ddtg_game_name'] ) ) : '';
        $game_description  = isset( $_POST['ddtg_game_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ddtg_game_description'] ) ) : '';
        $num_items_to_show = isset( $_POST['ddtg_number_of_slots'] ) ? intval( $_POST['ddtg_number_of_slots'] ) : 0;
        $attempt_limit     = isset( $_POST['ddtg_attempts_number'] ) ? intval( $_POST['ddtg_attempts_number'] ) : 0;
        $limit_period      = isset( $_POST['ddtg_attempts_period'] ) ? sanitize_text_field( wp_unslash( $_POST['ddtg_attempts_period'] ) ) : 'unlimited';
        $csv_file          = isset( $_FILES['ddtg_csv_file'] ) ? $_FILES['ddtg_csv_file'] : null;

        if ( ! $game_name || ! $num_items_to_show || ( ! $is_edit && ( ! $csv_file || UPLOAD_ERR_OK !== $csv_file['error'] ) ) ) {
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

        $game_data = array(
            'name'              => $game_name,
            'description'       => $game_description,
            'num_items_to_show' => $num_items_to_show,
            'max_attempts'      => $max_attempts,
            'attempts_period'   => $attempts_period,
        );

        if ( $is_edit ) {
            // Update the existing game
            $wpdb->update( $games_table, $game_data, array( 'game_id' => $game_id ) );
        } else {
            // Insert the new game into the database
            $wpdb->insert( $games_table, $game_data );
            $game_id = $wpdb->insert_id;
        }

        // Process the CSV file if it was uploaded
        if ( $csv_file && UPLOAD_ERR_OK === $csv_file['error'] ) {
            if ( $is_edit ) {
                // Delete existing items for this game
                $wpdb->delete( $items_table, array( 'game_id' => $game_id ) );
            }

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
        }

        // Redirect to the "My Games" page
        wp_safe_redirect( admin_url( 'admin.php?page=ddtg-my-games' ) );
        exit;
    }
}
