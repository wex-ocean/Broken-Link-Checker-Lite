=== Broken Link Checker Lite ===
Contributors: plugin-author
Requires at least: 5.8
Requires PHP: 7.4
Version: 1.0.0

Stores scanned links and their HTTP check results in a custom database table.

== Scanning ==
Call `BLC_Lite_Scanner::scan_all()` from a scheduled task or admin action to scan
all published posts and pages. Each supported link is stored with a `pending`
status until an HTTP checker processes it. Non-empty `href` values are stored;
relative links are classified as internal and links with a scheme are external.

`BLC_Lite_Checker::check_batch()` checks up to 20 pending links per WP Cron run
using `wp_remote_head()` with a `wp_remote_get()` fallback.

The Dashboard includes a secure AJAX-powered Scan Now action with progress
feedback. Admin actions and scan requests require capability and nonce checks.

== Scheduled scans ==
Use the Link Checker Lite settings page to enable daily or weekly background
scans. Manual-only mode disables recurring scans; the Run Scan Now action is
available for on-demand scans.

== Exclusions ==
The Exclusions tab supports wildcard URL patterns and exclusions for public
post types.

== Database table ==
The activation hook creates the {$wpdb->prefix}blc_lite_links table with dbDelta().
