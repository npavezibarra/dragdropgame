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
        global $wpdb;
        $lessons_table = $wpdb->prefix . 'draglearn_lessons';
        $courses_table = $wpdb->prefix . 'draglearn_courses';

        // Enqueue scripts and styles.
        wp_enqueue_style( 'draglearn-game-style', plugins_url( '../assets/css/draglearn-game.css', __FILE__ ) );
        wp_enqueue_script( 'draglearn-game-script', plugins_url( '../assets/js/draglearn-game.js', __FILE__ ), array(), DRAGLEARN_VERSION, true );

        // Fetch 5 random courses.
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
        <div id="draglearn-game">
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
