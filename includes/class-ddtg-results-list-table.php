<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class DDTG_Results_List_Table extends WP_List_Table {

    private $game_id = 0;

    public function __construct( $game_id ) {
        $this->game_id = $game_id;

        parent::__construct( [
            'singular' => __( 'Result', 'draglearn' ),
            'plural'   => __( 'Results', 'draglearn' ),
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        return [
            'user_login'  => __( 'Student Name', 'draglearn' ),
            'score'       => __( 'Score', 'draglearn' ),
            'total'       => __( 'Total', 'draglearn' ),
            'start_time'  => __( 'Start Time', 'draglearn' ),
            'finish_time' => __( 'Finish Time', 'draglearn' ),
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

        $sortable_columns = $this->get_sortable_columns();
        $orderby          = isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $sortable_columns ), true ) ? sanitize_key( $_GET['orderby'] ) : 'user_login';
        $order            = isset( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), [ 'asc', 'desc' ], true ) ? strtoupper( sanitize_key( $_GET['order'] ) ) : 'ASC';

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
