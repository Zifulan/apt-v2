<?php
/**
 * Tools > PDF search: status and a batch indexer for existing content.
 * Each request handles a few posts, then the page continues on its own, so it
 * stays within shared-hosting time limits.
 */

defined( 'ABSPATH' ) || exit;

define( 'APT_PDF_SEARCH_BATCH', 8 );

add_action( 'admin_menu', function () {
	add_management_page(
		__( 'PDF search', 'apt-pdf-search' ),
		__( 'PDF search', 'apt-pdf-search' ),
		'manage_options',
		'apt-pdf-search',
		'apt_pdf_search_admin_page'
	);
} );

/** Counts for the status table. */
function apt_pdf_search_stats() {
	global $wpdb;
	$indexed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", APT_PDF_SEARCH_TEXT ) );
	$states  = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s", APT_PDF_SEARCH_STATE ) );
	$pdfs    = 0;
	$failed  = array();
	foreach ( $states as $raw ) {
		$state = maybe_unserialize( $raw );
		if ( ! is_array( $state ) ) {
			continue;
		}
		foreach ( $state as $url => $s ) {
			$pdfs++;
			if ( ! empty( $s['error'] ) ) {
				$failed[ $url ] = $s['error'];
			}
		}
	}
	$candidates = apt_pdf_search_candidate_count();
	return compact( 'indexed', 'pdfs', 'failed', 'candidates' );
}

function apt_pdf_search_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$running = isset( $_GET['run'] ) && check_admin_referer( 'apt_pdf_search_run' );
	$force   = ! empty( $_GET['force'] );
	$offset  = isset( $_GET['offset'] ) ? max( 0, (int) $_GET['offset'] ) : 0;

	echo '<div class="wrap"><h1>' . esc_html__( 'PDF search', 'apt-pdf-search' ) . '</h1>';
	echo '<p>' . esc_html__( 'Lets the site search find statements by the text inside their attached PDFs. New and edited posts are indexed automatically when saved; use this page once to index existing content, or again after replacing PDF files.', 'apt-pdf-search' ) . '</p>';
	echo '<p>' . esc_html__( 'Only PDFs stored on this site and linked from published content without a password are read. Anything marked "limited" (slug, title, category, parent page or file path) is skipped.', 'apt-pdf-search' ) . '</p>';

	if ( $running ) {
		$ids     = apt_pdf_search_candidate_ids( $offset, APT_PDF_SEARCH_BATCH );
		$results = array();
		foreach ( $ids as $id ) {
			$results[] = get_the_title( $id ) . ': ' . apt_pdf_search_index_post( $id, $force );
		}
		$total = apt_pdf_search_stats()['candidates'];
		$done  = min( $total, $offset + count( $ids ) );
		update_option( 'apt_pdf_search_progress', array( 'done' => $done, 'total' => $total, 'time' => time() ), false );
		echo '<h2>' . esc_html( sprintf( __( 'Indexing: %1$d of %2$d posts and pages with PDF links', 'apt-pdf-search' ), $done, $total ) ) . '</h2>';
		echo '<ul>';
		foreach ( $results as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul>';
		if ( count( $ids ) === APT_PDF_SEARCH_BATCH ) {
			$next = wp_nonce_url(
				add_query_arg( array( 'page' => 'apt-pdf-search', 'run' => 1, 'force' => $force ? 1 : 0, 'offset' => $offset + APT_PDF_SEARCH_BATCH ), admin_url( 'tools.php' ) ),
				'apt_pdf_search_run'
			);
			echo '<p><a class="button" id="apt-pdf-next" href="' . esc_url( $next ) . '">' . esc_html__( 'Continue', 'apt-pdf-search' ) . '</a> ' . esc_html__( 'Continuing automatically…', 'apt-pdf-search' ) . '</p>';
			echo '<script>setTimeout(function(){ location.replace(document.getElementById("apt-pdf-next").href); }, 400);</script>';
			echo '</div>';
			return;
		}
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Done.', 'apt-pdf-search' ) . '</p></div>';
	}

	$stats = apt_pdf_search_stats();
	echo '<table class="widefat striped" style="max-width:640px"><tbody>';
	echo '<tr><th>' . esc_html__( 'Posts and pages that link to a PDF', 'apt-pdf-search' ) . '</th><td>' . (int) $stats['candidates'] . '</td></tr>';
	echo '<tr><th>' . esc_html__( 'Searchable (PDF text indexed)', 'apt-pdf-search' ) . '</th><td>' . (int) $stats['indexed'] . '</td></tr>';
	echo '<tr><th>' . esc_html__( 'PDF files read', 'apt-pdf-search' ) . '</th><td>' . (int) $stats['pdfs'] . '</td></tr>';
	echo '<tr><th>' . esc_html__( 'PDFs with no readable text', 'apt-pdf-search' ) . '</th><td>' . count( $stats['failed'] ) . '</td></tr>';
	$progress = get_option( 'apt_pdf_search_progress' );
	if ( is_array( $progress ) ) {
		echo '<tr><th>' . esc_html__( 'Last indexing run', 'apt-pdf-search' ) . '</th><td>' . esc_html( sprintf( '%d / %d', $progress['done'], $progress['total'] ) ) . ' &middot; ' . esc_html( wp_date( 'j M Y H:i', $progress['time'] ) ) . '</td></tr>';
	}
	echo '</tbody></table>';
	if ( ! is_array( $progress ) ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Indexing has not been run yet: existing PDFs are not searchable until you click "Index PDFs".', 'apt-pdf-search' ) . '</p></div>';
	} elseif ( $progress['done'] < $progress['total'] ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'The last indexing run stopped before the end (the oldest posts come last). Click "Index PDFs" again; already indexed posts are skipped quickly.', 'apt-pdf-search' ) . '</p></div>';
	}

	if ( $stats['failed'] ) {
		echo '<details style="margin-top:12px"><summary>' . esc_html__( 'Show PDFs with no readable text (often scanned images)', 'apt-pdf-search' ) . '</summary><ul>';
		foreach ( $stats['failed'] as $url => $error ) {
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( basename( $url ) ) . '</a>: ' . esc_html( $error ) . '</li>';
		}
		echo '</ul></details>';
	}

	$run   = wp_nonce_url( add_query_arg( array( 'page' => 'apt-pdf-search', 'run' => 1 ), admin_url( 'tools.php' ) ), 'apt_pdf_search_run' );
	$force = wp_nonce_url( add_query_arg( array( 'page' => 'apt-pdf-search', 'run' => 1, 'force' => 1 ), admin_url( 'tools.php' ) ), 'apt_pdf_search_run' );
	echo '<p style="margin-top:16px"><a class="button button-primary" href="' . esc_url( $run ) . '">' . esc_html__( 'Index PDFs', 'apt-pdf-search' ) . '</a> ';
	echo '<a class="button" href="' . esc_url( $force ) . '">' . esc_html__( 'Re-read all PDFs', 'apt-pdf-search' ) . '</a></p>';

	apt_pdf_search_check_tool();
	echo '</div>';
}

