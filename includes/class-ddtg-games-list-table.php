<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class DDTG_Games_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Game', 'draglearndtg' ),
            'plural'   => __( 'Games', 'draglearndtg' ),
            'ajax'     => false,
        ] );
    }

    /**
     * Define table columns.
     *
     * @return array
     */
    public function get_columns() {
        return [
            'game_name'          => __( 'Game Name', 'draglearndtg' ),
            'shortcode'          => __( 'Shortcode', 'draglearndtg' ),
            'num_events_to_show' => __( 'Events to Display', 'draglearndtg' ),
            'date_created'       => __( 'Created', 'draglearndtg' ),
            'actions'            => __( 'Actions', 'draglearndtg' ),
        ];
    }

    /**
     * Declare sortable columns.
     *
     * @return array
     */
    protected function get_sortable_columns() {
        return [
            'game_name'          => [ 'game_name', true ],
            'num_events_to_show' => [ 'num_events_to_show', false ],
            'date_created'       => [ 'date_created', true ],
        ];
    }

    /**
     * Default column output.
     *
     * @param object $item        Current row.
     * @param string $column_name Column name.
     *
     * @return string
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'game_name':
                return esc_html( $item->game_name );
            case 'shortcode':
                return '<code>[dragdropgame game="' . esc_attr( $item->shortcode_slug ) . '"]</code>';
            case 'num_events_to_show':
                return absint( $item->num_events_to_show );
            case 'date_created':
                $date = mysql2date( get_option( 'date_format' ), $item->date_created );
                $time = mysql2date( get_option( 'time_format' ), $item->date_created );
                return esc_html( sprintf( '%1$s %2$s', $date, $time ) );
            case 'actions':
                $edit_url    = admin_url( 'admin.php?page=ddtg-create-game&game_id=' . absint( $item->id ) );
                $results_url = admin_url( 'admin.php?page=ddtg-game-results&game_id=' . absint( $item->id ) );
                $delete_url  = wp_nonce_url(
                    admin_url( 'admin-post.php?action=ddtg_delete_game&game_id=' . absint( $item->id ) ),
                    'ddtg_delete_game_' . absint( $item->id )
                );

                $links = [
                    sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'draglearndtg' ) ),
                    sprintf( '<a href="%s">%s</a>', esc_url( $results_url ), esc_html__( 'Results', 'draglearndtg' ) ),
                    sprintf(
                        '<a href="%s" class="ddtg-delete-game" onclick="return confirm(\'%s\');">%s</a>',
                        esc_url( $delete_url ),
                        esc_attr__( 'Are you sure you want to delete this game? This action cannot be undone.', 'draglearndtg' ),
                        esc_html__( 'Delete', 'draglearndtg' )
                    ),
                ];

                return implode( ' | ', $links );
            default:
                return '';
        }
    }

    /**
     * Prepare table items.
     */
    public function prepare_items() {
        global $wpdb;

        $games_table = $wpdb->prefix . 'ddg_games';
        $current_user = get_current_user_id();
        $per_page     = $this->get_items_per_page( 'ddtg_games_per_page', 20 );
        $current_page = $this->get_pagenum();
        $sortable     = $this->get_sortable_columns();

        $requested_orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'date_created';
        $orderby            = array_key_exists( $requested_orderby, $sortable ) ? $requested_orderby : 'date_created';

        $requested_order = isset( $_GET['order'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'DESC';
        $order           = in_array( $requested_order, [ 'ASC', 'DESC' ], true ) ? $requested_order : 'DESC';

        $offset = ( $current_page - 1 ) * $per_page;

        $total_items = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$games_table} WHERE user_id = %d",
                $current_user
            )
        );

        $query = $wpdb->prepare(
            "SELECT id, game_name, shortcode_slug, num_events_to_show, date_created
            FROM {$games_table}
            WHERE user_id = %d
            ORDER BY {$orderby} {$order}
            LIMIT %d OFFSET %d",
            $current_user,
            $per_page,
            $offset
        );

        $this->items = $wpdb->get_results( $query );

        $this->_column_headers = [ $this->get_columns(), [], $sortable ];

        $this->set_pagination_args(
            [
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => $per_page ? ceil( $total_items / $per_page ) : 1,
            ]
        );
    }
}
