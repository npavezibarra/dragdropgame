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

        $atts = shortcode_atts(
            array(
                'game' => '',
            ),
            $atts
        );

        $slug = sanitize_title( $atts['game'] );
        if ( ! $slug ) {
            return "<p>No game specified.</p>";
        }

        // Fetch game
        $game = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ddg_games WHERE shortcode_slug = %s", $slug )
        );

        if ( ! $game ) {
            return "<p>Game not found.</p>";
        }

        // Fetch events
        $events = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ddg_events WHERE game_id = %d ORDER BY id ASC", $game->game_id )
        );

        if ( ! $events || count( $events ) === 0 ) {
            return "<p>No events available yet.</p>";
        }

        // Prepare data for JS
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

        // Inject JSON for JavaScript
        $json_events = wp_json_encode( $js_events );

        // Enqueue the new assets
        wp_enqueue_script( 'ddg-game-script' );
        wp_enqueue_style( 'ddg-game-style' );

        ob_start();
        ?>

        <script>
            // Make the events available for the new game engine
            const game_events = <?php echo $json_events; ?>;
        </script>

        <!-- NEW GAME UI HTML SKELETON -->
        <div id="ddg-game-wrapper">

            <!-- Top bar: Drop-zone timeline -->
            <header id="top-bar-drop-zone" class="h-[100px] flex items-center justify-center p-3 bg-white shadow-xl z-30">
                <div class="flex space-x-2 w-full max-w-6xl mx-auto items-center">
                    <div id="drop-zone-items" class="flex items-center justify-center space-x-2 w-full"></div>
                    <button id="finish-btn" class="bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition duration-200 shadow-lg flex-shrink-0 text-lg font-semibold whitespace-nowrap">Finish</button>
                </div>
            </header>

            <!-- Main carousel -->
            <main id="main-content-area" class="flex-1 flex flex-col items-center justify-center p-8 relative">
                <h2 class="text-2xl font-bold text-gray-700 mb-6 hidden md:block">
                    Order the events chronologically (Earliest in Slot 1)
                </h2>

                <div id="carousel" class="relative w-full max-w-4xl h-full flex items-center justify-center">
                    <div id="slides-wrapper" class="relative w-full h-[80%] md:h-[90%]"></div>

                    <!-- Navigation -->
                    <button onclick="navigateSlide(-1)" id="prev-btn" class="absolute left-0 p-3 bg-white/70 backdrop-blur-sm rounded-full shadow-lg hover:bg-white transition duration-200 z-40">
                        ←
                    </button>
                    <button onclick="navigateSlide(1)" id="next-btn" class="absolute right-0 p-3 bg-white/70 backdrop-blur-sm rounded-full shadow-lg hover:bg-white transition duration-200 z-40">
                        →
                    </button>
                </div>
            </main>

            <!-- Modal -->
            <div id="message-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-[100]">
                <div class="bg-white p-6 rounded-xl shadow-2xl max-w-lg w-full text-center">
                    <h3 id="modal-title" class="text-xl font-bold mb-3">Result</h3>
                    <p id="modal-message" class="text-gray-700 mb-4"></p>
                    <button onclick="closeModal()" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200">Close</button>
                </div>
            </div>

        </div>

        <?php
        return ob_get_clean();
    }
}
