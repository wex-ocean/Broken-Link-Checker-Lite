<?php
/**
 * Scans published post and page content for links.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BLC_Lite_Scanner {
	/**
	 * Scan all published posts and pages and store their links.
	 *
	 * @return int Number of links stored.
	 */
	public static function scan_all() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = array_values( array_diff( $post_types, BLC_Lite_Exclusions::get_settings()['post_types'] ) );

		if ( empty( $post_types ) ) {
			return 0;
		}

		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		$stored_count = 0;

		foreach ( $posts as $post ) {
			if ( BLC_Lite_Exclusions::is_post_type_excluded( $post->post_type ) ) {
				continue;
			}

			$links = array_unique( self::extract_links( $post->post_content ) );

			foreach ( $links as $url ) {
				if ( '' === trim( $url ) || BLC_Lite_Exclusions::is_url_excluded( $url ) ) {
					continue;
				}

				$inserted = self::store_link(
					$post->ID,
					$url,
					self::classify_link( $url )
				);

				if ( $inserted ) {
					$stored_count++;
				}
			}
		}

		return $stored_count;
	}

	/**
	 * Extract href attributes from content.
	 *
	 * @param string $content Post content.
	 * @return array
	 */
	public static function extract_links( $content ) {
		$links = array();

		if ( class_exists( 'DOMDocument' ) ) {
			$document = new DOMDocument();
			$previous = libxml_use_internal_errors( true );
			$document->loadHTML( '<?xml encoding="UTF-8">' . $content );

			foreach ( $document->getElementsByTagName( 'a' ) as $anchor ) {
				if ( $anchor->hasAttribute( 'href' ) ) {
					$links[] = trim( $anchor->getAttribute( 'href' ) );
				}
			}

			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
		} else {
			preg_match_all(
				'/<a\b[^>]*\bhref\s*=\s*(?:["\']([^"\']+)["\']|([^\s>]+))/i',
				$content,
				$matches
			);
			$links = array_merge( $matches[1], $matches[2] );
		}

		return array_filter( $links );
	}

	/**
	 * Classify a URL against the site's host.
	 *
	 * @param string $url Link URL.
	 * @return string
	 */
	public static function classify_link( $url ) {
		$parsed_url = wp_parse_url( $url );

		if ( empty( $parsed_url['host'] ) ) {
			return empty( $parsed_url['scheme'] ) ? 'internal' : 'external';
		}

		$site_url  = wp_parse_url( home_url() );
		$link_host = strtolower( $parsed_url['host'] );
		$site_host = isset( $site_url['host'] ) ? strtolower( $site_url['host'] ) : '';

		return $link_host === $site_host ? 'internal' : 'external';
	}

	/**
	 * Store a link with a pending check result.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url Link URL.
	 * @param string $link_type Internal or external.
	 * @return int|false
	 */
	private static function store_link( $post_id, $url, $link_type ) {
		global $wpdb;

		return $wpdb->insert(
			BLC_Lite_DB::table_name(),
			array(
				'post_id'     => $post_id,
				'url'         => $url,
				'link_type'   => $link_type,
				'http_status' => null,
				'error_type'  => '',
				'last_checked'=> null,
				'status'      => 'pending',
			),
			array( '%d', '%s', '%s', null, '%s', null, '%s' )
		);
	}

}