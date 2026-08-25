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
		$page     = $this->get_pagenum();
		$offset   = ( $page - 1 ) * $this->per_page;
		$table    = BLC_Lite_DB::table_name();

		$total_items = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'broken'" );
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT links.id, links.url, links.http_status, links.link_type, links.last_checked, posts.ID AS post_id, posts.post_title AS title
				FROM {$table} AS links
				LEFT JOIN {$wpdb->posts} AS posts ON posts.ID = links.post_id
				WHERE links.status = %s
				ORDER BY {$orderby} {$order}
				LIMIT %d OFFSET %d",
				'broken',
				$this->per_page,
				$offset
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

		if ( $item->post_id ) {
			$edit_link = get_edit_post_link( $item->post_id );

			if ( $edit_link ) {
				return sprintf( '<a href="%1$s">%2$s</a>', esc_url( $edit_link ), esc_html( $title ) );
			}
		}

		return esc_html( $title );
	}
}