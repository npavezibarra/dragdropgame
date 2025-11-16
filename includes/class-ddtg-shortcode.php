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

        $attempt_id = self::create_new_attempt( $game->game_id, $user_id, count( $events ) );

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

        $events = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    id,
                    event_name AS name,
                    description,
                    event_date AS date,
                    image_url AS image
                FROM {$wpdb->prefix}ddg_events
                WHERE game_id = %d
                ",
                $game->game_id
            ),
            ARRAY_A
        );

        if ( empty( $events ) ) {
            return array();
        }

        shuffle( $events );

        return array_slice( $events, 0, (int) $game->num_events_to_show );
    }

    private static function enqueue_game_scripts( $attempt_id, $events ) {
        wp_enqueue_script(
            'ddtg-timeline-game',
            plugins_url( '../assets/js/timeline-game.js', __FILE__ ),
            array(),
            DRAGLEARN_VERSION,
            true
        );

        wp_add_inline_script(
            'ddtg-timeline-game',
            'const game_events = ' . wp_json_encode( $events ) . ';',
            'before'
        );
    }

    private static function render_game_html( $game, $attempt_id ) {
        ob_start();
        include plugin_dir_path( __FILE__ ) . '../templates/frontend-timeline.php';
        return ob_get_clean();
    }
}
