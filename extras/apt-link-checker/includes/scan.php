<?php
/**
 * Extracting links from content and checking them.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Links in a post's content: href/src attributes and shortcode url="…"
 * attributes. Skips anchors, mailto:, tel:, javascript: and data: links.
 *
 * @return string[] raw addresses, unique.
 */
function apt_lc_extract_links( $content ) {
	$content = str_replace( '\\/', '/', (string) $content );
	$links   = array();
	if ( preg_match_all( '#\b(?:href|src|url)\s*=\s*(["\'])(.*?)\1#is', $content, $m ) ) {
		$links = $m[2];
	}
	$links = array_map( function ( $l ) {
		return trim( html_entity_decode( $l, ENT_QUOTES, 'UTF-8' ) );
	}, $links );
	return array_values( array_unique( array_filter( $links, function ( $l ) {
		return '' !== $l && ! preg_match( '#^(?:\#|mailto:|tel:|javascript:|data:|sms:|whatsapp:)#i', $l );
	} ) ) );
}

/** Whether an address points at this site (or is site-relative). */
function apt_lc_is_internal( $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! $host ) {
		return 0 !== strpos( $url, '//' );
	}
	$site = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	return 0 === strcasecmp( preg_replace( '/^www\./i', '', $host ), preg_replace( '/^www\./i', '', $site ) );
}

/**
 * Check one address.
 *
 * @return array{status:string,detail:string} status ok|broken|warning
 */
function apt_lc_check_url( $url ) {
	// Malformed: HTML or spaces inside the address, or no scheme on an external-looking link.
	if ( preg_match( '#[<>"\s]|&lt;|&gt;#', $url ) ) {
		return array( 'status' => 'broken', 'detail' => 'Malformed address (contains HTML or spaces).' );
	}

	if ( apt_lc_is_internal( $url ) ) {
		$path    = rawurldecode( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		$uploads = wp_upload_dir( null, false );
		$base    = (string) wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );

		// A file in uploads: check it on disk.
		if ( '' !== $base && 0 === strpos( $path, $base . '/' ) ) {
			$file     = $uploads['basedir'] . substr( $path, strlen( $base ) );
			$real     = realpath( $file );
			$base_dir = realpath( $uploads['basedir'] );
			if ( ! $real || ! $base_dir || 0 !== strpos( $real, $base_dir . DIRECTORY_SEPARATOR ) || ! is_file( $real ) ) {
				return array( 'status' => 'broken', 'detail' => 'File not found in uploads.' );
			}
			$size = filesize( $real );
			if ( 0 === (int) $size ) {
				return array( 'status' => 'broken', 'detail' => 'File is empty (0 bytes).' );
			}
			if ( preg_match( '/\.pdf$/i', $real ) ) {
				$head = (string) @file_get_contents( $real, false, null, 0, 1024 );
				if ( false === strpos( $head, '%PDF-' ) ) {
					return array( 'status' => 'broken', 'detail' => 'Not a real PDF (probably a web page saved as .pdf).' );
				}
			}
			return array( 'status' => 'ok', 'detail' => size_format( $size ) );
		}

		// A page on this site: resolve it without a web request when possible.
		$absolute = wp_parse_url( $url, PHP_URL_HOST ) ? $url : home_url( $url );
		$post_id  = url_to_postid( $absolute );
		if ( $post_id ) {
			$status = get_post_status( $post_id );
			if ( 'publish' === $status ) {
				return array( 'status' => 'ok', 'detail' => 'Published content.' );
			}
			return array( 'status' => 'broken', 'detail' => 'Links to content that is ' . $status . '.' );
		}
		return apt_lc_http_check( $absolute );
	}

	if ( ! preg_match( '#^https?://#i', $url ) ) {
		$url = 'https:' . ( 0 === strpos( $url, '//' ) ? '' : '//' ) . ltrim( $url, '/' );
	}
	return apt_lc_http_check( $url );
}

