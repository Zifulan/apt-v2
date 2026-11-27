<?php
/**
 * Tools → Redirects: send old or duplicate addresses to the right page.
 *
 * - Each redirect: an address on this site ("from", a path such as
 *   /gallery2/), where to send visitors ("to", a path or a full address),
 *   and the type: 301 (moved for good: Google moves the page's standing to
 *   the new address), 302 (temporary) or 410 (gone for good: no new
 *   address; Google drops it faster than a 404).
 * - Redirects run before WordPress shows the page, so they also work for
 *   pages that still exist (duplicates).
 * - When a published page's address changes (its slug or parent), a 301
 *   from the old address is added automatically. WordPress already does
 *   this for posts, not for pages.
 * - "Not found" log: the most requested addresses that gave a 404 (no
 *   files, admin or scanner noise), to turn into redirects.
 * - Saving clears WP Super Cache, which could otherwise keep serving a
 *   cached copy of a redirected page.
 *
 * Options: apt_redirects (list), apt_404_log (not autoloaded).
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** A path for comparing: lowercase, decoded, with a leading slash, no trailing slash, no query. */
function apt_rd_norm( $path ) {
	$path = (string) wp_parse_url( (string) $path, PHP_URL_PATH );
	$path = '/' . trim( rawurldecode( $path ), '/' );
	return mb_strtolower( $path, 'UTF-8' );
}

/** The redirects. */
function apt_rd_list() {
	$list = get_option( 'apt_redirects', array() );
	return is_array( $list ) ? array_values( $list ) : array();
}

/** Save the list (and empty the page cache). */
function apt_rd_save( $list ) {
	update_option( 'apt_redirects', array_values( $list ), true );
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
}

/** Add or replace a redirect; returns '' or an error message. */
function apt_rd_add( $from, $to, $code, $note = '' ) {
	$code = in_array( (int) $code, array( 301, 302, 410 ), true ) ? (int) $code : 301;
	$from = trim( (string) $from );
	if ( preg_match( '#^https?://#i', $from ) ) {
		$host = wp_parse_url( $from, PHP_URL_HOST );
		if ( strtolower( (string) $host ) !== strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
			return __( '"From" must be an address on this site.', 'akseki-vi' );
		}
	}
	$key = apt_rd_norm( $from );
	if ( '/' === $key || preg_match( '#^/(wp-admin|wp-login\.php|wp-json|wp-content|wp-includes)(/|$)#', $key ) ) {
		return __( 'This address cannot be redirected.', 'akseki-vi' );
	}
	$to = trim( (string) $to );
	if ( 410 !== $code ) {
		if ( '' === $to ) {
			return __( 'Enter where to send visitors.', 'akseki-vi' );
		}
		if ( '/' === $to[0] ) {
			$to = '/' . ltrim( preg_replace( '/[\s"\'<>]/', '', $to ), '/' );
		} else {
			$to = esc_url_raw( $to, array( 'http', 'https' ) );
			if ( '' === $to ) {
				return __( '"To" must be a path such as /gallery/ or a full address.', 'akseki-vi' );
			}
		}
		$same_site = '/' === $to[0] || strtolower( (string) wp_parse_url( $to, PHP_URL_HOST ) ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		if ( $same_site && apt_rd_norm( $to ) === $key ) {
			return __( '"From" and "To" are the same address.', 'akseki-vi' );
		}
	} else {
		$to = '';
	}
	$list = array_values( array_filter( apt_rd_list(), function ( $r ) use ( $key ) {
		return apt_rd_norm( $r['from'] ) !== $key;
	} ) );
	$list[] = array(
		'from'    => $key . ( false !== strpos( basename( $key ), '.' ) ? '' : '/' ), // /gallery2/, but /old-file.pdf
		'to'      => $to,
		'code'    => $code,
		'note'    => sanitize_text_field( $note ),
		'created' => time(),
		'hits'    => 0,
		'last'    => 0,
	);
	apt_rd_save( $list );
	// It is handled now: take it off the "Not found" log.
	$log = get_option( 'apt_404_log', array() );
	if ( is_array( $log ) && isset( $log[ $key ] ) ) {
		unset( $log[ $key ] );
		update_option( 'apt_404_log', $log, false );
	}
	return '';
}

/* ---------- Doing the redirects ---------- */

add_action( 'template_redirect', function () {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$key  = apt_rd_norm( $uri );
	$list = apt_rd_list();
	foreach ( $list as $i => $r ) {
		if ( apt_rd_norm( $r['from'] ) !== $key ) {
			continue;
		}
		$list[ $i ]['hits'] = (int) $r['hits'] + 1;
		$list[ $i ]['last'] = time();
		update_option( 'apt_redirects', $list, true );
		if ( 410 === (int) $r['code'] ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 410 );
			nocache_headers();
			return; // WordPress shows the "not found" page, with status 410
		}
		$to = '/' === $r['to'][0] ? home_url( $r['to'] ) : $r['to'];
		$q  = (string) wp_parse_url( $uri, PHP_URL_QUERY );
		if ( '' !== $q && false === strpos( $to, '?' ) ) {
			$to .= '?' . $q; // keep ?page=2 and the like
		}
		wp_redirect( $to, (int) $r['code'], 'apt-v2' ); // phpcs:ignore WordPress.Security.SafeRedirect -- the target is set by editors on purpose (may be another site)
		exit;
	}

	// Not found: remember it (addresses only; no files, admin or scanner noise).
	if ( is_404() ) {
		if ( preg_match( '#\.(php|asp|aspx|env|ini|sql|bak|zip|tar|gz|xml|txt|js|css|map|ico|png|jpe?g|gif|webp|svg|woff2?)$#i', $key )
			|| preg_match( '#^/(wp-|xmlrpc|cgi-bin|\.well-known|\.git|vendor|admin|phpmyadmin)#i', $key )
			|| strlen( $key ) > 200 ) {
			return;
		}
		$log = get_option( 'apt_404_log', array() );
		$log = is_array( $log ) ? $log : array();
		$ref = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$log[ $key ] = array(
			'hits' => ( isset( $log[ $key ]['hits'] ) ? (int) $log[ $key ]['hits'] : 0 ) + 1,
			'last' => time(),
			'ref'  => $ref ? $ref : ( isset( $log[ $key ]['ref'] ) ? $log[ $key ]['ref'] : '' ),
		);
		if ( count( $log ) > 300 ) { // keep the most requested
			uasort( $log, function ( $a, $b ) { return $b['hits'] <=> $a['hits']; } );
			$log = array_slice( $log, 0, 250, true );
		}
		update_option( 'apt_404_log', $log, false );
	}
}, 1 );

