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
            return '<p>' . esc_html__( 'Game ID is missing.', 'draglearn' ) . '</p>';
        }

        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You must be logged in to play this game.', 'draglearn' ) . '</p>';
        }
        $user_id = get_current_user_id();

        global $wpdb;
        $games_table      = $wpdb->prefix . 'draglearn_games';
        $attempts_table   = $wpdb->prefix . 'draglearn_attempts';
        $lessons_table    = $wpdb->prefix . 'draglearn_lessons';
        $courses_table    = $wpdb->prefix . 'draglearn_courses';

        $game = $wpdb->get_row( $wpdb->prepare( "SELECT max_attempts FROM {$games_table} WHERE game_id = %d", $game_id ) );

        if ( ! $game ) {
            return '<p>' . esc_html__( 'Game not found.', 'draglearn' ) . '</p>';
        }

        $max_attempts = $game->max_attempts;
        $user_attempts = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(attempt_id) FROM {$attempts_table} WHERE user_id = %d AND game_id = %d",
                $user_id,
                $game_id
            )
        );

        if ( $max_attempts > 0 && $user_attempts >= $max_attempts ) {
            return '<p>' . esc_html__( 'You have reached the maximum number of attempts for this game.', 'draglearn' ) . '</p>';
        }

        $wpdb->insert(
            $attempts_table,
            array(
                'user_id'     => $user_id,
                'game_id'     => $game_id,
                'start_time'  => current_time( 'mysql', 1 ),
            ),
            array( '%d', '%d', '%s' )
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

        $courses_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$courses_table} ORDER BY RAND() LIMIT %d",
                5
            )
        );

        if ( empty( $courses_results ) ) {
            return '<p>' . esc_html__( 'No courses found to start the game.', 'draglearn' ) . '</p>';
        }

        $lessons = array();
        $courses = array();

        foreach ( $courses_results as $course ) {
            $courses[] = $course->course_name;
            $lesson_result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$lessons_table} WHERE course_id = %d ORDER BY RAND() LIMIT %d",
                    $course->course_id,
                    1
                )
            );

            if ( ! empty( $lesson_result ) ) {
                $lessons[] = array(
                    'title'  => $lesson_result->lesson_title,
                    'course' => $course->course_name,
                );
            }
        }

        shuffle( $lessons );
        shuffle( $courses );

        ob_start();
        ?>
        <div id="draglearn-game" data-attempt-id="<?php echo esc_attr( $attempt_id ); ?>">
            <h2><?php esc_html_e( 'Match the Lessons to the Courses', 'draglearn' ); ?></h2>
            <div class="drag-container">
                <div id="lessons-pool">
                    <h3><?php esc_html_e( 'Lessons', 'draglearn' ); ?></h3>
                    <?php foreach ( $lessons as $lesson ) : ?>
                        <div class="draggable" draggable="true" data-course="<?php echo esc_attr( $lesson['course'] ); ?>">
                            <?php echo esc_html( $lesson['title'] ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="courses-zones">
                    <h3><?php esc_html_e( 'Courses', 'draglearn' ); ?></h3>
                    <?php foreach ( $courses as $course ) : ?>
                        <div class="drop-zone" data-course-name="<?php echo esc_attr( $course ); ?>">
                            <h4><?php echo esc_html( $course ); ?></h4>
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