/** HEAD (then GET if the server rejects HEAD) and classify the answer. */
function apt_lc_http_check( $url ) {
	$args = array(
		'timeout'     => APT_LC_TIMEOUT,
		'redirection' => 5,
		'user-agent'  => 'Mozilla/5.0 (compatible; APT-Link-Checker; +' . home_url( '/' ) . ')',
		'sslverify'   => true,
	);
	$res = wp_remote_head( $url, $args );
	$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	if ( is_wp_error( $res ) || in_array( $code, array( 403, 405, 501 ), true ) ) {
		$args['limit_response_size'] = 65536;
		$res  = wp_remote_get( $url, $args );
		$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	}
	if ( is_wp_error( $res ) ) {
		return array( 'status' => 'warning', 'detail' => 'No answer: ' . $res->get_error_message() );
	}
	if ( $code >= 200 && $code < 400 ) {
		return array( 'status' => 'ok', 'detail' => 'HTTP ' . $code );
	}
	if ( in_array( $code, array( 401, 403, 429 ), true ) || $code >= 500 ) {
		// Login walls, bot blocks and temporary server errors: check by hand.
		return array( 'status' => 'warning', 'detail' => 'HTTP ' . $code . ' (may be a bot block or a temporary error)' );
	}
	return array( 'status' => 'broken', 'detail' => 'HTTP ' . $code );
}

/** Published posts and pages, newest first, in batches. */
function apt_lc_post_ids( $offset, $limit ) {
	global $wpdb;
	$types = array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
	if ( ! $types ) {
		return array();
	}
	$in = implode( ',', array_fill( 0, count( $types ), '%s' ) );
	return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($in) ORDER BY ID DESC LIMIT %d OFFSET %d",
		array_merge( $types, array( $limit, $offset ) )
	) ) );
}

/** Number of published posts and pages to scan. */
function apt_lc_post_count() {
	global $wpdb;
	$types = array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
	if ( ! $types ) {
		return 0;
	}
	$in = implode( ',', array_fill( 0, count( $types ), '%s' ) );
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($in)", $types ) );
}

/**
 * Scan a batch of posts. Each address is checked once per run, however many
 * posts use it; results remember which posts link to it.
 *
 * @param bool $fresh  Re-check addresses already checked in the last week.
 * @return int number of posts scanned
 */
function apt_lc_scan_batch( $offset, $limit, $fresh = false ) {
	$results = get_option( APT_LC_RESULTS, array() );
	$results = is_array( $results ) ? $results : array();
	$ids     = apt_lc_post_ids( $offset, $limit );
	$now     = time();

	foreach ( $ids as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			continue;
		}
		foreach ( apt_lc_extract_links( $post->post_content ) as $url ) {
			$known = isset( $results[ $url ] ) ? $results[ $url ] : null;
			if ( ! $known || $fresh || ( $now - (int) $known['checked'] ) > APT_LC_RECHECK ) {
				$check   = apt_lc_check_url( $url );
				$results[ $url ] = array(
					'status'  => $check['status'],
					'detail'  => $check['detail'],
					'checked' => $now,
					'posts'   => $known ? (array) $known['posts'] : array(),
				);
			}
			if ( ! in_array( $post_id, $results[ $url ]['posts'], true ) ) {
				$results[ $url ]['posts'][] = $post_id;
			}
		}
	}
	update_option( APT_LC_RESULTS, $results, false );
	return count( $ids );
}

// Weekly background re-check, a few batches per run so it stays light.
add_action( 'apt_lc_cron', function () {
	$progress = get_option( APT_LC_PROGRESS, array() );
	$offset   = isset( $progress['cron_offset'] ) ? (int) $progress['cron_offset'] : 0;
	for ( $i = 0; $i < 5; $i++ ) {
		$n = apt_lc_scan_batch( $offset, APT_LC_BATCH );
		$offset += $n;
		if ( $n < APT_LC_BATCH ) {
			$offset = 0;
			$progress['last_full'] = time();
			break;
		}
	}
	$progress['cron_offset'] = $offset;
	update_option( APT_LC_PROGRESS, $progress, false );
} );

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'apt_lc_cron' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'apt_lc_cron' );
	}
} );

register_deactivation_hook( dirname( __DIR__ ) . '/apt-link-checker.php', function () {
	wp_clear_scheduled_hook( 'apt_lc_cron' );
} );