/**
 * "Check a statement": shows, step by step, why a post is or isn't found.
 */
function apt_pdf_search_check_tool() {
	$target = isset( $_GET['check'] ) ? sanitize_text_field( wp_unslash( $_GET['check'] ) ) : '';
	$phrase = isset( $_GET['phrase'] ) ? sanitize_text_field( wp_unslash( $_GET['phrase'] ) ) : '';
	if ( ( '' !== $target || '' !== $phrase ) && ! wp_verify_nonce( isset( $_GET['_aptcheck'] ) ? sanitize_key( $_GET['_aptcheck'] ) : '', 'apt_pdf_search_check' ) ) {
		$target = '';
		$phrase = '';
	}

	echo '<h2 style="margin-top:32px">' . esc_html__( 'Check a statement', 'apt-pdf-search' ) . '</h2>';
	echo '<p>' . esc_html__( 'Paste the statement\'s address (or post ID) and a phrase from its PDF to see where it is found or why not.', 'apt-pdf-search' ) . '</p>';
	echo '<form method="get" action="' . esc_url( admin_url( 'tools.php' ) ) . '">';
	echo '<input type="hidden" name="page" value="apt-pdf-search">';
	wp_nonce_field( 'apt_pdf_search_check', '_aptcheck', false );
	echo '<p><label>' . esc_html__( 'Statement URL or ID', 'apt-pdf-search' ) . '<br><input type="text" class="large-text" name="check" value="' . esc_attr( $target ) . '"></label></p>';
	echo '<p><label>' . esc_html__( 'Phrase', 'apt-pdf-search' ) . '<br><input type="text" class="regular-text" name="phrase" value="' . esc_attr( $phrase ) . '"></label></p>';
	echo '<p><button class="button">' . esc_html__( 'Check', 'apt-pdf-search' ) . '</button></p></form>';

	if ( '' === $target && '' === $phrase ) {
		return;
	}

	$post_id = 0;
	if ( '' !== $target ) {
		$post_id = ctype_digit( $target ) ? (int) $target : url_to_postid( $target );
		if ( ! $post_id ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'No post or page found at that address.', 'apt-pdf-search' ) . '</p></div>';
		}
	}

	if ( $post_id && isset( $_GET['reindex'] ) ) {
		$result = apt_pdf_search_index_post( $post_id, true );
		echo '<div class="notice notice-info inline"><p>' . esc_html( sprintf( __( 'Re-indexed: %s', 'apt-pdf-search' ), $result ) ) . '</p></div>';
	}

	$rows = array();
	if ( $post_id ) {
		$post = get_post( $post_id );
		$rows[] = array( 'Post', $post->post_title . ' (#' . $post_id . ', ' . $post->post_type . ', ' . $post->post_status . ')' );
		$why    = apt_pdf_search_why_not_indexable( $post );
		$rows[] = array( 'Can be indexed?', '' === $why ? 'Yes' : 'No: ' . $why );
		$links  = apt_pdf_search_explain_links( $post );
		if ( ! $links ) {
			$rows[] = array( 'PDF links in the content', 'None found in the post content.' );
		}
		foreach ( $links as $url => $reason ) {
			$rows[] = array( 'PDF link', $url . ' &rarr; ' . ( '' === $reason ? 'read' : 'skipped: ' . $reason ) );
		}
		$state = get_post_meta( $post_id, APT_PDF_SEARCH_STATE, true );
		if ( is_array( $state ) ) {
			foreach ( $state as $url => $s ) {
				$rows[] = array( 'Indexed file', basename( $url ) . ': ' . ( ! empty( $s['error'] ) ? 'error: ' . $s['error'] : number_format_i18n( (int) $s['chars'] ) . ' characters' ) );
			}
		}
		$text   = (string) get_post_meta( $post_id, APT_PDF_SEARCH_TEXT, true );
		$rows[] = array( 'Stored PDF text', '' === $text ? 'None: this post is not indexed yet.' : number_format_i18n( mb_strlen( $text ) ) . ' characters' );
		if ( '' !== $phrase && '' !== $text ) {
			$rows[] = array( 'Phrase in stored text?', false !== mb_stripos( $text, $phrase ) ? 'Yes' : 'No (all words must appear; check the spelling)' );
		}
	}

	if ( '' !== $phrase ) {
		$q     = new WP_Query( array( 's' => $phrase, 'apt_pdf_search' => true, 'posts_per_page' => 20, 'post_type' => 'any', 'post_status' => 'publish', 'fields' => 'ids', 'no_found_rows' => true ) );
		$found = $q->posts;
		$rows[] = array( 'Search for the phrase', $found ? count( $found ) . ' result(s): ' . implode( ', ', array_map( function ( $id ) { return get_the_title( $id ) . ' (#' . $id . ')'; }, array_slice( $found, 0, 8 ) ) ) : 'No results.' );
		if ( $post_id ) {
			$rows[] = array( 'Is this post in the results?', in_array( $post_id, array_map( 'intval', $found ), true ) ? 'Yes. If the site still does not show it, a cache (e.g. Sucuri or a caching plugin) may be serving an older results page: clear it.' : 'No' );
		}
	}

	echo '<table class="widefat striped" style="max-width:960px"><tbody>';
	foreach ( $rows as $r ) {
		echo '<tr><th style="width:220px">' . esc_html( $r[0] ) . '</th><td>' . wp_kses( $r[1], array() ) . '</td></tr>';
	}
	echo '</tbody></table>';

	if ( $post_id ) {
		$again = wp_nonce_url( add_query_arg( array( 'page' => 'apt-pdf-search', 'check' => $post_id, 'phrase' => $phrase, 'reindex' => 1 ), admin_url( 'tools.php' ) ), 'apt_pdf_search_check', '_aptcheck' );
		echo '<p><a class="button" href="' . esc_url( $again ) . '">' . esc_html__( 'Re-index this post now', 'apt-pdf-search' ) . '</a></p>';
	}
}