/* ---------- A page's address changes: redirect the old one ---------- */

add_action( 'post_updated', function ( $post_id, $after, $before ) {
	if ( 'page' !== $after->post_type || 'publish' !== $before->post_status || 'publish' !== $after->post_status ) {
		return;
	}
	if ( $before->post_name === $after->post_name && (int) $before->post_parent === (int) $after->post_parent ) {
		return;
	}
	// The old address, rebuilt from the old slug and parent.
	$old = get_page_uri( $before );
	$new = get_page_uri( $post_id );
	if ( $old && $new && apt_rd_norm( $old ) !== apt_rd_norm( $new ) ) {
		/* translators: %s: page title */
		apt_rd_add( '/' . trim( $old, '/' ) . '/', '/' . trim( $new, '/' ) . '/', 301, sprintf( __( 'Added automatically: the address of "%s" changed.', 'akseki-vi' ), $after->post_title ) );
	}
}, 10, 3 );

/* ---------- The screen ---------- */

add_action( 'admin_menu', function () {
	$hook = add_management_page( __( 'Redirects', 'akseki-vi' ), __( 'Redirects', 'akseki-vi' ), 'edit_others_posts', 'apt-redirects', 'apt_rd_page' );
	add_action( 'load-' . $hook, 'apt_rd_actions' );
} );

