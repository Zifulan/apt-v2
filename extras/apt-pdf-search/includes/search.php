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
	foreach ( $terms as $term ) {
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
