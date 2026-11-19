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
	$candidates = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type NOT IN ('attachment','revision','nav_menu_item') AND post_status IN ('publish','private','future','draft','pending') AND post_content LIKE %s",
		'%' . $wpdb->esc_like( '.pdf' ) . '%'
	) );
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
	echo '</tbody></table>';

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
	echo '</div>';
}
