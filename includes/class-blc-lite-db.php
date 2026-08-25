<?php
/**
 * Database setup and access helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BLC_Lite_DB {
	/**
	 * Return the scanned links table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'blc_lite_links';
	}

	/**
	 * Create or update the scanned links table.
	 *
	 * @return void
	 */
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			url text NOT NULL,
			link_type varchar(8) NOT NULL DEFAULT 'external',
			http_status smallint(5) unsigned DEFAULT NULL,
			error_type varchar(100) NOT NULL DEFAULT '',
			last_checked datetime DEFAULT NULL,
			status varchar(7) NOT NULL DEFAULT 'pending',
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY status (status),
			KEY link_type (link_type)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Keep the table intact when the plugin is deactivated.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Data is retained so reactivation does not discard scan history.
	}
}
