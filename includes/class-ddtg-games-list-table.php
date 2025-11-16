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
            'name' => __( 'Game Name', 'draglearndtg' ),
            'shortcode' => __( 'Shortcode', 'draglearndtg' ),
            'num_items_to_show' => __( 'Number of Items', 'draglearndtg' ),
            'actions' => __( 'Actions', 'draglearndtg' ),
        ];
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'name':
                return esc_html( $item->name );
            case 'shortcode':
                return '<code>[dragdropgame game="' . esc_attr( $item->shortcode_slug ) . '"]</code>';
            case 'num_items_to_show':
                return absint( $item->num_items_to_show );
            case 'actions':
                return sprintf( '<a href="%s">%s</a>',
                    esc_url( admin_url( 'admin.php?page=ddtg-game-results&game_id=' . $item->game_id ) ),
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
        $this->items = $wpdb->get_results( "SELECT game_id, name, shortcode_slug, num_items_to_show FROM {$games_table} ORDER BY name ASC" );
    }
}
