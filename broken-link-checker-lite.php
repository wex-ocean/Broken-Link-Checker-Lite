<?php
/**
 * Plugin Name: Broken Link Checker Lite
 * Description: Stores scanned links and their HTTP check results.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Plugin Author
 * Text Domain: broken-link-checker-lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLC_LITE_VERSION', '1.0.0' );
define( 'BLC_LITE_FILE', __FILE__ );
define( 'BLC_LITE_DIR', plugin_dir_path( __FILE__ ) );

require_once BLC_LITE_DIR . 'includes/class-blc-lite-db.php';
require_once BLC_LITE_DIR . 'includes/class-blc-lite-exclusions.php';
require_once BLC_LITE_DIR . 'includes/class-blc-lite-scanner.php';
require_once BLC_LITE_DIR . 'includes/class-blc-lite-checker.php';
require_once BLC_LITE_DIR . 'includes/class-blc-lite-scheduler.php';
require_once BLC_LITE_DIR . 'admin/class-blc-lite-broken-links-table.php';
require_once BLC_LITE_DIR . 'admin/class-blc-lite-admin.php';

register_activation_hook( __FILE__, array( 'BLC_Lite_Checker', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BLC_Lite_Checker', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		BLC_Lite_Admin::init();
		BLC_Lite_Checker::init();
	}
);
