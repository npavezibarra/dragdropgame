<?php
/**
 * Attempt tracking and eligibility logic for DragLearn games.
 *
 * @package DragLearn
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * DDTG_Attempts Class.
 */
class DDTG_Attempts {

    /**
     * Determine whether the current user can play the specified game.
     *
     * @param int $game_id Game identifier.
     * @param int $user_id User identifier.
     *
     * @return array{allowed: bool, message: string}
     */
    public static function can_play( $game_id, $user_id ) {
        global $wpdb;

        $games_table    = $wpdb->prefix . 'ddg_games';
        $attempts_table = $wpdb->prefix . 'ddg_attempts';

        $game = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$games_table} WHERE game_id = %d", $game_id ) );

        if ( ! $game ) {
            return array(
                'allowed' => false,
                'message' => __( 'Game not found.', 'draglearndtg' ),
            );
        }

        $attempt_limit  = isset( $game->attempt_limit ) ? (int) $game->attempt_limit : 0;
        $attempt_period = isset( $game->attempt_period ) ? (int) $game->attempt_period : 0;

        if ( $attempt_limit <= 0 ) {
            return array(
                'allowed' => true,
                'message' => '',
            );
        }

        $period_clause = '';

        if ( $attempt_period > 0 ) {
            $cutoff       = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $attempt_period . ' days' ) );
            $period_clause = $wpdb->prepare( ' AND start_time >= %s', $cutoff );
        }

        $attempts_made = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$attempts_table} WHERE game_id = %d AND user_id = %d{$period_clause}",
                $game_id,
                $user_id
            )
        );

        if ( $attempts_made >= $attempt_limit ) {
            return array(
                'allowed' => false,
                'message' => __( 'You have reached the attempt limit for this game.', 'draglearndtg' ),
            );
        }

        return array(
            'allowed' => true,
            'message' => '',
        );
    }

    /**
     * Record the start of a new attempt.
     *
     * @param int $game_id Game identifier.
     * @param int $user_id User identifier.
     *
     * @return int Attempt identifier.
     */
    public static function record_attempt( $game_id, $user_id ) {
        global $wpdb;

        $attempts_table = $wpdb->prefix . 'ddg_attempts';

        $wpdb->insert(
            $attempts_table,
            array(
                'game_id'    => (int) $game_id,
                'user_id'    => (int) $user_id,
                'start_time' => current_time( 'mysql', true ),
                'finish_time'=> null,
                'score'      => 0,
                'total'      => 0,
            )
        );

        return (int) $wpdb->insert_id;
    }
}
