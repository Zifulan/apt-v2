<?php
/**
 * Small site settings that plugins used to do.
 *
 * 1. No emoji script (was "Disable Emojis"): WordPress adds a script and
 *    styles to every page to draw emojis as images; modern browsers draw them
 *    themselves.
 * 2. Admin area for editors only (was "Remove Dashboard Access"): users who
 *    cannot edit posts, such as the shared LimitedAccess account, are sent to
 *    the home page from the admin area, don't see the admin bar, and can't
 *    change their account (password, email) through the REST API. So nobody
 *    using the shared account can lock the others out. Background requests
 *    (admin-ajax.php, admin-post.php) keep working for the public pages that
 *    use them, e.g. the news archive's "load more".
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- 1. No emoji script ---------- */

add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );   // WordPress 6.4+
	remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
} );
add_filter( 'tiny_mce_plugins', function ( $plugins ) {
	return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
} );
add_filter( 'wp_resource_hints', function ( $urls, $relation ) { // no DNS prefetch for the emoji images
	if ( 'dns-prefetch' !== $relation ) {
		return $urls;
	}
	return array_values( array_filter( (array) $urls, function ( $u ) {
		$href = is_array( $u ) ? ( $u['href'] ?? '' ) : (string) $u;
		return false === strpos( $href, '/images/core/emoji/' );
	} ) );
}, 10, 2 );

/* ---------- 2. Admin area for editors only ---------- */

/** Whether the current user may use the admin area. */
function apt_can_use_admin() {
	return current_user_can( 'edit_posts' );
}

add_action( 'admin_init', function () {
	if ( apt_can_use_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}
	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';
	if ( 'admin-post.php' === $script ) {
		return; // form handlers used by public pages
	}
	wp_safe_redirect( home_url( '/' ) );
	exit;
} );

add_filter( 'show_admin_bar', function ( $show ) {
	return $show && apt_can_use_admin();
} );

// No changes to user accounts through the REST API for non-editors.
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
	if ( null !== $result || ! is_user_logged_in() || apt_can_use_admin() ) {
		return $result;
	}
	if ( 'GET' !== $request->get_method() && 0 === strpos( $request->get_route(), '/wp/v2/users' ) ) {
		return new WP_Error( 'rest_forbidden', __( 'This account cannot be changed here.', 'akseki-vi' ), array( 'status' => 403 ) );
	}
	return $result;
}, 10, 3 );
