<?php
/**
 * Tools > Link checker: summary, full scan in small batches, broken list.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_management_page(
		__( 'Link checker', 'apt-link-checker' ),
		__( 'Link checker', 'apt-link-checker' ),
		'manage_options',
		'apt-link-checker',
		'apt_lc_admin_page'
	);
} );

function apt_lc_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap"><h1>' . esc_html__( 'Link checker', 'apt-link-checker' ) . '</h1>';
	echo '<p>' . esc_html__( 'Checks every link and file in published posts and pages: files in uploads must exist and PDFs must be real PDFs; pages must be published; other websites must answer. Links are re-checked weekly in the background. Nothing in your content is changed.', 'apt-link-checker' ) . '</p>';

	// Full scan, a batch per request, continuing on its own.
	if ( isset( $_GET['scan'] ) && check_admin_referer( 'apt_lc_scan' ) ) {
		$offset = isset( $_GET['offset'] ) ? max( 0, (int) $_GET['offset'] ) : 0;
		if ( 0 === $offset ) {
			update_option( APT_LC_RESULTS, array(), false ); // start clean: drop links no longer used
		}
		$started = microtime( true );
		$handled = 0;
		do {
			$n        = apt_lc_scan_batch( $offset + $handled, APT_LC_BATCH, true );
			$handled += $n;
		} while ( APT_LC_BATCH === $n && microtime( true ) - $started < APT_LC_SECONDS );
		$total = apt_lc_post_count();
		$done  = min( $total, $offset + $handled );
		echo '<h2>' . esc_html( sprintf( __( 'Checking: %1$d of %2$d posts and pages', 'apt-link-checker' ), $done, $total ) ) . '</h2>';
		if ( APT_LC_BATCH === $n ) { // stopped on time, not at the end
			$next = wp_nonce_url( add_query_arg( array( 'page' => 'apt-link-checker', 'scan' => 1, 'offset' => $offset + $handled ), admin_url( 'tools.php' ) ), 'apt_lc_scan' );
			echo '<p><a class="button" id="apt-lc-next" href="' . esc_url( $next ) . '">' . esc_html__( 'Continue', 'apt-link-checker' ) . '</a> ' . esc_html__( 'Continuing automatically in a few seconds… keep this tab open.', 'apt-link-checker' ) . '</p>';
			echo '<script>setTimeout(function(){ location.replace(document.getElementById("apt-lc-next").href); }, ' . (int) APT_LC_PAUSE . ');</script></div>';
			return;
		}
		$progress              = get_option( APT_LC_PROGRESS, array() );
		$progress['last_full'] = time();
		update_option( APT_LC_PROGRESS, $progress, false );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Done.', 'apt-link-checker' ) . '</p></div>';
	}

	$results = get_option( APT_LC_RESULTS, array() );
	$results = is_array( $results ) ? $results : array();
	$groups  = array( 'broken' => array(), 'warning' => array(), 'ok' => array() );
	foreach ( $results as $url => $r ) {
		$groups[ isset( $groups[ $r['status'] ] ) ? $r['status'] : 'warning' ][ $url ] = $r;
	}
	$progress = get_option( APT_LC_PROGRESS, array() );

	echo '<table class="widefat striped" style="max-width:640px"><tbody>';
	echo '<tr><th>' . esc_html__( 'Broken', 'apt-link-checker' ) . '</th><td><strong>' . count( $groups['broken'] ) . '</strong></td></tr>';
	echo '<tr><th>' . esc_html__( 'Check by hand (no answer, blocked or server error)', 'apt-link-checker' ) . '</th><td>' . count( $groups['warning'] ) . '</td></tr>';
	echo '<tr><th>' . esc_html__( 'Working', 'apt-link-checker' ) . '</th><td>' . count( $groups['ok'] ) . '</td></tr>';
	echo '<tr><th>' . esc_html__( 'Last full check', 'apt-link-checker' ) . '</th><td>' . ( ! empty( $progress['last_full'] ) ? esc_html( wp_date( 'j M Y H:i', $progress['last_full'] ) ) : esc_html__( 'Not yet: click "Check all links".', 'apt-link-checker' ) ) . '</td></tr>';
	echo '</tbody></table>';

	$scan = wp_nonce_url( add_query_arg( array( 'page' => 'apt-link-checker', 'scan' => 1 ), admin_url( 'tools.php' ) ), 'apt_lc_scan' );
	echo '<p style="margin-top:16px"><a class="button button-primary" href="' . esc_url( $scan ) . '">' . esc_html__( 'Check all links', 'apt-link-checker' ) . '</a> <span class="description">' . esc_html__( 'Takes a few minutes; links to other websites are the slow part.', 'apt-link-checker' ) . '</span></p>';

	apt_lc_render_list( __( 'Broken', 'apt-link-checker' ), $groups['broken'], true );
	apt_lc_render_list( __( 'Check by hand', 'apt-link-checker' ), $groups['warning'], false );
	echo '</div>';
}

/** One table: address, problem, where it is used (with edit links). */
function apt_lc_render_list( $title, $items, $open ) {
	if ( ! $items ) {
		return;
	}
	// Files on this site first, then this site's pages, then other websites.
	uksort( $items, function ( $a, $b ) {
		return array( apt_lc_is_internal( $b ), $a ) <=> array( apt_lc_is_internal( $a ), $b );
	} );
	echo '<details' . ( $open ? ' open' : '' ) . ' style="margin-top:24px"><summary style="cursor:pointer"><h2 style="display:inline">' . esc_html( $title ) . ' (' . count( $items ) . ')</h2></summary>';
	echo '<table class="widefat striped" style="margin-top:8px"><thead><tr><th>' . esc_html__( 'Address', 'apt-link-checker' ) . '</th><th>' . esc_html__( 'Problem', 'apt-link-checker' ) . '</th><th>' . esc_html__( 'Used in', 'apt-link-checker' ) . '</th></tr></thead><tbody>';
	foreach ( $items as $url => $r ) {
		$used = array();
		foreach ( array_slice( (array) $r['posts'], 0, 5 ) as $pid ) {
			$used[] = '<a href="' . esc_url( get_edit_post_link( $pid ) ) . '">' . esc_html( wp_trim_words( get_the_title( $pid ), 10 ) ) . '</a>';
		}
		$more = count( (array) $r['posts'] ) > 5 ? ' ' . esc_html( sprintf( __( 'and %d more', 'apt-link-checker' ), count( $r['posts'] ) - 5 ) ) : '';
		echo '<tr><td style="word-break:break-all"><code>' . esc_html( $url ) . '</code></td><td>' . esc_html( $r['detail'] ) . '</td><td>' . implode( '<br>', $used ) . $more . '</td></tr>';
	}
	echo '</tbody></table></details>';
}
