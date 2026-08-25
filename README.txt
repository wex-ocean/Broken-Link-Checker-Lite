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

== Database table ==
The activation hook creates the {$wpdb->prefix}blc_lite_links table with dbDelta().
