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
        add_shortcode( 'draglearn_game', array( __CLASS__, 'render_game' ) );
    }

    /**
     * Render the game.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function render_game( $atts ) {
        $atts = shortcode_atts( array(
            'game' => '',
        ), $atts, 'draglearn_game' );

        $shortcode_slug = sanitize_text_field( $atts['game'] );

        if ( empty( $shortcode_slug ) ) {
            return '<p>' . esc_html__( 'Game slug is missing.', 'draglearndtg' ) . '</p>';
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

        self::enqueue_game_scripts( $attempt_id );

        return self::render_game_html( $game, $attempt_id, $events );
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

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_name, event_date, description, image_url FROM {$events_table} WHERE game_id = %d ORDER BY RAND() LIMIT %d",
                $game->id,
                $limit
            )
        );
    }

    private static function enqueue_game_scripts( $attempt_id ) {
        wp_enqueue_style( 'draglearn-game-style', plugins_url( '../assets/css/draglearn-game.css', __FILE__ ) );
        wp_enqueue_script( 'draglearn-game-script', plugins_url( '../assets/js/draglearn-game.js', __FILE__ ), array( 'jquery' ), DRAGLEARN_VERSION, true );
        wp_localize_script(
            'draglearn-game-script',
            'ddtg_game_data',
            array(
                'ajax_url'   => admin_url( 'admin-ajax.php' ),
                'nonce'      => wp_create_nonce( 'ddtg_game_nonce' ),
                'attempt_id' => $attempt_id,
            )
        );
    }

    private static function render_game_html( $game, $attempt_id, $events ) {
        $completions = wp_list_pluck( $events, 'event_date' );
        shuffle( $completions );
        shuffle( $events );

        ob_start();
        ?>
        <div id="draglearn-game" data-attempt-id="<?php echo esc_attr( $attempt_id ); ?>">
            <h2><?php echo esc_html( $game->game_name ); ?></h2>
            <p><?php esc_html_e( 'Match each event with the correct date to complete the timeline.', 'draglearndtg' ); ?></p>
            <div class="drag-container">
                <div id="lessons-pool">
                    <h3><?php esc_html_e( 'Events', 'draglearndtg' ); ?></h3>
                    <?php foreach ( $events as $event ) : ?>
                        <div class="draggable" draggable="true" data-course="<?php echo esc_attr( $event->event_date ); ?>">
                            <strong><?php echo esc_html( $event->event_name ); ?></strong>
                            <?php if ( ! empty( $event->description ) ) : ?>
                                <p class="event-description"><?php echo esc_html( $event->description ); ?></p>
                            <?php endif; ?>
                            <?php if ( ! empty( $event->image_url ) ) : ?>
                                <img src="<?php echo esc_url( $event->image_url ); ?>" alt="<?php echo esc_attr( $event->event_name ); ?>" />
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="courses-zones">
                    <h3><?php esc_html_e( 'Dates', 'draglearndtg' ); ?></h3>
                    <?php foreach ( $completions as $completion ) : ?>
                        <div class="drop-zone" data-course-name="<?php echo esc_attr( $completion ); ?>">
                            <h4><?php echo esc_html( $completion ); ?></h4>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button id="finish-game"><?php esc_html_e( 'Finish', 'draglearndtg' ); ?></button>
            <p id="feedback" class="feedback"></p>
        </div>
        <?php
        return ob_get_clean();
    }
}
