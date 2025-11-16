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
            'id' => 0,
        ), $atts, 'draglearn_game' );

        $game_id = intval( $atts['id'] );

        if ( ! $game_id ) {
            return '<p>' . esc_html__( 'Game ID is missing.', 'draglearndtg' ) . '</p>';
        }

        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You must be logged in to play this game.', 'draglearndtg' ) . '</p>';
        }
        $user_id = get_current_user_id();

        global $wpdb;
        $games_table    = $wpdb->prefix . 'ddg_games';
        $items_table    = $wpdb->prefix . 'ddg_items';
        $attempts_table = $wpdb->prefix . 'ddg_attempts';

        $game = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$games_table} WHERE game_id = %d", $game_id ) );

        if ( ! $game ) {
            return '<p>' . esc_html__( 'Game not found.', 'draglearndtg' ) . '</p>';
        }

        $user_id = get_current_user_id();

        // Handle attempts
        $max_attempts = $game->max_attempts;
        if ( $max_attempts > 0 ) {
            $user_attempts = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(attempt_id) FROM {$attempts_table} WHERE user_id = %d AND game_id = %d",
                    $user_id,
                    $game_id
                )
            );

            if ( $user_attempts >= $max_attempts ) {
                return '<p>' . esc_html__( 'You have reached the maximum number of attempts for this game.', 'draglearndtg' ) . '</p>';
            }
        }

        $wpdb->insert(
            $attempts_table,
            array(
                'user_id'    => $user_id,
                'game_id'    => $game_id,
                'start_time' => current_time( 'mysql', 1 ),
            )
        );
        $attempt_id = $wpdb->insert_id;

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

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT item_title, sort_value FROM {$items_table} WHERE game_id = %d ORDER BY RAND() LIMIT %d",
                $game_id,
                $game->num_items_to_show
            )
        );

        if ( empty( $items ) ) {
            return '<p>' . esc_html__( 'No items found for this game.', 'draglearndtg' ) . '</p>';
        }

        $completions = array();
        foreach ( $items as $item ) {
            $completions[] = $item->sort_value;
        }
        shuffle( $completions );
        shuffle( $items );

        ob_start();
        ?>
        <div id="draglearn-game" data-attempt-id="<?php echo esc_attr( $attempt_id ); ?>">
            <h2><?php echo esc_html( $game->name ); ?></h2>
            <p><?php echo esc_html( $game->description ); ?></p>
            <div class="drag-container">
                <div id="lessons-pool">
                    <h3><?php esc_html_e( 'Prompts', 'draglearndtg' ); ?></h3>
                    <?php foreach ( $items as $item ) : ?>
                        <div class="draggable" draggable="true" data-course="<?php echo esc_attr( $item->sort_value ); ?>">
                            <?php echo esc_html( $item->item_title ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="courses-zones">
                    <h3><?php esc_html_e( 'Completions', 'draglearndtg' ); ?></h3>
                    <?php foreach ( $completions as $completion ) : ?>
                        <div class="drop-zone" data-course-name="<?php echo esc_attr( $completion ); ?>">
                            <h4><?php echo esc_html( $completion ); ?></h4>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <p id="feedback" class="feedback"></p>
        </div>
        <?php
        return ob_get_clean();
    }
}
