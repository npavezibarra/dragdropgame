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
            $game        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$games_table} WHERE id = %d", $game_id ) );
        }

        $current_game_name    = $game ? $game->game_name : '';
        $current_slug         = $game ? $game->shortcode_slug : '';
        $current_events_limit = $game ? (int) $game->num_events_to_show : '';
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
                            <input type="text" id="ddtg_game_name" name="ddtg_game_name" class="regular-text" value="<?php echo esc_attr( $current_game_name ); ?>" required />
                            <input type="hidden" id="ddtg_shortcode_slug" name="ddtg_shortcode_slug" value="<?php echo esc_attr( $current_slug ); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ddtg_csv_file"><?php esc_html_e( 'CSV File', 'draglearndtg' ); ?></label>
                        </th>
                        <td>
                            <input type="file" id="ddtg_csv_file" name="ddtg_csv_file" accept=".csv" <?php echo $is_edit ? '' : 'required'; ?> />
                            <p class="description">
                                <?php esc_html_e( 'Upload a CSV file with a header row that includes event_name and event_date columns. Optional columns: description, image_url.', 'draglearndtg' ); ?>
                                <?php if ( $is_edit ) : ?>
                                    <br />
                                    <em><?php esc_html_e( 'Upload a new CSV to replace the existing events.', 'draglearndtg' ); ?></em>
                                <?php endif; ?>
                            </p>
                            <div id="ddtg_csv_preview" style="display: none; margin-top: 1em;"></div>
                        </td>
                    </tr>
                    <tr id="ddtg_additional_fields" valign="top" style="display: none;">
                        <th scope="row"><?php esc_html_e( 'Game Settings', 'draglearndtg' ); ?></th>
                        <td>
                            <p class="description" style="margin-top: 0;">
                                <?php esc_html_e( 'These options unlock after a successful CSV upload.', 'draglearndtg' ); ?>
                            </p>
                            <label for="ddtg_attempt_limit" style="display: block; margin-top: 0.5em;">
                                <?php esc_html_e( 'Attempt Limit', 'draglearndtg' ); ?>
                            </label>
                            <input type="number" id="ddtg_attempt_limit" name="ddtg_attempt_limit" class="regular-text" min="0" step="1" />
                            <p class="description"><?php esc_html_e( 'Maximum number of times a player can attempt this game (0 for unlimited).', 'draglearndtg' ); ?></p>

                            <label for="ddtg_attempt_period" style="display: block; margin-top: 1em;">
                                <?php esc_html_e( 'Attempt Period (days)', 'draglearndtg' ); ?>
                            </label>
                            <input type="number" id="ddtg_attempt_period" name="ddtg_attempt_period" class="regular-text" min="0" step="1" />
                            <p class="description"><?php esc_html_e( 'How long the attempt limit applies before resetting (0 keeps all attempts).', 'draglearndtg' ); ?></p>

                            <label for="ddtg_number_of_events" style="display: block; margin-top: 1em;">
                                <?php esc_html_e( 'Events to Display', 'draglearndtg' ); ?>
                            </label>
                            <input type="number" id="ddtg_number_of_events" name="ddtg_number_of_events" class="regular-text" min="1" value="<?php echo esc_attr( $current_events_limit ); ?>" required />
                            <p class="description"><?php esc_html_e( 'Controls how many events will be randomly selected per play session.', 'draglearndtg' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( $is_edit ? __( 'Update Game', 'draglearndtg' ) : __( 'Create Game', 'draglearndtg' ) ); ?>
            </form>
        </div>
        <script>
            (function() {
                const nameInput = document.getElementById('ddtg_game_name');
                const slugInput = document.getElementById('ddtg_shortcode_slug');
                const csvInput = document.getElementById('ddtg_csv_file');
                const additionalFields = document.getElementById('ddtg_additional_fields');
                const previewContainer = document.getElementById('ddtg_csv_preview');
                const nonceField = document.getElementById('ddtg_add_new_nonce');
                const attemptLimitInput = document.getElementById('ddtg_attempt_limit');
                const attemptPeriodInput = document.getElementById('ddtg_attempt_period');
                const eventsInput = document.getElementById('ddtg_number_of_events');
                const editGameUrl = '<?php echo $is_edit ? esc_url( admin_url( 'admin.php?page=ddtg-add-new&game_id=' . $game_id ) ) : ''; ?>';
                let latestEventCount = 0;

                const setFieldAvailability = (enabled) => {
                    [attemptLimitInput, attemptPeriodInput, eventsInput]
                        .filter(Boolean)
                        .forEach((input) => {
                            input.disabled = !enabled;
                            if (!enabled) {
                                input.value = input.defaultValue;
                            }
                        });
                };

                const resetPreview = () => {
                    if (previewContainer) {
                        previewContainer.innerHTML = '';
                        previewContainer.style.display = 'none';
                    }

                    if (additionalFields) {
                        additionalFields.style.display = 'none';
                    }

                    setFieldAvailability(false);
                };

                if (!nameInput || !slugInput) {
                    return;
                }

                const slugify = (value) => {
                    return value
                        .toString()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '')
                        .replace(/-{2,}/g, '-');
                };

                const updateSlug = () => {
                    slugInput.value = slugify(nameInput.value);
                };

                if (!slugInput.value) {
                    updateSlug();
                }

                nameInput.addEventListener('input', updateSlug);
                setFieldAvailability(false);

                if (!csvInput || !previewContainer || !nonceField) {
                    return;
                }

                const appendEditButton = () => {
                    if (!previewContainer) {
                        return;
                    }

                    const existingButton = document.getElementById('edit-game-btn');
                    if (existingButton) {
                        existingButton.remove();
                    }

                    const editBtn = document.createElement('button');
                    editBtn.id = 'edit-game-btn';
                    editBtn.className = 'button button-secondary';
                    editBtn.type = 'button';
                    editBtn.textContent = '<?php echo esc_js( __( 'Edit', 'draglearndtg' ) ); ?>';
                    editBtn.addEventListener('click', () => {
                        if (editGameUrl) {
                            window.location.href = editGameUrl;
                            return;
                        }

                        const form = document.querySelector('form');
                        if (form) {
                            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });

                    previewContainer.appendChild(editBtn);
                };

                const showPreview = (html, shouldShowEditButton = false) => {
                    previewContainer.innerHTML = html;
                    previewContainer.style.display = 'block';

                    if (additionalFields) {
                        additionalFields.style.display = '';
                    }

                    if (shouldShowEditButton) {
                        appendEditButton();
                    }

                    setFieldAvailability(true);
                };

                const renderError = (message) => {
                    latestEventCount = 0;
                    showPreview(`<div class="notice notice-error" style="padding: 10px;">${message}</div>`);
                };

                csvInput.addEventListener('change', () => {
                    resetPreview();

                    if (!csvInput.files || !csvInput.files.length) {
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'ddtg_preview_csv');
                    formData.append('nonce', nonceField.value);
                    formData.append('ddtg_csv_file', csvInput.files[0]);

                    previewContainer.innerHTML = '<?php echo esc_js( __( 'Processing CSV preview...', 'draglearndtg' ) ); ?>';
                    previewContainer.style.display = 'block';

                    const ajaxUrl = typeof ajaxurl !== 'undefined' ? ajaxurl : (window.location.origin + '/wp-admin/admin-ajax.php');

                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            if (!data || !data.success) {
                                renderError(data && data.data && data.data.message ? data.data.message : '<?php echo esc_js( __( 'Unable to process the CSV file.', 'draglearndtg' ) ); ?>');
                                return;
                            }

                            latestEventCount = data.data.eventCount ? parseInt(data.data.eventCount, 10) : 0;

                            if (eventsInput && latestEventCount > 0) {
                                eventsInput.max = latestEventCount;

                                if (Number(eventsInput.value) > latestEventCount) {
                                    eventsInput.value = latestEventCount;
                                }
                            }

                            showPreview(data.data.html, latestEventCount > 0);
                        })
                        .catch(() => {
                            renderError('<?php echo esc_js( __( 'Unexpected error while previewing the CSV.', 'draglearndtg' ) ); ?>');
                        });
                });
            })();
        </script>
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
        $games_table  = $wpdb->prefix . 'ddg_games';
        $events_table = $wpdb->prefix . 'ddg_events';

        $game_id = isset( $_POST['ddtg_game_id'] ) ? intval( $_POST['ddtg_game_id'] ) : 0;
        $is_edit = $game_id > 0;

        $game_data = self::get_submitted_game_data();
        $csv_file  = isset( $_FILES['ddtg_csv_file'] ) ? $_FILES['ddtg_csv_file'] : null;

        if ( ! self::validate_game_submission( $game_data, $is_edit, $csv_file ) ) {
            return;
        }

        $slug_conflict = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$games_table} WHERE shortcode_slug = %s AND id != %d",
                $game_data['shortcode_slug'],
                $game_id
            )
        );

        if ( $slug_conflict ) {
            self::add_admin_error_notice( __( 'That shortcode slug is already in use. Please choose another value.', 'draglearndtg' ) );
            return;
        }

        $game_data['num_events_to_show'] = absint( $game_data['num_events_to_show'] );
        $csv_events                      = null;
        $event_count                    = 0;

        if ( self::is_valid_csv_payload( $csv_file ) ) {
            $csv_events = self::extract_csv_events( $csv_file );

            if ( is_wp_error( $csv_events ) ) {
                self::add_admin_error_notice( $csv_events->get_error_message() );
                return;
            }

            $event_count = count( $csv_events );
        } elseif ( $is_edit ) {
            $event_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$events_table} WHERE game_id = %d", $game_id ) );
        }

        if ( $game_data['num_events_to_show'] > $event_count && $event_count >= 0 ) {
            if ( 0 === $event_count ) {
                self::add_admin_error_notice( __( 'Error: No events are available. Please upload a CSV before setting Events to Display.', 'draglearndtg' ) );
            } else {
                self::add_admin_error_notice( sprintf( __( 'Error: Events to Display cannot exceed the %d events in your CSV.', 'draglearndtg' ), $event_count ) );
            }
            return;
        }

        $game_record = array(
            'game_name'          => $game_data['game_name'],
            'shortcode_slug'     => $game_data['shortcode_slug'],
            'num_events_to_show' => $game_data['num_events_to_show'],
        );

        if ( $is_edit ) {
            $updated = $wpdb->update( $games_table, $game_record, array( 'id' => $game_id ) );

            if ( false === $updated ) {
                self::add_admin_error_notice( __( 'Unable to update the game. Please try again.', 'draglearndtg' ) );
                return;
            }
        } else {
            $game_record['user_id'] = get_current_user_id();
            $inserted               = $wpdb->insert( $games_table, $game_record );

            if ( ! $inserted ) {
                self::add_admin_error_notice( __( 'Unable to create the game. Please try again.', 'draglearndtg' ) );
                return;
            }

            $game_id = (int) $wpdb->insert_id;
        }

        if ( self::is_valid_csv_payload( $csv_file ) ) {
            if ( ! self::import_events_from_csv( $events_table, $game_id, $csv_file, $csv_events ) ) {
                return;
            }
        } elseif ( ! $is_edit ) {
            self::add_admin_error_notice( __( 'Error: Please upload a valid CSV file.', 'draglearndtg' ) );
            return;
        }

        wp_safe_redirect( admin_url( 'admin.php?page=ddtg-my-games' ) );
        exit;
    }

    /**
     * AJAX: Validate and preview the uploaded CSV before showing additional options.
     */
    public static function handle_csv_preview() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'draglearndtg' ) ) );
        }

        check_ajax_referer( 'ddtg_add_new_action', 'nonce' );

        $csv_file = isset( $_FILES['ddtg_csv_file'] ) ? $_FILES['ddtg_csv_file'] : null;

        if ( ! self::is_valid_csv_payload( $csv_file ) ) {
            wp_send_json_error( array( 'message' => __( 'Please upload a valid CSV file.', 'draglearndtg' ) ) );
        }

        $csv_events = self::extract_csv_events( $csv_file );

        if ( is_wp_error( $csv_events ) ) {
            wp_send_json_error( array( 'message' => $csv_events->get_error_message() ) );
        }

        $preview_html = self::build_csv_preview_table( $csv_events );

        if ( is_wp_error( $preview_html ) ) {
            wp_send_json_error( array( 'message' => $preview_html->get_error_message() ) );
        }

        wp_send_json_success(
            array(
                'html'       => $preview_html,
                'eventCount' => count( $csv_events ),
            )
        );
    }

    /**
     * Extract sanitized events from a CSV payload.
     *
     * @param array $csv_events Uploaded CSV payload.
     *
     * @return array|WP_Error
     */
    private static function extract_csv_events( $csv_file ) {
        $handle = fopen( $csv_file['tmp_name'], 'rb' );
        if ( false === $handle ) {
            return new WP_Error( 'ddtg_csv_unreadable', __( 'Unable to read the uploaded CSV file.', 'draglearndtg' ) );
        }

        $header_row = fgetcsv( $handle );
        if ( empty( $header_row ) ) {
            fclose( $handle );
            return new WP_Error( 'ddtg_csv_header', __( 'The CSV file must include a header row.', 'draglearndtg' ) );
        }

        if ( count( $header_row ) < 3 ) {
            fclose( $handle );
            return new WP_Error( 'ddtg_csv_columns', __( 'CSV files must include at least three columns.', 'draglearndtg' ) );
        }

        if ( ! self::is_valid_encoding( $header_row ) ) {
            fclose( $handle );
            return new WP_Error( 'ddtg_csv_header_encoding', __( 'Unable to read the CSV header because of an encoding issue.', 'draglearndtg' ) );
        }

        $header_row        = array_map( 'sanitize_key', $header_row );
        $available_columns = array_flip( $header_row );
        $required_columns  = array( 'event_name', 'description', 'event_date' );
        $missing_columns   = array_diff( $required_columns, array_keys( $available_columns ) );

        if ( ! empty( $missing_columns ) ) {
            fclose( $handle );
            return new WP_Error( 'ddtg_csv_missing_columns', sprintf( __( 'Missing required CSV columns: %s', 'draglearndtg' ), implode( ', ', $missing_columns ) ) );
        }

        $events_to_insert = array();
        $line_number      = 1; // Account for header already read.

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            ++$line_number;

            if ( count( $row ) < count( $header_row ) ) {
                fclose( $handle );
                return new WP_Error( 'ddtg_csv_short_row', sprintf( __( 'Row %d has fewer columns than the header.', 'draglearndtg' ), $line_number ) );
            }

            if ( ! self::is_valid_encoding( $row ) ) {
                fclose( $handle );
                return new WP_Error( 'ddtg_csv_row_encoding', __( 'Unable to read one of the CSV rows because of encoding.', 'draglearndtg' ) );
            }

            $event_name  = self::sanitize_csv_value( $row[ $available_columns['event_name'] ], 'text' );
            $description = self::sanitize_csv_value( $row[ $available_columns['description'] ], 'textarea' );
            $event_date  = self::sanitize_csv_value( $row[ $available_columns['event_date'] ], 'text' );

            if ( is_wp_error( $event_name ) || is_wp_error( $description ) || is_wp_error( $event_date ) ) {
                fclose( $handle );
                return new WP_Error( 'ddtg_csv_disallowed_markup', __( 'CSV rows cannot include HTML tags or scripts.', 'draglearndtg' ) );
            }

            if ( '' === $event_name || '' === $event_date || '' === $description ) {
                fclose( $handle );
                return new WP_Error( 'ddtg_csv_missing_data', sprintf( __( 'Missing required data at line %d.', 'draglearndtg' ), $line_number ) );
            }

            $image_url = '';
            if ( isset( $available_columns['image_url'] ) && isset( $row[ $available_columns['image_url'] ] ) ) {
                $image_url = self::sanitize_csv_value( $row[ $available_columns['image_url'] ], 'url' );

                if ( is_wp_error( $image_url ) ) {
                    fclose( $handle );
                    return new WP_Error( 'ddtg_csv_image_markup', __( 'Image URLs cannot include scripts or HTML tags.', 'draglearndtg' ) );
                }
            }

            $events_to_insert[] = array(
                'event_name'  => $event_name,
                'event_date'  => $event_date,
                'description' => $description,
                'image_url'   => $image_url,
            );
        }

        fclose( $handle );

        if ( empty( $events_to_insert ) ) {
            return new WP_Error( 'ddtg_csv_empty', __( 'No events were imported. Please verify the CSV contents.', 'draglearndtg' ) );
        }

        return $events_to_insert;
    }

    /**
     * Import events from the uploaded CSV file.
     *
     * @param string $events_table Table name for events.
     * @param int    $game_id      Game ID.
     * @param array  $csv_file     Uploaded CSV data.
     * @param array  $parsed_events Optional pre-parsed CSV data.
     *
     * @return bool
     */
    private static function import_events_from_csv( $events_table, $game_id, $csv_file, $parsed_events = null ) {
        global $wpdb;

        $events_to_insert = is_array( $parsed_events ) ? $parsed_events : self::extract_csv_events( $csv_file );

        if ( is_wp_error( $events_to_insert ) ) {
            self::add_admin_error_notice( $events_to_insert->get_error_message() );
            return false;
        }

        // Replace existing events for this game.
        $wpdb->delete( $events_table, array( 'game_id' => $game_id ) );

        $chunks = array_chunk( $events_to_insert, 100 );
        foreach ( $chunks as $chunk ) {
            $placeholders = array();
            $values       = array();

            foreach ( $chunk as $event ) {
                $placeholders[] = '( %d, %s, %s, %s, %s )';
                $values[]       = $game_id;
                $values[]       = $event['event_name'];
                $values[]       = $event['event_date'];
                $values[]       = $event['description'];
                $values[]       = $event['image_url'];
            }

            $query     = 'INSERT INTO ' . $events_table . ' (game_id, event_name, event_date, description, image_url) VALUES ' . implode( ', ', $placeholders );
            $prepared  = $wpdb->prepare( $query, $values );
            $wpdb->query( $prepared );
        }

        return true;
    }

    /**
     * Build a preview table from the uploaded CSV file without persisting the data.
     *
     * @param array $csv_file Uploaded CSV payload.
     *
     * @return string|WP_Error
     */
    private static function build_csv_preview_table( $csv_events ) {
        if ( empty( $csv_events ) ) {
            return new WP_Error( 'ddtg_preview_empty', __( 'No data rows were found in the CSV file.', 'draglearndtg' ) );
        }

        ob_start();
        ?>
        <h3><?php esc_html_e( 'CSV Preview', 'draglearndtg' ); ?></h3>
        <table class="widefat striped" style="max-width: 720px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Event Name', 'draglearndtg' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'draglearndtg' ); ?></th>
                    <th><?php esc_html_e( 'Event Date', 'draglearndtg' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $csv_events as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row['event_name'] ); ?></td>
                        <td><?php echo esc_html( $row['description'] ); ?></td>
                        <td><?php echo esc_html( $row['event_date'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php

        return ob_get_clean();
    }

    /**
     * Helper to display an admin error notice.
     *
     * @param string $message Message to display.
     */
    private static function add_admin_error_notice( $message ) {
        add_action(
            'admin_notices',
            function() use ( $message ) {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html( $message ); ?></p>
                </div>
                <?php
            }
        );
    }

    /**
     * Retrieve sanitized game data from the request.
     *
     * @return array
     */
    private static function get_submitted_game_data() {
        $game_name        = isset( $_POST['ddtg_game_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ddtg_game_name'] ) ) : '';
        $shortcode_slug   = isset( $_POST['ddtg_shortcode_slug'] ) ? sanitize_title( wp_unslash( $_POST['ddtg_shortcode_slug'] ) ) : '';
        $num_events       = isset( $_POST['ddtg_number_of_events'] ) ? absint( $_POST['ddtg_number_of_events'] ) : 0;

        if ( empty( $shortcode_slug ) && ! empty( $game_name ) ) {
            $shortcode_slug = sanitize_title( $game_name );
        }

        return array(
            'game_name'          => $game_name,
            'shortcode_slug'     => $shortcode_slug,
            'num_events_to_show' => $num_events,
        );
    }

    /**
     * Validate that the submitted form data is complete.
     *
     * @param array $game_data Sanitized game data.
     * @param bool  $is_edit   Whether this is an edit operation.
     * @param array $csv_file  Uploaded file data.
     *
     * @return bool
     */
    private static function validate_game_submission( $game_data, $is_edit, $csv_file ) {
        if ( empty( $game_data['game_name'] ) ) {
            self::add_admin_error_notice( __( 'Error: Please provide a Game Name so we can generate a shortcode.', 'draglearndtg' ) );
            return false;
        }

        if ( empty( $game_data['shortcode_slug'] ) ) {
            self::add_admin_error_notice( __( 'Error: Unable to generate a shortcode slug. Please adjust the Game Name.', 'draglearndtg' ) );
            return false;
        }

        if ( empty( $game_data['num_events_to_show'] ) ) {
            self::add_admin_error_notice( __( 'Error: Please provide how many events should be displayed per game.', 'draglearndtg' ) );
            return false;
        }

        if ( ! $is_edit && ! self::is_valid_csv_payload( $csv_file ) ) {
            self::add_admin_error_notice( __( 'Error: Please upload a CSV file before creating a new game.', 'draglearndtg' ) );
            return false;
        }

        if ( self::has_file_to_process( $csv_file ) && ! self::passes_csv_file_checks( $csv_file ) ) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether a CSV payload is available and valid.
     *
     * @param array|null $csv_file Uploaded file payload.
     *
     * @return bool
     */
    private static function is_valid_csv_payload( $csv_file ) {
        return self::has_file_to_process( $csv_file ) && self::passes_csv_file_checks( $csv_file );
    }

    /**
     * Check if the upload array contains a file that needs to be processed.
     *
     * @param array|null $csv_file Uploaded file payload.
     *
     * @return bool
     */
    private static function has_file_to_process( $csv_file ) {
        return is_array( $csv_file ) && isset( $csv_file['error'], $csv_file['tmp_name'] ) && UPLOAD_ERR_OK === (int) $csv_file['error'] && ! empty( $csv_file['tmp_name'] );
    }

    /**
     * Validate that the uploaded file is a CSV.
     *
     * @param array $csv_file Uploaded file payload.
     *
     * @return bool
     */
    private static function passes_csv_file_checks( $csv_file ) {
        if ( ! is_array( $csv_file ) || ! isset( $csv_file['error'] ) ) {
            self::add_admin_error_notice( __( 'Error: No file was uploaded.', 'draglearndtg' ) );
            return false;
        }

        if ( UPLOAD_ERR_NO_FILE === (int) $csv_file['error'] || empty( $csv_file['tmp_name'] ) ) {
            self::add_admin_error_notice( __( 'Error: CSV upload missing. Please choose a file.', 'draglearndtg' ) );
            return false;
        }

        if ( UPLOAD_ERR_OK !== (int) $csv_file['error'] ) {
            self::add_admin_error_notice( __( 'Error: The upload failed. Please try again.', 'draglearndtg' ) );
            return false;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $checked_file = wp_check_filetype_and_ext( $csv_file['tmp_name'], $csv_file['name'], array( 'csv' => 'text/csv' ) );

        if ( empty( $checked_file['ext'] ) || 'csv' !== $checked_file['ext'] ) {
            self::add_admin_error_notice( __( 'Error: Only CSV files are supported.', 'draglearndtg' ) );
            return false;
        }

        return true;
    }

    /**
     * Determine whether the provided CSV values use UTF-8 encoding.
     *
     * @param array $row Row data to inspect.
     *
     * @return bool
     */
    private static function is_valid_encoding( $row ) {
        if ( ! function_exists( 'mb_detect_encoding' ) ) {
            return true;
        }

        $row_string = implode( '', (array) $row );
        return false !== mb_detect_encoding( $row_string, 'UTF-8', true );
    }

    /**
     * Sanitize CSV values and block markup/scripts.
     *
     * @param string $value   Raw value.
     * @param string $context Context of the value (text, textarea, url).
     *
     * @return string|WP_Error
     */
private static function sanitize_csv_value( $value, $context = 'text' ) {
        $value = trim( (string) $value );

        if ( '' === $value ) {
            return '';
        }

        if ( $value !== wp_strip_all_tags( $value ) ) {
            return new WP_Error( 'ddtg_disallowed_markup' );
        }

        switch ( $context ) {
            case 'textarea':
                return sanitize_textarea_field( $value );
            case 'url':
                return esc_url_raw( $value );
            case 'text':
            default:
                return sanitize_text_field( $value );
        }
    }
}

add_action( 'wp_ajax_ddtg_preview_csv', array( 'DDTG_Add_New', 'handle_csv_preview' ) );
