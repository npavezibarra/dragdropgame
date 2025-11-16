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
        global $wpdb;

        error_log( "DDG_SHORTCODE: Shortcode called" );

        // Sanitize shortcode attributes
        $atts = shortcode_atts(
            array(
                'game' => '',
            ),
            $atts
        );

        error_log( 'DDG_SHORTCODE: Raw atts = ' . print_r( $atts, true ) );

        $slug = sanitize_title( $atts['game'] );
        error_log( "DDG_SHORTCODE: Sanitized slug = {$slug}" );

        if ( ! $slug ) {
            error_log( 'DDG_SHORTCODE ERROR: No slug provided' );
            return '<p>No game specified.</p>';
        }

        //-- Fetch Game -----------------------------------------------------------

        $sql_game = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ddg_games WHERE shortcode_slug = %s",
            $slug
        );
        error_log( "DDG_SHORTCODE: SQL_GAME = {$sql_game}" );

        $game = $wpdb->get_row( $sql_game );

        if ( ! $game ) {
            error_log( "DDG_SHORTCODE ERROR: Game not found for slug '{$slug}'" );
            return '<p>Game not found.</p>';
        }

        error_log( 'DDG_SHORTCODE: GAME FOUND = ' . print_r( $game, true ) );

        //-- Fetch Events ---------------------------------------------------------

        $sql_events = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ddg_events WHERE game_id = %d ORDER BY id ASC",
            $game->game_id
        );
        error_log( "DDG_SHORTCODE: SQL_EVENTS = {$sql_events}" );

        $events = $wpdb->get_results( $sql_events );

        if ( ! $events || count( $events ) === 0 ) {
            error_log( "DDG_SHORTCODE ERROR: No events found for game_id {$game->game_id}" );
            return '<p>No events available yet.</p>';
        }

        error_log( 'DDG_SHORTCODE: EVENTS FOUND = ' . print_r( $events, true ) );

        //-- Prepare JS Array -----------------------------------------------------

        $js_events = array();

        foreach ( $events as $ev ) {
            $js_events[] = array(
                'id'          => intval( $ev->id ),
                'name'        => $ev->event_name,
                'description' => $ev->description,
                'date'        => intval( $ev->event_date ),
                'image'       => $ev->image_url,
            );
        }

        error_log( 'DDG_SHORTCODE: JS_EVENTS = ' . print_r( $js_events, true ) );

        $json_events = wp_json_encode( $js_events );

        //-- Enqueue assets -------------------------------------------------------
        error_log( 'DDG_SHORTCODE: Enqueueing scripts…' );

        wp_enqueue_script( 'ddg-game-script' );
        wp_enqueue_style( 'ddg-game-style' );

        //-- Build HTML -----------------------------------------------------------

        error_log( 'DDG_SHORTCODE: Rendering HTML…' );

        ob_start();
        ?>

        <script>
            console.log("DDG: Injecting game_events");
            const game_events = <?php echo $json_events; ?>;
            console.log("DDG: game_events =", game_events);
        </script>

        <div id="ddg-game-wrapper">
            <header id="top-bar-drop-zone"></header>
            <main id="main-content-area"></main>
        </div>

        <?php
        $html = ob_get_clean();

        error_log( 'DDG_SHORTCODE: Final HTML length = ' . strlen( $html ) );

        return $html;
    }
}
