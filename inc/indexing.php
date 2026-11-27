<?php
/**
 * Keep thin automatic pages out of Google, so it spends its attention on the
 * statements, meeting categories and pages.
 *
 * From the Search Console exports of 27 November 2026 ("Crawled / Discovered
 * – currently not indexed"), about 250 of the addresses were pages like
 * these, which Google crawled and rejected:
 * - tag archives (/tag/…): the special tags (SM_…, SC_…, hasnocontent…)
 *   are settings, not topics: noindex, and left out of Yoast's sitemap;
 * - feeds of tags, categories, authors and comments, advertised in every
 *   page's <head>: no longer advertised (the main feed stays);
 * - shortlinks (?p=123) in the <head> and HTTP headers: removed;
 * - author archives and the "category-descriptors" pages (a plugin's post
 *   type): noindex, and left out of the sitemap.
 * Locations (/mec_location/…) no longer have public pages at all; see the
 * taxonomy in functions.php.
 *
 * Nothing here changes any content; "noindex" pages stay visible to
 * visitors, and Google keeps following their links.
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Whether the current page is one that should not be indexed. */
function apt_is_thin_page() {
	if ( is_tag() || is_author() || is_tax( 'mec_location' ) ) {
		return true;
	}
	$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	return 0 === strpos( $path, '/category-descriptors/' );
}

// noindex, follow (WordPress core robots tag; Yoast reads the same filter).
add_filter( 'wp_robots', function ( $robots ) {
	if ( apt_is_thin_page() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'], $robots['max-image-preview'] );
	}
	return $robots;
}, 20 );
add_filter( 'wpseo_robots', function ( $robots ) {
	return apt_is_thin_page() ? 'noindex, follow' : $robots;
} );

// Yoast sitemap: no tag or location sitemaps, no author sitemap, and no
// sitemap for a post type published under /category-descriptors/.
add_filter( 'wpseo_sitemap_exclude_taxonomy', function ( $exclude, $taxonomy ) {
	return in_array( $taxonomy, array( 'post_tag', 'mec_location' ), true ) ? true : $exclude;
}, 10, 2 );
add_filter( 'wpseo_sitemap_exclude_author', '__return_empty_array' );
add_filter( 'wpseo_sitemap_exclude_post_type', function ( $exclude, $post_type ) {
	$obj = get_post_type_object( $post_type );
	$slug = ( $obj && is_array( $obj->rewrite ) && isset( $obj->rewrite['slug'] ) ) ? $obj->rewrite['slug'] : $post_type;
	return ( 'category-descriptors' === trim( $slug, '/' ) || 'category-descriptors' === $post_type ) ? true : $exclude;
}, 10, 2 );
// WordPress's own sitemap (used only if Yoast's is off): same.
add_filter( 'wp_sitemaps_taxonomies', function ( $taxonomies ) {
	unset( $taxonomies['post_tag'], $taxonomies['mec_location'] );
	return $taxonomies;
} );
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );

// Feeds: keep the site's main feed; stop advertising one per tag, category,
// author and comment thread in every page's <head>.
add_action( 'init', function () {
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
} );

// Once: refresh the URL rules, so the removed /mec_location/… rule is gone
// (old addresses then return 404 and Google drops them).
add_action( 'init', function () {
	if ( '1' !== get_option( 'apt_rewrite_locations' ) ) {
		flush_rewrite_rules( false );
		update_option( 'apt_rewrite_locations', '1' );
	}
}, 99 );
