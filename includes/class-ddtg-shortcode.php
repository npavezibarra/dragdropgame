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
        $slug = isset( $atts['game'] ) ? sanitize_title( $atts['game'] ) : '';
        if ( ! $slug ) {
            return '<p>No game specified.</p>';
        }

        global $wpdb;

        $game = $wpdb->get_row(
            $wpdb->prepare(
                "
        SELECT *
        FROM {$wpdb->prefix}ddg_games
        WHERE shortcode_slug = %s
        LIMIT 1
    ",
                $slug
            )
        );

        if ( ! $game ) {
            return '<p>Game not found.</p>';
        }

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

        shuffle( $events );
        $events = array_slice( $events, 0, intval( $game->num_events_to_show ) );
        $events = array_map(
            static function ( $event ) {
                return array(
                    'id'          => isset( $event['id'] ) ? (int) $event['id'] : 0,
                    'name'        => isset( $event['name'] ) ? (string) $event['name'] : '',
                    'description' => isset( $event['description'] ) ? (string) $event['description'] : '',
                    'date'        => isset( $event['date'] ) ? $event['date'] : '',
                    'image'       => isset( $event['image'] ) ? (string) $event['image'] : '',
                );
            },
            $events
        );

        wp_enqueue_script(
            'ddtg-tailwind',
            'https://cdn.tailwindcss.com',
            array(),
            null,
            false
        );

        wp_add_inline_script(
            'ddtg-tailwind',
            'tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ["Inter", "sans-serif"],
                },
            },
        },
    };'
        );

        wp_enqueue_script(
            'ddtg-timeline-game',
            plugins_url( '../assets/js/timeline-game.js', __FILE__ ),
            array(),
            DRAGLEARN_VERSION,
            true
        );

        wp_add_inline_script(
            'ddtg-timeline-game',
            'const game_events = ' . json_encode( array_values( $events ) ) . ';',
            'before'
        );

        ob_start();
        include plugin_dir_path( __FILE__ ) . '../templates/frontend-timeline.php';
        return ob_get_clean();
    }
}
