<?php
/**
 * Admin table for broken links.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class BLC_Lite_Broken_Links_Table extends WP_List_Table {
	/**
	 * Number of rows shown per page.
	 *
	 * @var int
	 */
	private $per_page = 20;

	/**
	 * Configure the table.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'broken-link',
				'plural'   => 'broken-links',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Define the table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'title'        => __( 'Post/Page', 'broken-link-checker-lite' ),
			'url'          => __( 'Broken URL', 'broken-link-checker-lite' ),
			'http_status'  => __( 'HTTP Status', 'broken-link-checker-lite' ),
			'link_type'    => __( 'Link Type', 'broken-link-checker-lite' ),
			'last_checked' => __( 'Detection Date', 'broken-link-checker-lite' ),
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'title'        => array( 'title', false ),
			'url'          => array( 'url', false ),
			'http_status'  => array( 'http_status', false ),
			'link_type'    => array( 'link_type', false ),
			'last_checked' => array( 'last_checked', true ),
		);
	}

	/**
	 * Fetch and paginate broken links.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		$sortable = array( 'title', 'url', 'http_status', 'link_type', 'last_checked' );
		$orderby  = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'last_checked';
		$order    = isset( $_REQUEST['order'] ) && 'asc' === strtolower( sanitize_key( wp_unslash( $_REQUEST['order'] ) ) ) ? 'ASC' : 'DESC';
		$orderby  = in_array( $orderby, $sortable, true ) ? $orderby : 'last_checked';
		$status   = isset( $_REQUEST['blc_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['blc_status'] ) ) : 'all';
		$type     = isset( $_REQUEST['blc_link_type'] ) ? sanitize_key( wp_unslash( $_REQUEST['blc_link_type'] ) ) : 'all';
		$date     = isset( $_REQUEST['blc_date_range'] ) ? sanitize_key( wp_unslash( $_REQUEST['blc_date_range'] ) ) : 'all';
		$page     = $this->get_pagenum();
		$offset   = ( $page - 1 ) * $this->per_page;
		$table    = BLC_Lite_DB::table_name();
		$where    = array( '1=1' );
		$args     = array();

		if ( in_array( $status, array( 'ok', 'broken', 'pending' ), true ) ) {
			$where[] = 'links.status = %s';
			$args[]  = $status;
		} else {
			$status = 'all';
		}

		if ( in_array( $type, array( 'internal', 'external' ), true ) ) {
			$where[] = 'links.link_type = %s';
			$args[]  = $type;
		} else {
			$type = 'all';
		}

		if ( in_array( $date, array( '7', '30', '90' ), true ) ) {
			$where[] = 'links.last_checked >= %s';
			$args[]  = gmdate( 'Y-m-d H:i:s', time() - ( (int) $date * DAY_IN_SECONDS ) );
		} else {
			$date = 'all';
		}

		$where_sql = implode( ' AND ', $where );

		$total_items = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} AS links WHERE {$where_sql}", $args ) );
		$args[] = $this->per_page;
		$args[] = $offset;
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT links.id, links.url, links.http_status, links.link_type, links.last_checked, posts.ID AS post_id, posts.post_title AS title
				FROM {$table} AS links
				LEFT JOIN {$wpdb->posts} AS posts ON posts.ID = links.post_id
				WHERE {$where_sql}
				ORDER BY {$orderby} {$order}
				LIMIT %d OFFSET %d",
				$args
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
				'total_pages' => (int) ceil( $total_items / $this->per_page ),
			)
		);
	}

	/**
	 * Render filters above the table.
	 *
	 * @param string $which Table navigation position.
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$status = isset( $_REQUEST['blc_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['blc_status'] ) ) : 'all';
		$type   = isset( $_REQUEST['blc_link_type'] ) ? sanitize_key( wp_unslash( $_REQUEST['blc_link_type'] ) ) : 'all';
		$date   = isset( $_REQUEST['blc_date_range'] ) ? sanitize_key( wp_unslash( $_REQUEST['blc_date_range'] ) ) : 'all';
		?>
		<div class="alignleft actions">
			<select name="blc_status">
				<option value="all" <?php selected( $status, 'all' ); ?>><?php echo esc_html__( 'All statuses', 'broken-link-checker-lite' ); ?></option>
				<option value="broken" <?php selected( $status, 'broken' ); ?>><?php echo esc_html__( 'Broken', 'broken-link-checker-lite' ); ?></option>
				<option value="ok" <?php selected( $status, 'ok' ); ?>><?php echo esc_html__( 'OK', 'broken-link-checker-lite' ); ?></option>
				<option value="pending" <?php selected( $status, 'pending' ); ?>><?php echo esc_html__( 'Pending', 'broken-link-checker-lite' ); ?></option>
			</select>
			<select name="blc_link_type">
				<option value="all" <?php selected( $type, 'all' ); ?>><?php echo esc_html__( 'All link types', 'broken-link-checker-lite' ); ?></option>
				<option value="internal" <?php selected( $type, 'internal' ); ?>><?php echo esc_html__( 'Internal', 'broken-link-checker-lite' ); ?></option>
				<option value="external" <?php selected( $type, 'external' ); ?>><?php echo esc_html__( 'External', 'broken-link-checker-lite' ); ?></option>
			</select>
			<select name="blc_date_range">
				<option value="all" <?php selected( $date, 'all' ); ?>><?php echo esc_html__( 'Any detection date', 'broken-link-checker-lite' ); ?></option>
				<option value="7" <?php selected( $date, '7' ); ?>><?php echo esc_html__( 'Last 7 days', 'broken-link-checker-lite' ); ?></option>
				<option value="30" <?php selected( $date, '30' ); ?>><?php echo esc_html__( 'Last 30 days', 'broken-link-checker-lite' ); ?></option>
				<option value="90" <?php selected( $date, '90' ); ?>><?php echo esc_html__( 'Last 90 days', 'broken-link-checker-lite' ); ?></option>
			</select>
			<?php submit_button( __( 'Filter', 'broken-link-checker-lite' ), 'secondary', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Render a standard column.
	 *
	 * @param object $item Row data.
	 * @param string $column_name Column name.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		if ( 'url' === $column_name ) {
			return sprintf(
				'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_url( $item->url ),
				esc_html( $item->url )
			);
		}

		if ( 'http_status' === $column_name ) {
			return null === $item->http_status ? '&mdash;' : esc_html( $item->http_status );
		}

		if ( 'last_checked' === $column_name ) {
			return $item->last_checked
				? esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item->last_checked ) )
				: '&mdash;';
		}

		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
	}

	/**
	 * Render the linked post/page title.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	protected function column_title( $item ) {
		$title = $item->title ? $item->title : __( '(no title)', 'broken-link-checker-lite' );
		$actions = array(
			'rescan' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=blc_lite_rescan_link&link_id=' . $item->id ), 'blc_lite_rescan_' . $item->id ) ),
				esc_html__( 'Rescan', 'broken-link-checker-lite' )
			),
			'edit' => $item->post_id && get_edit_post_link( $item->post_id )
				? sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $item->post_id ) ), esc_html__( 'Edit link', 'broken-link-checker-lite' ) )
				: '',
			'ignore' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=blc_lite_remove_link&link_id=' . $item->id ), 'blc_lite_remove_' . $item->id ) ),
				esc_html__( 'Remove', 'broken-link-checker-lite' )
			),
		);

		if ( $item->post_id ) {
			$edit_link = get_edit_post_link( $item->post_id );

			if ( $edit_link ) {
				return sprintf( '<a href="%1$s">%2$s</a>%3$s', esc_url( $edit_link ), esc_html( $title ), $this->row_actions( array_filter( $actions ) ) );
			}
		}

		return esc_html( $title ) . $this->row_actions( array_filter( $actions ) );
	}
}