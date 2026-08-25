<?php
/**
 * Scanner exclusion settings and matching helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BLC_Lite_Exclusions {
	const SETTINGS_KEY = 'blc_lite_exclusions';

	/**
	 * Register exclusion settings.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'blc_lite_exclusions',
			self::SETTINGS_KEY,
			array(
				'default'           => self::defaults(),
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize wildcard patterns and post types.
	 *
	 * @param array $input Submitted exclusions.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$input           = is_array( $input ) ? $input : array();
		$url_patterns    = isset( $input['url_patterns'] ) ? preg_split( '/\r\n|\r|\n/', $input['url_patterns'] ) : array();
		$public_post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types       = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? $input['post_types'] : array();

		return array(
			'url_patterns' => array_values( array_filter( array_map( 'trim', $url_patterns ) ) ),
			'post_types'   => array_values( array_intersect( array_map( 'sanitize_key', $post_types ), $public_post_types ) ),
		);
	}

	/**
	 * Return saved exclusions with defaults applied.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return wp_parse_args( get_option( self::SETTINGS_KEY, array() ), self::defaults() );
	}

	/**
	 * Determine whether a URL matches an excluded wildcard pattern.
	 *
	 * @param string $url URL to test.
	 * @return bool
	 */
	public static function is_url_excluded( $url ) {
		foreach ( self::get_settings()['url_patterns'] as $pattern ) {
			$regex = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/i';

			if ( preg_match( $regex, $url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether a post type is excluded.
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public static function is_post_type_excluded( $post_type ) {
		return in_array( $post_type, self::get_settings()['post_types'], true );
	}

	/**
	 * Return default exclusions.
	 *
	 * @return array
	 */
	private static function defaults() {
		return array(
			'url_patterns' => array(),
			'post_types'   => array(),
		);
	}
}