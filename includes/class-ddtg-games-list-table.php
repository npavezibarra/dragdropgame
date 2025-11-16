<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class DDTG_Games_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Game', 'draglearndtg' ),
            'plural'   => __( 'Games', 'draglearndtg' ),
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        return [
            'game_name'         => __( 'Game Name', 'draglearndtg' ),
            'shortcode'         => __( 'Shortcode', 'draglearndtg' ),
            'num_events_to_show' => __( 'Events to Display', 'draglearndtg' ),
            'actions'           => __( 'Actions', 'draglearndtg' ),
        ];
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'game_name':
                return esc_html( $item->game_name );
            case 'shortcode':
                return '<code>[draglearn_game game="' . esc_attr( $item->shortcode_slug ) . '"]</code>';
            case 'num_events_to_show':
                return absint( $item->num_events_to_show );
            case 'actions':
                $edit_url    = admin_url( 'admin.php?page=ddtg-create-game&action=edit&game_id=' . $item->id );
                $results_url = admin_url( 'admin.php?page=ddtg-game-results&game_id=' . $item->id );
                return sprintf( '<a href="%s">%s</a> | <a href="%s">%s</a>',
                    esc_url( $edit_url ),
                    __( 'Edit', 'draglearndtg' ),
                    esc_url( $results_url ),
                    __( 'Results', 'draglearndtg' )
                );
            default:
                return print_r( $item, true );
        }
    }

    function prepare_items() {
        global $wpdb;
        $games_table = $wpdb->prefix . 'ddg_games';

        $this->_column_headers = array( $this->get_columns(), array(), array() );
        $this->items           = $wpdb->get_results( "SELECT id, game_name, shortcode_slug, num_events_to_show FROM {$games_table} ORDER BY game_name ASC" );
    }
}
