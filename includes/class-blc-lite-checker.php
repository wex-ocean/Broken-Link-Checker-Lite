<?php
/**
 * Checks stored links in small WP Cron batches.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BLC_Lite_Checker {
	const CRON_HOOK   = 'blc_lite_check_links';
	const BATCH_SIZE  = 20;
	const REQUEST_TIMEOUT = 10;

	/**
	 * Register the cron callback.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'check_batch' ) );
	}

	/**
	 * Create the database table and schedule link checks.
	 *
	 * @return void
	 */
	public static function activate() {
		BLC_Lite_DB::activate();

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Remove scheduled checks while retaining scan data.
	 *
	 * @return void
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Check the next batch of pending links.
	 *
	 * @return int Number of links updated.
	 */
	public static function check_batch() {
		global $wpdb;

		$links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, url FROM " . BLC_Lite_DB::table_name() . " WHERE status = %s ORDER BY id ASC LIMIT %d",
				'pending',
				self::BATCH_SIZE
			)
		);
		$updated_count = 0;

		foreach ( $links as $link ) {
			$result = self::request_link( $link->url );

			$updated = $wpdb->update(
				BLC_Lite_DB::table_name(),
				array(
					'http_status' => $result['http_status'],
					'error_type'  => $result['error_type'],
					'last_checked'=> current_time( 'mysql', true ),
					'status'      => $result['status'],
				),
				array( 'id' => $link->id ),
				array( '%d', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				$updated_count++;
			}
		}

		return $updated_count;
	}

	/**
	 * Request a URL with HEAD, falling back to GET when HEAD is unsupported.
	 *
	 * @param string $url Link URL.
	 * @return array
	 */
	private static function request_link( $url ) {
		$args = array(
			'timeout'     => self::REQUEST_TIMEOUT,
			'redirection' => 5,
			'user-agent'  => 'Broken Link Checker Lite/' . BLC_LITE_VERSION,
		);
		$response = wp_remote_head( $url, $args );

		if ( is_wp_error( $response ) || in_array( wp_remote_retrieve_response_code( $response ), array( 405, 501 ), true ) ) {
			$response = wp_remote_get( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			return array(
				'http_status' => null,
				'error_type'  => self::error_type( $response ),
				'status'      => 'broken',
			);
		}

		$http_status = (int) wp_remote_retrieve_response_code( $response );

		return array(
			'http_status' => $http_status,
			'error_type'  => $http_status >= 200 && $http_status < 400 ? '' : 'http_error',
			'status'      => $http_status >= 200 && $http_status < 400 ? 'ok' : 'broken',
		);
	}

	/**
	 * Convert a WP_Error into a stable error category.
	 *
	 * @param WP_Error $error Request error.
	 * @return string
	 */
	private static function error_type( $error ) {
		$message = strtolower( $error->get_error_message() );

		return false !== strpos( $message, 'timed out' ) || false !== strpos( $message, 'timeout' )
			? 'timeout'
			: 'connection_error';
	}
}