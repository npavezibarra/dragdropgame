<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class DDTG_Results_List_Table extends WP_List_Table {

    private $game_id = 0;

    public function __construct( $game_id ) {
        $this->game_id = absint( $game_id );

        parent::__construct( [
            'singular' => __( 'Result', 'draglearndtg' ),
            'plural'   => __( 'Results', 'draglearndtg' ),
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        return [
            'user_login'  => __( 'Student Name', 'draglearndtg' ),
            'score'       => __( 'Score', 'draglearndtg' ),
            'total'       => __( 'Total', 'draglearndtg' ),
            'start_time'  => __( 'Start Time', 'draglearndtg' ),
            'finish_time' => __( 'Finish Time', 'draglearndtg' ),
        ];
    }

    public function get_sortable_columns() {
        return [
            'user_login'  => [ 'user_login', false ],
            'score'       => [ 'score', false ],
            'start_time'  => [ 'start_time', false ],
            'finish_time' => [ 'finish_time', false ],
        ];
    }

    public function column_default( $item, $column_name ) {
        return esc_html( $item->$column_name );
    }

    public function prepare_items() {
        global $wpdb;
        $attempts_table = $wpdb->prefix . 'ddg_attempts';
        $users_table    = $wpdb->prefix . 'users';

        if ( ! $this->game_id ) {
            $this->items = [];
            return;
        }

        $sortable_columns = $this->get_sortable_columns();
        $orderby_request  = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'user_login';
        $orderby          = array_key_exists( $orderby_request, $sortable_columns ) ? $orderby_request : 'user_login';
        $order_request    = isset( $_GET['order'] ) ? strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'asc';
        $order            = in_array( $order_request, [ 'asc', 'desc' ], true ) ? strtoupper( $order_request ) : 'ASC';

        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
        $this->items           = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT u.user_login, a.score, a.total, a.start_time, a.finish_time
                FROM {$attempts_table} AS a
                JOIN {$users_table} AS u ON a.user_id = u.ID
                WHERE a.game_id = %d
                ORDER BY {$orderby} {$order}",
                $this->game_id
            )
        );
    }
}
