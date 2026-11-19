<?php
/**
 * Front-end search: also match the stored PDF text, and offer a snippet.
 */

defined( 'ABSPATH' ) || exit;

/**
 * WordPress builds one "(posts.post_content LIKE '%term%')" clause per search
 * term. Extend each with "OR the post's PDF text LIKE the same pattern", reusing
 * WordPress's own escaped pattern so quoting and wildcards stay identical.
 * Excluded terms ("-word", NOT LIKE) are left alone.
 */
add_filter( 'posts_search', function ( $search, $query ) {
	// Any front-end search query (not only the main one: themes and search
	// plugins sometimes run their own), plus the admin "Check a statement" test.
	$forced = (bool) $query->get( 'apt_pdf_search' );
	if ( '' === $search || '' === (string) $query->get( 's' ) || ( is_admin() && ! $forced ) ) {
		return $search;
	}
	global $wpdb;
	$pattern = '/\(' . preg_quote( $wpdb->posts, '/' ) . '\.post_content LIKE (\'(?:[^\'\\\\]|\\\\.)*\')\)/';
	return preg_replace_callback(
		$pattern,
		function ( $m ) use ( $wpdb ) {
			return '(' . $wpdb->posts . '.post_content LIKE ' . $m[1]
				. ' OR EXISTS (SELECT 1 FROM ' . $wpdb->postmeta . ' apt_pdf'
				. ' WHERE apt_pdf.post_id = ' . $wpdb->posts . '.ID'
				. " AND apt_pdf.meta_key = '" . APT_PDF_SEARCH_TEXT . "'"
				. ' AND apt_pdf.meta_value LIKE ' . $m[1] . '))';
		},
		$search
	);
}, 10, 2 );

/**
 * The whole search as one phrase ("welcome constructive engagement"), or ''
 * when it is a single word. Quotes and excluded "-words" are dropped.
 */
function apt_pdf_search_phrase( $s ) {
	$words = preg_split( '/\s+/u', trim( str_replace( array( '"', '“', '”' ), ' ', (string) $s ) ) );
	$words = array_values( array_filter( $words, function ( $w ) {
		return '' !== $w && '-' !== $w[0];
	} ) );
	return count( $words ) > 1 ? implode( ' ', $words ) : '';
}

/**
 * Ranking: when the search has several words, put results that contain the
 * whole phrase first: in the title, then in the page text or its PDF. Within
 * each group WordPress's own relevance order still applies.
 */
add_filter( 'posts_search_orderby', function ( $orderby, $query ) {
	$forced = (bool) $query->get( 'apt_pdf_search' );
	if ( '' === (string) $query->get( 's' ) || ( is_admin() && ! $forced ) ) {
		return $orderby;
	}
	$phrase = apt_pdf_search_phrase( $query->get( 's' ) );
	if ( '' === $phrase ) {
		return $orderby;
	}
	global $wpdb;
	$like = '%' . $wpdb->esc_like( $phrase ) . '%';
	$case = $wpdb->prepare(
		"(CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN 0
		      WHEN {$wpdb->posts}.post_content LIKE %s
		        OR EXISTS ( SELECT 1 FROM {$wpdb->postmeta} apt_pdf_o
		                    WHERE apt_pdf_o.post_id = {$wpdb->posts}.ID
		                      AND apt_pdf_o.meta_key = %s
		                      AND apt_pdf_o.meta_value LIKE %s ) THEN 1
		      ELSE 2 END) ASC",
		$like,
		$like,
		APT_PDF_SEARCH_TEXT,
		$like
	);
	return '' === trim( (string) $orderby ) ? $case : $case . ', ' . $orderby;
}, 10, 2 );

/**
 * For the current search: a short passage from the post's PDF text around the
 * first search term, but only when the term is NOT already in the post's title,
 * excerpt or content (so it explains a match the page itself doesn't show).
 *
 * @return array{text:string,terms:string[]}|null
 */
function apt_pdf_search_snippet( $post = null, $radius = 120 ) {
	$post = get_post( $post );
	if ( ! $post || ! is_search() ) {
		return null;
	}
	$text = (string) get_post_meta( $post->ID, APT_PDF_SEARCH_TEXT, true );
	if ( '' === $text ) {
		return null;
	}
	$terms = array_filter( (array) get_query_var( 'search_terms' ), function ( $t ) {
		return '' !== $t && '-' !== $t[0];
	} );
	if ( ! $terms ) {
		return null;
	}
	$page = $post->post_title . ' ' . $post->post_excerpt . ' ' . wp_strip_all_tags( $post->post_content );
	$pos  = false;
	$hit  = '';

	// Prefer the whole phrase when the PDF contains it (and the page doesn't).
	$phrase = apt_pdf_search_phrase( get_query_var( 's' ) );
	if ( '' !== $phrase && false === mb_stripos( $page, $phrase ) ) {
		$p = mb_stripos( $text, $phrase );
		if ( false !== $p ) {
			$pos   = $p;
			$hit   = $phrase;
			$terms = array( $phrase );
		}
	}

	foreach ( false === $pos ? $terms : array() as $term ) {
		if ( false !== mb_stripos( $page, $term ) ) {
			continue; // visible on the page already
		}
		$p = mb_stripos( $text, $term );
		if ( false !== $p && ( false === $pos || $p < $pos ) ) {
			$pos = $p;
			$hit = $term;
		}
	}
	if ( false === $pos ) {
		return null;
	}
	// Cut on word boundaries around the match.
	$start = max( 0, $pos - $radius );
	$end   = min( mb_strlen( $text ), $pos + mb_strlen( $hit ) + $radius );
	if ( $start > 0 ) {
		$space = mb_strpos( $text, ' ', $start );
		$start = ( false !== $space && $space < $pos ) ? $space + 1 : $start;
	}
	if ( $end < mb_strlen( $text ) ) {
		$space = mb_strrpos( mb_substr( $text, 0, $end ), ' ' );
		$end   = ( false !== $space && $space > $pos ) ? $space : $end;
	}
	$snippet = ( $start > 0 ? '…' : '' ) . mb_substr( $text, $start, $end - $start ) . ( $end < mb_strlen( $text ) ? '…' : '' );
	return array( 'text' => $snippet, 'terms' => array_values( $terms ) );
}

/**
 * The snippet as escaped HTML with the search terms wrapped in <mark>.
 */
function apt_pdf_search_snippet_html( $post = null ) {
	$s = apt_pdf_search_snippet( $post );
	if ( ! $s ) {
		return '';
	}
	$html  = esc_html( $s['text'] );
	$quote = array_map( function ( $t ) {
		return preg_quote( esc_html( $t ), '/' );
	}, $s['terms'] );
	return preg_replace( '/(' . implode( '|', $quote ) . ')/iu', '<mark>$1</mark>', $html );
}
