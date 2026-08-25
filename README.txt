=== Broken Link Checker Lite ===
Contributors: plugin-author
Requires at least: 5.8
Requires PHP: 7.4
Version: 1.0.0

Stores scanned links and their HTTP check results in a custom database table.

== Database table ==
The activation hook creates the {$wpdb->prefix}blc_lite_links table with dbDelta().
