<?php
/**
 * Schedules background content scans.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BLC_Lite_Scheduler {
	const CRON_HOOK    = 'blc_lite_run_scan';
	const SETTINGS_KEY = 'blc_lite_scan_settings';

	/**
	 * Register cron hooks and custom intervals.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_intervals' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_scan' ) );
	}

	/**
	 * Add daily and weekly recurrence intervals.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public static function add_intervals( $schedules ) {
		$schedules['blc_lite_daily'] = array(
			'interval' => DAY_IN_SECONDS,
			'display'  => __( 'Daily', 'broken-link-checker-lite' ),
		);
		$schedules['blc_lite_weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => __( 'Weekly', 'broken-link-checker-lite' ),
		);

		return $schedules;
	}

	/**
	 * Schedule scans according to the saved settings.
	 *
	 * @return void
	 */
	public static function activate() {
		self::reschedule( self::get_settings() );
	}

	/**
	 * Remove all scheduled scans.
	 *
	 * @return void
	 */
	public static function deactivate() {
		self::clear_schedule();
	}

	/**
	 * Register the scan settings.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'blc_lite_settings',
			self::SETTINGS_KEY,
			array(
				'default'           => self::defaults(),
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Validate and apply scan settings.
	 *
	 * @param array $input Submitted settings.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$settings = array(
			'enabled'  => empty( $input['enabled'] ) ? 0 : 1,
			'frequency' => isset( $input['frequency'] ) ? sanitize_key( $input['frequency'] ) : 'manual',
		);

		if ( ! in_array( $settings['frequency'], array( 'daily', 'weekly', 'manual' ), true ) ) {
			$settings['frequency'] = 'manual';
		}

		self::reschedule( $settings );

		return $settings;
	}

	/**
	 * Run the scanner from WP Cron.
	 *
	 * @return int Number of links stored.
	 */
	public static function run_scan() {
		return BLC_Lite_Scanner::scan_all();
	}

	/**
	 * Return saved settings with defaults applied.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return wp_parse_args( get_option( self::SETTINGS_KEY, array() ), self::defaults() );
	}

	/**
	 * Return default scan settings.
	 *
	 * @return array
	 */
	private static function defaults() {
		return array(
			'enabled'   => 0,
			'frequency' => 'manual',
		);
	}

	/**
	 * Replace the existing scan event with the selected schedule.
	 *
	 * @param array $settings Scan settings.
	 * @return void
	 */
	private static function reschedule( $settings ) {
		self::clear_schedule();

		if ( empty( $settings['enabled'] ) || 'manual' === $settings['frequency'] ) {
			return;
		}

		$recurrence = 'daily' === $settings['frequency'] ? 'blc_lite_daily' : 'blc_lite_weekly';
		wp_schedule_event( time() + MINUTE_IN_SECONDS, $recurrence, self::CRON_HOOK );
	}

	/**
	 * Clear every instance of the scan event.
	 *
	 * @return void
	 */
	private static function clear_schedule() {
		while ( $timestamp = wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}