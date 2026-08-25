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
		add_action( 'admin_init', array( 'BLC_Lite_Scheduler', 'register_settings' ) );
		add_action( 'admin_post_blc_lite_manual_scan', array( __CLASS__, 'manual_scan' ) );
		add_action( 'admin_post_blc_lite_rescan_link', array( __CLASS__, 'rescan_link' ) );
		add_action( 'admin_post_blc_lite_remove_link', array( __CLASS__, 'remove_link' ) );
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
		$settings = BLC_Lite_Scheduler::get_settings();
		$broken_links_table = new BLC_Lite_Broken_Links_Table();
		$broken_links_table->prepare_items();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Broken Link Checker Lite', 'broken-link-checker-lite' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'blc_lite_settings' );
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Enable scheduled scans', 'broken-link-checker-lite' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( BLC_Lite_Scheduler::SETTINGS_KEY ); ?>[enabled]" value="1" <?php checked( $settings['enabled'], 1 ); ?> />
								<?php echo esc_html__( 'Run scans automatically in the background.', 'broken-link-checker-lite' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blc-lite-frequency"><?php echo esc_html__( 'Scan frequency', 'broken-link-checker-lite' ); ?></label></th>
						<td>
							<select id="blc-lite-frequency" name="<?php echo esc_attr( BLC_Lite_Scheduler::SETTINGS_KEY ); ?>[frequency]">
								<option value="daily" <?php selected( $settings['frequency'], 'daily' ); ?>><?php echo esc_html__( 'Daily', 'broken-link-checker-lite' ); ?></option>
								<option value="weekly" <?php selected( $settings['frequency'], 'weekly' ); ?>><?php echo esc_html__( 'Weekly', 'broken-link-checker-lite' ); ?></option>
								<option value="manual" <?php selected( $settings['frequency'], 'manual' ); ?>><?php echo esc_html__( 'Manual only', 'broken-link-checker-lite' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<hr />
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="blc_lite_manual_scan" />
				<?php wp_nonce_field( 'blc_lite_manual_scan' ); ?>
				<?php submit_button( __( 'Run Scan Now', 'broken-link-checker-lite' ), 'secondary' ); ?>
			</form>
			<h2><?php echo esc_html__( 'Broken Links', 'broken-link-checker-lite' ); ?></h2>
			<?php $broken_links_table->display(); ?>
		</div>
		<?php
	}

	/**
	 * Run a scan from the manual admin action.
	 *
	 * @return void
	 */
	public static function manual_scan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to run a scan.', 'broken-link-checker-lite' ) );
		}

		check_admin_referer( 'blc_lite_manual_scan' );
		BLC_Lite_Scanner::scan_all();

		wp_safe_redirect( admin_url( 'admin.php?page=broken-link-checker-lite&scan=complete' ) );
		exit;
	}

	/**
	 * Recheck one link from its row action.
	 *
	 * @return void
	 */
	public static function rescan_link() {
		$link_id = isset( $_GET['link_id'] ) ? absint( $_GET['link_id'] ) : 0;
		self::authorize_link_action( $link_id, 'blc_lite_rescan_' . $link_id );
		BLC_Lite_Checker::check_link( $link_id );
		self::redirect_to_dashboard();
	}

	/**
	 * Remove one link from its row action.
	 *
	 * @return void
	 */
	public static function remove_link() {
		global $wpdb;

		$link_id = isset( $_GET['link_id'] ) ? absint( $_GET['link_id'] ) : 0;
		self::authorize_link_action( $link_id, 'blc_lite_remove_' . $link_id );
		$wpdb->delete( BLC_Lite_DB::table_name(), array( 'id' => $link_id ), array( '%d' ) );
		self::redirect_to_dashboard();
	}

	/**
	 * Validate permissions and the row-action nonce.
	 *
	 * @param int    $link_id Link ID.
	 * @param string $action Nonce action.
	 * @return void
	 */
	private static function authorize_link_action( $link_id, $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage links.', 'broken-link-checker-lite' ) );
		}

		check_admin_referer( $action );

		if ( ! $link_id ) {
			wp_die( esc_html__( 'The link could not be found.', 'broken-link-checker-lite' ) );
		}
	}

	/**
	 * Return to the plugin dashboard.
	 *
	 * @return void
	 */
	private static function redirect_to_dashboard() {
		wp_safe_redirect( admin_url( 'admin.php?page=broken-link-checker-lite' ) );
		exit;
	}
}