function apt_rd_actions() {
	if ( empty( $_REQUEST['apt_rd'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	check_admin_referer( 'apt_rd' );
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'akseki-vi' ) );
	}
	$do   = sanitize_key( wp_unslash( $_REQUEST['apt_rd'] ) );
	$args = array( 'page' => 'apt-redirects' );
	if ( 'add' === $do ) {
		$err = apt_rd_add(
			isset( $_POST['from'] ) ? wp_unslash( $_POST['from'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- checked in apt_rd_add()
			isset( $_POST['to'] ) ? wp_unslash( $_POST['to'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			isset( $_POST['code'] ) ? absint( $_POST['code'] ) : 301,
			isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '' // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		);
		$args['msg'] = $err ? 'error' : 'added';
		if ( $err ) {
			set_transient( 'apt_rd_error_' . get_current_user_id(), $err, 60 );
		}
	} elseif ( 'delete' === $do && isset( $_GET['from'] ) ) {
		$key = apt_rd_norm( wp_unslash( $_GET['from'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		apt_rd_save( array_filter( apt_rd_list(), function ( $r ) use ( $key ) { return apt_rd_norm( $r['from'] ) !== $key; } ) );
		$args['msg'] = 'deleted';
	} elseif ( 'forget' === $do && isset( $_GET['from'] ) ) {
		$log = get_option( 'apt_404_log', array() );
		unset( $log[ apt_rd_norm( wp_unslash( $_GET['from'] ) ) ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		update_option( 'apt_404_log', $log, false );
	} elseif ( 'clear' === $do ) {
		update_option( 'apt_404_log', array(), false );
	}
	wp_safe_redirect( add_query_arg( $args, admin_url( 'tools.php' ) ) );
	exit;
}

/** Problems with a redirect: chains (its target is redirected too) and loops. */
function apt_rd_warning( $r, $list ) {
	if ( 410 === (int) $r['code'] || '' === $r['to'] ) {
		return '';
	}
	$same_site = '/' === $r['to'][0] || strtolower( (string) wp_parse_url( $r['to'], PHP_URL_HOST ) ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	if ( ! $same_site ) {
		return '';
	}
	$to = apt_rd_norm( $r['to'] );
	foreach ( $list as $o ) {
		if ( apt_rd_norm( $o['from'] ) === $to ) {
			return apt_rd_norm( $o['to'] ) === apt_rd_norm( $r['from'] )
				? __( 'Loop: these two redirect to each other. Delete one.', 'akseki-vi' )
				/* translators: %s: address */
				: sprintf( __( 'Chain: %s is redirected too. Point this one straight to the final address.', 'akseki-vi' ), $o['to'] );
		}
	}
	return '';
}

function apt_rd_page() {
	$list = apt_rd_list();
	usort( $list, function ( $a, $b ) { return strcmp( $a['from'], $b['from'] ); } );
	$log = get_option( 'apt_404_log', array() );
	$log = is_array( $log ) ? $log : array();
	uasort( $log, function ( $a, $b ) { return $b['hits'] <=> $a['hits']; } );
	$msg   = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$pre   = isset( $_GET['prefill'] ) ? apt_rd_norm( wp_unslash( $_GET['prefill'] ) ) . '/' : ''; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
	$base  = admin_url( 'tools.php?page=apt-redirects' );
	$codes = array(
		301 => __( '301: moved for good', 'akseki-vi' ),
		302 => __( '302: temporary', 'akseki-vi' ),
		410 => __( '410: gone for good (no new address)', 'akseki-vi' ),
	);
	?>
	<div class="wrap apt-rd">
		<h1><?php esc_html_e( 'Redirects', 'akseki-vi' ); ?></h1>
		<p class="apt-rd-lead"><?php esc_html_e( 'Send old or duplicate addresses to the right page. Use 301 when a page has moved or two pages say the same thing: Google moves the old page\'s standing to the new address. Use 410 for a page that is gone with no replacement.', 'akseki-vi' ); ?></p>

		<?php if ( 'added' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Redirect saved. Open the old address to check it.', 'akseki-vi' ); ?></p></div>
		<?php elseif ( 'deleted' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Redirect deleted.', 'akseki-vi' ); ?></p></div>
		<?php elseif ( 'error' === $msg ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( (string) get_transient( 'apt_rd_error_' . get_current_user_id() ) ); ?></p></div>
		<?php endif; ?>

		<div class="postbox apt-rd-add"><h2 class="hndle"><?php esc_html_e( 'Add a redirect', 'akseki-vi' ); ?></h2><div class="inside">
			<form method="post" class="apt-rd-form">
				<?php wp_nonce_field( 'apt_rd' ); ?>
				<input type="hidden" name="apt_rd" value="add">
				<p><label for="apt-rd-from"><?php esc_html_e( 'From (old address)', 'akseki-vi' ); ?></label>
					<input type="text" id="apt-rd-from" name="from" value="<?php echo esc_attr( $pre ); ?>" placeholder="/gallery2/" required></p>
				<p><label for="apt-rd-to"><?php esc_html_e( 'To', 'akseki-vi' ); ?></label>
					<input type="text" id="apt-rd-to" name="to" placeholder="/gallery/"></p>
				<p><label for="apt-rd-code"><?php esc_html_e( 'Type', 'akseki-vi' ); ?></label>
					<select id="apt-rd-code" name="code"><?php foreach ( $codes as $c => $l ) { echo '<option value="' . (int) $c . '">' . esc_html( $l ) . '</option>'; } ?></select></p>
				<p class="apt-rd-wide"><label for="apt-rd-note"><?php esc_html_e( 'Note (optional)', 'akseki-vi' ); ?></label>
					<input type="text" id="apt-rd-note" name="note" placeholder="<?php esc_attr_e( 'e.g. duplicate of the Gallery page', 'akseki-vi' ); ?>"></p>
				<p><button class="button button-primary"><?php esc_html_e( 'Save redirect', 'akseki-vi' ); ?></button></p>
			</form>
			<p class="description"><?php esc_html_e( '"From" is a path on this site, e.g. /gallery2/ (a full address of this site also works). "To" is a path or a full address, also on another site. Saving the same "From" again replaces it.', 'akseki-vi' ); ?></p>
		</div></div>

		<h2><?php echo esc_html( sprintf( /* translators: %d: count */ __( 'Redirects (%d)', 'akseki-vi' ), count( $list ) ) ); ?></h2>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'From', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'To', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Type', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Used', 'akseki-vi' ); ?></th><th></th></tr></thead>
			<tbody>
			<?php if ( ! $list ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No redirects yet.', 'akseki-vi' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $list as $r ) : ?>
				<?php $warn = apt_rd_warning( $r, $list ); ?>
				<tr>
					<td><a href="<?php echo esc_url( home_url( $r['from'] ) ); ?>" target="_blank"><code><?php echo esc_html( $r['from'] ); ?></code></a><?php echo $r['note'] ? '<br><span class="description">' . esc_html( $r['note'] ) . '</span>' : ''; ?></td>
					<td><?php echo '' !== $r['to'] ? '<code>' . esc_html( $r['to'] ) . '</code>' : '&mdash;'; ?><?php echo $warn ? '<br><span class="apt-rd-warn">' . esc_html( $warn ) . '</span>' : ''; ?></td>
					<td><?php echo (int) $r['code']; ?></td>
					<td><?php echo esc_html( (int) $r['hits'] ? sprintf( /* translators: 1: count, 2: date */ _n( '%1$d time, last %2$s', '%1$d times, last %2$s', (int) $r['hits'], 'akseki-vi' ), (int) $r['hits'], wp_date( 'j M Y', $r['last'] ) ) : __( 'Not yet', 'akseki-vi' ) ); ?></td>
					<td><a class="apt-rd-del" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'apt_rd' => 'delete', 'from' => rawurlencode( $r['from'] ) ), $base ), 'apt_rd' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this redirect?', 'akseki-vi' ) ); ?>')"><?php esc_html_e( 'Delete', 'akseki-vi' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Not found (404)', 'akseki-vi' ); ?></h2>
		<p><?php esc_html_e( 'Addresses visitors or search engines asked for that do not exist, most requested first. If one should lead somewhere, add a redirect; if it is junk, forget it.', 'akseki-vi' ); ?></p>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Address', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Requests', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Linked from', 'akseki-vi' ); ?></th><th></th></tr></thead>
			<tbody>
			<?php if ( ! $log ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'Nothing yet.', 'akseki-vi' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( array_slice( $log, 0, 50, true ) as $path => $l ) : ?>
				<tr>
					<td><code><?php echo esc_html( $path ); ?></code></td>
					<td><?php echo (int) $l['hits']; ?> <span class="description">(<?php echo esc_html( wp_date( 'j M', $l['last'] ) ); ?>)</span></td>
					<td><?php echo $l['ref'] ? '<a href="' . esc_url( $l['ref'] ) . '" target="_blank" rel="noopener">' . esc_html( wp_html_excerpt( preg_replace( '#^https?://#', '', $l['ref'] ), 60, '…' ) ) . '</a>' : '&mdash;'; ?></td>
					<td><a href="<?php echo esc_url( add_query_arg( 'prefill', rawurlencode( $path ), $base ) . '#apt-rd-from' ); ?>"><?php esc_html_e( 'Add redirect', 'akseki-vi' ); ?></a> · <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'apt_rd' => 'forget', 'from' => rawurlencode( $path ) ), $base ), 'apt_rd' ) ); ?>"><?php esc_html_e( 'Forget', 'akseki-vi' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( $log ) : ?>
			<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'apt_rd', 'clear', $base ), 'apt_rd' ) ); ?>"><?php esc_html_e( 'Clear the log', 'akseki-vi' ); ?></a></p>
		<?php endif; ?>
	</div>
	<style>
		.apt-rd-lead { max-width: 900px; font-size: 14px; }
		.apt-rd .postbox { max-width: 1100px; margin-top: 12px; }
		.apt-rd .postbox .hndle { font-size: 14px; padding: 10px 12px; margin: 0; border-bottom: 1px solid #c3c4c7; }
		.apt-rd-form { display: grid; grid-template-columns: 1fr 1fr 220px; gap: 0 14px; align-items: end; }
		.apt-rd-form p { margin: 0 0 10px; } .apt-rd-form label { display: block; font-weight: 600; margin-bottom: 4px; }
		.apt-rd-form input[type=text], .apt-rd-form select { width: 100%; }
		.apt-rd-wide { grid-column: 1 / 3; }
		@media (max-width: 900px) { .apt-rd-form { grid-template-columns: 1fr; } .apt-rd-wide { grid-column: auto; } }
		.apt-rd .widefat { max-width: 1100px; }
		.apt-rd-warn { color: #b32d2e; font-weight: 600; }
		.apt-rd-del { color: #b32d2e; }
	</style>
	<?php
}
