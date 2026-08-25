<?php
/**
 * Admin menu and screen registration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BLC_Lite_Admin {
	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	/**
	 * Register the plugin's admin page.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Broken Link Checker Lite', 'broken-link-checker-lite' ),
			__( 'Link Checker Lite', 'broken-link-checker-lite' ),
			'manage_options',
			'broken-link-checker-lite',
			array( __CLASS__, 'render_page' ),
			'dashicons-admin-links',
			25
		);
	}

	/**
	 * Render the initial admin screen.
	 *
	 * @return void
	 */
	public static function render_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Broken Link Checker Lite', 'broken-link-checker-lite' ); ?></h1>
			<p><?php echo esc_html__( 'Scanned links will appear here.', 'broken-link-checker-lite' ); ?></p>
		</div>
		<?php
	}
}
