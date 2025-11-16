<?php
/**
 * Shortcode handler for the DragLearn game.
 *
 * @package DragLearn
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * DDTG_Shortcode Class.
 */
class DDTG_Shortcode {

    /**
     * Initialize the shortcode.
     */
    public static function init() {
        add_shortcode( 'dragdropgame', array( __CLASS__, 'render_game' ) );
        add_shortcode( 'draglearn_game', array( __CLASS__, 'render_legacy_game' ) );
    }

    /**
     * Legacy shortcode handler for backward compatibility.
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     * @param string $tag     Shortcode tag.
     * @return string
     */
    public static function render_legacy_game( $atts, $content = null, $tag = 'draglearn_game' ) {
        _doing_it_wrong(
            'draglearn_game',
            esc_html__( 'The [draglearn_game] shortcode is deprecated. Please use [dragdropgame] instead.', 'draglearndtg' ),
            '1.6.0'
        );

        return self::render_game( $atts, $content, $tag );
    }

    /**
     * Render the game.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function render_game( $atts, $content = null, $tag = 'dragdropgame' ) {
        $atts = shortcode_atts(
            array(
                'game' => '',
            ),
            $atts,
            $tag
        );

        $shortcode_slug = sanitize_title( $atts['game'] );

        if ( empty( $shortcode_slug ) ) {
            return '<p>' . esc_html__( 'A valid game slug is required.', 'draglearndtg' ) . '</p>';
        }

        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You must be logged in to play this game.', 'draglearndtg' ) . '</p>';
        }

        global $wpdb;
        $games_table = $wpdb->prefix . 'ddg_games';
        $game        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$games_table} WHERE shortcode_slug = %s", $shortcode_slug ) );

        if ( ! $game ) {
            return '<p>' . esc_html__( 'Game not found.', 'draglearndtg' ) . '</p>';
        }

        $user_id = get_current_user_id();

        $events = self::get_events_for_game( $game );

        if ( empty( $events ) ) {
            return '<p>' . esc_html__( 'No events found for this game.', 'draglearndtg' ) . '</p>';
        }

        $attempt_id = self::create_new_attempt( $game->id, $user_id, count( $events ) );

        self::enqueue_game_scripts( $attempt_id, $events );

        return self::render_game_html( $game, $attempt_id );
    }

    private static function create_new_attempt( $game_id, $user_id, $total_events ) {
        global $wpdb;
        $attempts_table = $wpdb->prefix . 'ddg_attempts';

        $wpdb->insert(
            $attempts_table,
            array(
                'user_id'    => $user_id,
                'game_id'    => $game_id,
                'total'      => $total_events,
                'start_time' => current_time( 'mysql', 1 ),
            )
        );

        return $wpdb->insert_id;
    }

    private static function get_events_for_game( $game ) {
        global $wpdb;
        $events_table = $wpdb->prefix . 'ddg_events';

        $limit = max( 1, (int) $game->num_events_to_show );

        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_name, event_date, description, image_url FROM {$events_table} WHERE game_id = %d",
                $game->id
            )
        );

        if ( empty( $events ) ) {
            return array();
        }

        shuffle( $events );

        return array_slice( $events, 0, $limit );
    }

    private static function enqueue_game_scripts( $attempt_id, $events ) {
        wp_enqueue_style( 'draglearn-game-style', plugins_url( '../assets/css/draglearn-game.css', __FILE__ ) );
        wp_enqueue_script( 'draglearn-game-script', plugins_url( '../assets/js/draglearn-game.js', __FILE__ ), array( 'jquery' ), DRAGLEARN_VERSION, true );

        $localized_events = array_map(
            static function ( $event ) {
                return array(
                    'event_name'  => sanitize_text_field( $event->event_name ),
                    'event_date'  => sanitize_text_field( $event->event_date ),
                    'description' => sanitize_textarea_field( $event->description ),
                    'image_url'   => esc_url_raw( $event->image_url ),
                );
            },
            $events
        );

        wp_localize_script(
            'draglearn-game-script',
            'ddtg_game_data',
            array(
                'ajax_url'   => admin_url( 'admin-ajax.php' ),
                'nonce'      => wp_create_nonce( 'ddtg_game_nonce' ),
                'attempt_id' => $attempt_id,
            )
        );

        wp_add_inline_script(
            'draglearn-game-script',
            'window.dragdropgame_events = ' . wp_json_encode( $localized_events ) . ';',
            'before'
        );
    }

    private static function render_game_html( $game, $attempt_id ) {
        ob_start();
        ?>
        <div id="draglearn-game" data-attempt-id="<?php echo esc_attr( $attempt_id ); ?>">
            <h2><?php echo esc_html( $game->game_name ); ?></h2>
            <p><?php esc_html_e( 'Match each event with the correct date to complete the timeline.', 'draglearndtg' ); ?></p>
            <div class="drag-container">
                <div id="lessons-pool">
                    <h3><?php esc_html_e( 'Events', 'draglearndtg' ); ?></h3>
                    <div id="ddtg-events"></div>
                </div>
                <div id="courses-zones">
                    <h3><?php esc_html_e( 'Dates', 'draglearndtg' ); ?></h3>
                    <div id="ddtg-dates"></div>
                </div>
            </div>
            <button id="finish-game"><?php esc_html_e( 'Finish', 'draglearndtg' ); ?></button>
            <p id="feedback" class="feedback"></p>
        </div>
        <?php
        return ob_get_clean();
    }
}
