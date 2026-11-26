<?php
/**
 * Limited Access files: only logged-in visitors can open them.
 *
 * The Limited Access documents live in wp-content/uploads/private/ (for
 * example private/aptwg/33rd_aptwg_final.pdf). Before, the login only hid the
 * list of documents: anyone with a file's address could open it.
 *
 * 1. That folder gets a .htaccess that refuses every direct request, so old,
 *    shared or bookmarked file addresses stop working (Apache/LiteSpeed).
 * 2. Links to files in that folder, in any page or post, are rewritten to
 *    /?apt_file=<path inside the folder>. The theme checks the login, then
 *    reads the file from disk and sends it. Visitors who are not logged in
 *    are sent to the Limited Access login page.
 * 3. Media → Limited Access files: whether direct access is really blocked,
 *    and any link on a Limited Access page that points outside the folder,
 *    with a button that moves those files into the folder (keeping their
 *    year/month folders) and updates their links and Media Library items.
 *    Files also used on public content are skipped. The last move can be
 *    undone there.
 *
 * Files already in the folder are not moved; their links are only rewritten
 * when the page is shown.
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The protected folder, relative to the uploads folder. */
const APT_LIMITED_FOLDER = 'private';

/** File types the gate will send. */
const APT_LIMITED_TYPES = array(
	'pdf'  => 'application/pdf',
	'doc'  => 'application/msword',
	'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	'xls'  => 'application/vnd.ms-excel',
	'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	'ppt'  => 'application/vnd.ms-powerpoint',
	'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
	'zip'  => 'application/zip',
	'jpg'  => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'png'  => 'image/png',
	'txt'  => 'text/plain',
);

/** Absolute path of the protected folder (no trailing slash). */
function apt_limited_dir() {
	$uploads = wp_upload_dir( null, false );
	return untrailingslashit( $uploads['basedir'] ) . '/' . APT_LIMITED_FOLDER;
}

/** Public address of the protected folder (no trailing slash). */
function apt_limited_url() {
	$uploads = wp_upload_dir( null, false );
	return untrailingslashit( $uploads['baseurl'] ) . '/' . APT_LIMITED_FOLDER;
}

/** The address that serves one protected file to logged-in visitors. */
function apt_limited_gate_url( $relative ) {
	return home_url( '/?apt_file=' . str_replace( '%2F', '/', rawurlencode( rawurldecode( $relative ) ) ) );
}

/** Where visitors who are not logged in are sent: the login page (4452). */
function apt_limited_login_url() {
	return 'publish' === get_post_status( 4452 ) ? get_permalink( 4452 ) : wp_login_url();
}

/* ---------- 1. Refuse direct access to the folder ---------- */

const APT_LIMITED_HTACCESS = "# Added by the apt-v2 theme: Limited Access files are only sent through the\n# theme, to logged-in visitors (see inc/limited-access-files.php).\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n";

/** Write the folder's .htaccess if it is missing. Returns true when it is in place. */
function apt_limited_write_htaccess() {
	$dir = apt_limited_dir();
	if ( ! is_dir( $dir ) ) {
		return false;
	}
	$file = $dir . '/.htaccess';
	if ( is_file( $file ) && false !== strpos( (string) file_get_contents( $file ), 'Require all denied' ) ) {
		return true;
	}
	if ( is_file( $file ) ) {
		// Someone else's rules: keep them and add ours underneath.
		return false !== file_put_contents( $file, "\n" . APT_LIMITED_HTACCESS, FILE_APPEND );
	}
	return false !== file_put_contents( $file, APT_LIMITED_HTACCESS );
}
add_action( 'admin_init', 'apt_limited_write_htaccess' );

/* ---------- 2. Rewrite links and serve files ---------- */

/**
 * Links to the protected folder → the gate. Matches the full address (with or
 * without http:/https:) and the address without the domain.
 */
function apt_limited_rewrite_links( $content ) {
	$base = apt_limited_url();
	$path = wp_parse_url( $base, PHP_URL_PATH );
	$host = wp_parse_url( $base, PHP_URL_HOST );
	if ( ! $path || false === strpos( $content, $path . '/' ) ) {
		return $content;
	}
	// This site's address, or an address starting at the path (right after a
	// quote, "=", space or bracket), so other websites' links are left alone.
	$pattern = '#(?:(?:https?:)?//' . preg_quote( (string) $host, '#' ) . '|(?<=["\'=\s(]))' . preg_quote( $path, '#' ) . '/([^"\'\s<>?\#]+)#i';
	return preg_replace_callback(
		$pattern,
		function ( $m ) {
			return esc_url( apt_limited_gate_url( $m[1] ) );
		},
		$content
	);
}
add_filter( 'the_content', 'apt_limited_rewrite_links', 30 );
add_filter( 'widget_text', 'apt_limited_rewrite_links', 30 );

/** Resolve ?apt_file=… to a real file inside the folder, or null. */
function apt_limited_resolve( $relative ) {
	$relative = ltrim( str_replace( '\\', '/', (string) $relative ), '/' );
	if ( '' === $relative || false !== strpos( $relative, "\0" ) ) {
		return null;
	}
	$root = realpath( apt_limited_dir() );
	$file = realpath( apt_limited_dir() . '/' . $relative );
	if ( ! $root || ! $file || ! is_file( $file ) || 0 !== strpos( $file, $root . DIRECTORY_SEPARATOR ) ) {
		return null; // missing, or outside the folder (e.g. "../")
	}
	$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	if ( ! isset( APT_LIMITED_TYPES[ $ext ] ) ) {
		return null;
	}
	return $file;
}

add_action( 'init', function () {
	if ( ! isset( $_GET['apt_file'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- read-only file request
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( apt_limited_login_url() );
		exit;
	}
	$file = apt_limited_resolve( wp_unslash( $_GET['apt_file'] ) ); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput -- checked by apt_limited_resolve()
	if ( ! $file ) {
		status_header( 404 );
		wp_die( esc_html__( 'This document could not be found.', 'akseki-vi' ), '', array( 'response' => 404 ) );
	}
	$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	while ( ob_get_level() ) {
		ob_end_clean();
	}
	header( 'Content-Type: ' . APT_LIMITED_TYPES[ $ext ] );
	header( 'Content-Length: ' . filesize( $file ) );
	header( 'Content-Disposition: inline; filename="' . str_replace( array( '"', "\r", "\n" ), '', basename( $file ) ) . '"' );
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}, 1 );

/* The PDF search add-on never reads files in the folder, so their text can't
   appear in search results. Matched as a path, so posts that merely mention
   "private" (e.g. the private sector) are still indexed. */
add_filter( 'apt_pdf_search_excluded_keywords', function ( $words ) {
	$words[] = '/uploads/' . APT_LIMITED_FOLDER . '/';
	return $words;
} );

/* ---------- 3. Media → Limited Access files ---------- */

/**
 * Ask for a file in the folder the way a visitor would. Returns 'blocked',
 * 'open' (the server ignores .htaccess) or 'unknown'.
 */
function apt_limited_check_access() {
	$dir = apt_limited_dir();
	if ( ! is_dir( $dir ) ) {
		return 'unknown';
	}
	$name  = 'apt-access-check.txt';
	$token = 'apt-check-' . wp_generate_password( 12, false );
	if ( false === file_put_contents( $dir . '/' . $name, $token ) ) {
		return 'unknown';
	}
	$response = wp_remote_get( apt_limited_url() . '/' . $name . '?t=' . time(), array( 'timeout' => 15, 'redirection' => 0 ) );
	unlink( $dir . '/' . $name );
	if ( is_wp_error( $response ) ) {
		return 'unknown';
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( 200 === $code && false !== strpos( wp_remote_retrieve_body( $response ), $token ) ) {
		return 'open';
	}
	return in_array( $code, array( 401, 403, 404 ), true ) ? 'blocked' : 'unknown';
}

/** Links on Limited Access pages (slug or title contains "limited") to uploads outside the folder: url => [ page ID => page ]. */
function apt_limited_public_links() {
	$pages = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'private', 'draft', 'pending' ), 'numberposts' => -1 ) );
	$uploads = wp_upload_dir( null, false );
	$upath   = wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );
	$ppath   = wp_parse_url( apt_limited_url(), PHP_URL_PATH ) . '/';
	$found   = array();
	foreach ( $pages as $page ) {
		if ( false === stripos( $page->post_name . ' ' . $page->post_title, 'limited' ) ) {
			continue;
		}
		if ( ! preg_match_all( '#(?:href|src)=["\']([^"\']*' . preg_quote( $upath, '#' ) . '/[^"\']+)["\']#i', $page->post_content, $m ) ) {
			continue;
		}
		foreach ( $m[1] as $url ) {
			if ( false === strpos( $url, $ppath ) ) {
				$found[ $url ][ $page->ID ] = $page;
			}
		}
	}
	return $found;
}

/** "2021/11/file.pdf" from a link to the uploads folder, or null. */
function apt_limited_rel_from_url( $url ) {
	$upath = wp_parse_url( wp_upload_dir( null, false )['baseurl'], PHP_URL_PATH ) . '/';
	$path  = (string) wp_parse_url( $url, PHP_URL_PATH );
	if ( 0 !== strpos( $path, $upath ) ) {
		return null;
	}
	$rel = rawurldecode( substr( $path, strlen( $upath ) ) );
	return ( '' === $rel || false !== strpos( $rel, '..' ) ) ? null : $rel;
}

/** Whether a page or post is a Limited Access one (slug or title contains "limited"). */
function apt_limited_is_limited_post( $post ) {
	return false !== stripos( $post->post_name . ' ' . $post->post_title, 'limited' );
}

/**
 * Public content that also uses this file (linked in its text, or the file is
 * attached to it). Moving the file would break those, so it is skipped.
 */
function apt_limited_public_uses( $rel, $attachment_id ) {
	global $wpdb;
	$upath    = wp_parse_url( wp_upload_dir( null, false )['baseurl'], PHP_URL_PATH ) . '/';
	$variants = array_unique( array( $rel, implode( '/', array_map( 'rawurlencode', explode( '/', $rel ) ) ) ) );
	$uses     = array();
	foreach ( $variants as $v ) {
		$ids = $wpdb->get_col( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_type NOT IN ('revision','attachment','nav_menu_item') AND post_status IN ('publish','future','draft','pending')",
			'%' . $wpdb->esc_like( $upath . $v ) . '%'
		) );
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			if ( $post && ! apt_limited_is_limited_post( $post ) ) {
				$uses[ $post->ID ] = $post;
			}
		}
	}
	if ( $attachment_id ) {
		$parent = get_post( (int) wp_get_post_parent_id( $attachment_id ) );
		if ( $parent && 'publish' === $parent->post_status && ! apt_limited_is_limited_post( $parent ) ) {
			$uses[ $parent->ID ] = $parent;
		}
	}
	return $uses;
}

/** In each page, point links at $from_rel to $to_rel (both relative to uploads). */
function apt_limited_relink( $page_ids, $from_rel, $to_rel ) {
	$upath = wp_parse_url( wp_upload_dir( null, false )['baseurl'], PHP_URL_PATH ) . '/';
	$enc   = function ( $r ) {
		return implode( '/', array_map( 'rawurlencode', explode( '/', $r ) ) );
	};
	$map = array(
		$upath . $from_rel         => $upath . $to_rel,
		$upath . $enc( $from_rel ) => $upath . $enc( $to_rel ),
	);
	foreach ( (array) $page_ids as $id ) {
		$post = get_post( $id );
		if ( ! $post ) {
			continue;
		}
		$content = strtr( $post->post_content, $map );
		if ( $content !== $post->post_content ) {
			wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => $content ) ) );
		}
	}
}

/** Point a Media Library item at its file's new place. */
function apt_limited_update_attachment( $attachment_id, $to_rel ) {
	if ( ! $attachment_id ) {
		return;
	}
	update_post_meta( $attachment_id, '_wp_attached_file', $to_rel );
	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( is_array( $meta ) && isset( $meta['file'] ) ) {
		$meta['file'] = $to_rel;
		wp_update_attachment_metadata( $attachment_id, $meta );
	}
}

/**
 * Move one file into the protected folder (keeping its year/month folders),
 * then update the Limited Access pages and the Media Library item.
 * Returns array( 'ok' => bool, 'message' => string, 'log' => entry|null ).
 */
function apt_limited_move_one( $url, $page_ids ) {
	$base = untrailingslashit( wp_upload_dir( null, false )['basedir'] ) . '/';
	$rel  = apt_limited_rel_from_url( $url );
	if ( ! $rel ) {
		return array( 'ok' => false, 'message' => __( 'Not a file in the uploads folder.', 'akseki-vi' ), 'log' => null );
	}
	if ( 0 === strpos( $rel, APT_LIMITED_FOLDER . '/' ) ) {
		return array( 'ok' => true, 'message' => __( 'Already protected.', 'akseki-vi' ), 'log' => null );
	}
	if ( ! is_file( $base . $rel ) ) {
		return array( 'ok' => false, 'message' => __( 'File not found on the server (the link may be broken).', 'akseki-vi' ), 'log' => null );
	}
	$attachment_id = attachment_url_to_postid( wp_upload_dir( null, false )['baseurl'] . '/' . $rel );
	$uses          = apt_limited_public_uses( $rel, $attachment_id );
	if ( $uses ) {
		$titles = implode( ', ', array_map( 'get_the_title', $uses ) );
		/* translators: %s: page or post titles */
		return array( 'ok' => false, 'message' => sprintf( __( 'Skipped: also used on public content (%s). Decide whether it should stay public.', 'akseki-vi' ), $titles ), 'log' => null );
	}
	$to_rel = APT_LIMITED_FOLDER . '/' . $rel;
	if ( file_exists( $base . $to_rel ) ) {
		return array( 'ok' => false, 'message' => __( 'Skipped: a file with the same name is already in the protected folder.', 'akseki-vi' ), 'log' => null );
	}
	if ( ! wp_mkdir_p( dirname( $base . $to_rel ) ) || ! rename( $base . $rel, $base . $to_rel ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return array( 'ok' => false, 'message' => __( 'Could not move the file (folder permissions).', 'akseki-vi' ), 'log' => null );
	}
	apt_limited_update_attachment( $attachment_id, $to_rel );
	apt_limited_relink( array_keys( $page_ids ), $rel, $to_rel );
	return array(
		'ok'      => true,
		'message' => __( 'Moved and protected.', 'akseki-vi' ),
		'log'     => array( 'from' => $rel, 'to' => $to_rel, 'attachment' => (int) $attachment_id, 'pages' => array_keys( $page_ids ) ),
	);
}

/** Put the files of the last move back where they were. Returns how many. */
function apt_limited_undo_last_move() {
	$log  = get_option( 'apt_limited_moves' );
	$base = untrailingslashit( wp_upload_dir( null, false )['basedir'] ) . '/';
	$done = 0;
	if ( empty( $log['items'] ) ) {
		return 0;
	}
	foreach ( array_reverse( $log['items'] ) as $item ) {
		if ( ! is_file( $base . $item['to'] ) || file_exists( $base . $item['from'] ) ) {
			continue;
		}
		if ( rename( $base . $item['to'], $base . $item['from'] ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			apt_limited_update_attachment( $item['attachment'], $item['from'] );
			apt_limited_relink( $item['pages'], $item['to'], $item['from'] );
			$done++;
		}
	}
	delete_option( 'apt_limited_moves' );
	return $done;
}

/* Media Library items whose file is in the folder: their address is the gate too. */
add_filter( 'wp_get_attachment_url', function ( $url, $attachment_id ) {
	$file = (string) get_post_meta( $attachment_id, '_wp_attached_file', true );
	return 0 === strpos( $file, APT_LIMITED_FOLDER . '/' ) ? apt_limited_gate_url( substr( $file, strlen( APT_LIMITED_FOLDER ) + 1 ) ) : $url;
}, 10, 2 );

add_action( 'admin_menu', function () {
	add_media_page( __( 'Limited Access files', 'akseki-vi' ), __( 'Limited Access files', 'akseki-vi' ), 'edit_others_posts', 'apt-limited-files', 'apt_limited_admin_page' );
} );

function apt_limited_admin_page() {
	$dir    = apt_limited_dir();
	$exists = is_dir( $dir );
	$rule   = $exists && apt_limited_write_htaccess();
	$status = get_option( 'apt_limited_check' );
	if ( isset( $_POST['apt_limited_check'] ) && check_admin_referer( 'apt_limited_check' ) ) {
		$status = array( 'result' => apt_limited_check_access(), 'time' => time() );
		update_option( 'apt_limited_check', $status, false );
	}
	$labels = array(
		'blocked' => array( '#00a32a', __( 'Blocked. Direct file addresses do not open; files are only sent to logged-in visitors.', 'akseki-vi' ) ),
		'open'    => array( '#d63638', __( 'NOT blocked. This server ignores the folder rule (.htaccess), so files still open from their direct address. Ask the host to deny web access to the folder below.', 'akseki-vi' ) ),
		'unknown' => array( '#dba617', __( 'Could not check from the server (the firewall may have stopped the test). Check by hand: in a private window, open a file address from the folder below; it should not open.', 'akseki-vi' ) ),
	);
	$results = array();
	if ( isset( $_POST['apt_limited_move'] ) && check_admin_referer( 'apt_limited_move' ) ) {
		$items = array();
		foreach ( apt_limited_public_links() as $url => $pages ) {
			$r                 = apt_limited_move_one( $url, $pages );
			$results[ $url ]   = $r;
			if ( $r['log'] ) {
				$items[] = $r['log'];
			}
		}
		if ( $items ) {
			update_option( 'apt_limited_moves', array( 'time' => time(), 'items' => $items ), false );
		}
	}
	$undone = null;
	if ( isset( $_POST['apt_limited_undo'] ) && check_admin_referer( 'apt_limited_undo' ) ) {
		$undone = apt_limited_undo_last_move();
	}
	$links = apt_limited_public_links();
	$log   = get_option( 'apt_limited_moves' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Limited Access files', 'akseki-vi' ); ?></h1>
		<p><?php esc_html_e( 'Files in the folder below only open for logged-in visitors. Links to them on any page are sent through the theme, which checks the login first. Upload new Limited Access documents into this folder.', 'akseki-vi' ); ?></p>
		<table class="widefat striped" style="max-width:900px">
			<tbody>
				<tr><th style="width:220px"><?php esc_html_e( 'Protected folder', 'akseki-vi' ); ?></th><td><code><?php echo esc_html( apt_limited_url() ); ?>/</code><br><?php echo $exists ? esc_html__( 'Found.', 'akseki-vi' ) : '<strong style="color:#d63638">' . esc_html__( 'Not found on this server.', 'akseki-vi' ) . '</strong>'; ?></td></tr>
				<tr><th><?php esc_html_e( 'Folder rule (.htaccess)', 'akseki-vi' ); ?></th><td><?php echo $rule ? esc_html__( 'In place.', 'akseki-vi' ) : '<strong style="color:#d63638">' . esc_html__( 'Missing: the folder could not be written to.', 'akseki-vi' ) . '</strong>'; ?></td></tr>
				<tr><th><?php esc_html_e( 'Direct access', 'akseki-vi' ); ?></th><td>
					<?php if ( $status && isset( $labels[ $status['result'] ] ) ) : ?>
						<strong style="color:<?php echo esc_attr( $labels[ $status['result'] ][0] ); ?>"><?php echo esc_html( $labels[ $status['result'] ][1] ); ?></strong><br>
						<small><?php echo esc_html( sprintf( /* translators: %s: date and time */ __( 'Checked %s', 'akseki-vi' ), wp_date( 'j F Y, H:i', $status['time'] ) ) ); ?></small>
					<?php else : ?>
						<?php esc_html_e( 'Not checked yet.', 'akseki-vi' ); ?>
					<?php endif; ?>
					<form method="post" style="margin-top:8px"><?php wp_nonce_field( 'apt_limited_check' ); ?><button class="button" name="apt_limited_check" value="1"><?php esc_html_e( 'Check now', 'akseki-vi' ); ?></button></form>
				</td></tr>
			</tbody>
		</table>
		<?php if ( $results ) : ?>
			<h2><?php esc_html_e( 'Result of the move', 'akseki-vi' ); ?></h2>
			<table class="widefat striped" style="max-width:900px"><tbody>
			<?php foreach ( $results as $url => $r ) : ?>
				<tr><td><code><?php echo esc_html( basename( rawurldecode( $url ) ) ); ?></code></td><td style="color:<?php echo $r['ok'] ? '#00a32a' : '#b32d2e'; ?>"><?php echo esc_html( $r['message'] ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>
		<?php if ( null !== $undone ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( sprintf( /* translators: %d: number of files */ __( 'Undone: %d files are back where they were, with their links.', 'akseki-vi' ), $undone ) ); ?></p></div>
		<?php endif; ?>
		<h2><?php esc_html_e( 'Links on Limited Access pages that are not protected', 'akseki-vi' ); ?></h2>
		<?php if ( ! $links ) : ?>
			<p><?php esc_html_e( 'None: every file linked from a Limited Access page is in the protected folder.', 'akseki-vi' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'These files are outside the protected folder, so anyone with the address can open them.', 'akseki-vi' ); ?></p>
			<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Move these files into the protected folder and update their links? Take a backup first. You can undo this here afterwards.', 'akseki-vi' ) ); ?>');">
				<?php wp_nonce_field( 'apt_limited_move' ); ?>
				<p><button class="button button-primary" name="apt_limited_move" value="1"><?php echo esc_html( sprintf( /* translators: %d: number of files */ _n( 'Move this file into the protected folder', 'Move these %d files into the protected folder', count( $links ), 'akseki-vi' ), count( $links ) ) ); ?></button></p>
				<p class="description"><?php esc_html_e( 'Each file keeps its year/month folder inside the protected folder, and its links on the Limited Access pages (and its Media Library item) are updated. A file that is also used on public content is skipped and listed, so public pages keep their documents.', 'akseki-vi' ); ?></p>
			</form>
			<table class="widefat striped" style="max-width:900px"><thead><tr><th><?php esc_html_e( 'File', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Page', 'akseki-vi' ); ?></th></tr></thead><tbody>
			<?php foreach ( $links as $url => $pages ) : ?>
				<tr><td><code><?php echo esc_html( rawurldecode( $url ) ); ?></code></td><td>
				<?php foreach ( $pages as $page ) : ?>
					<a href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>"><?php echo esc_html( get_the_title( $page ) ); ?></a><br>
				<?php endforeach; ?>
				</td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>
		<?php if ( ! empty( $log['items'] ) ) : ?>
			<h2><?php esc_html_e( 'Undo', 'akseki-vi' ); ?></h2>
			<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Put the files of the last move back where they were, with their old links?', 'akseki-vi' ) ); ?>');">
				<?php wp_nonce_field( 'apt_limited_undo' ); ?>
				<p><?php echo esc_html( sprintf( /* translators: 1: number of files, 2: date and time */ __( 'Last move: %1$d files on %2$s.', 'akseki-vi' ), count( $log['items'] ), wp_date( 'j F Y, H:i', $log['time'] ) ) ); ?></p>
				<p><button class="button" name="apt_limited_undo" value="1"><?php esc_html_e( 'Undo the last move', 'akseki-vi' ); ?></button></p>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/* ---------- 4. The Limited Access documents page ---------- */

/*
 * The documents page (a private page whose slug or title contains "limited",
 * not the login page 4452) keeps its plain content: headings with bullet
 * lists of links. When it is shown, the theme adds:
 * - a bar at the top with a shortcut to each heading (and how many documents
 *   it lists), and the "You are logged in / Log out" line;
 * - the document count next to each heading;
 * - the class "apt-limited-page", which styles the lists as a grid of tiles
 *   (assets/css/apt-design.css).
 * The title is shown without WordPress's "Private:" prefix.
 */
function apt_is_limited_docs_page( $post = null ) {
	$post = get_post( $post );
	return $post && 'page' === $post->post_type && 4452 !== (int) $post->ID
		&& apt_limited_is_limited_post( $post ) && ! post_password_required( $post );
}

add_filter( 'private_title_format', function ( $format, $post ) {
	return ( $post && apt_limited_is_limited_post( $post ) ) ? '%s' : $format;
}, 10, 2 );

add_filter( 'post_class', function ( $classes ) {
	if ( is_page() && in_the_loop() && apt_is_limited_docs_page() ) {
		$classes[] = 'apt-limited-page';
	}
	return $classes;
} );

function apt_limited_docs_layout( $content ) {
	if ( ! is_page() || ! in_the_loop() || ! is_main_query() || ! apt_is_limited_docs_page() ) {
		return $content;
	}
	// The log-out line ([wp_login_form] when logged in) moves into the bar.
	$logout = '';
	if ( preg_match( '#<p class="apt-logout">.*?</p>#s', $content, $m ) ) {
		$logout  = $m[0];
		$content = str_replace( $m[0], '', $content );
	}
	// Split at the h2 headings: [before, h2, section, h2, section, …].
	$parts = preg_split( '#(<h2\b[^>]*>.*?</h2>)#is', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	$links = array();
	$used  = array();
	for ( $i = 1; $i < count( $parts ); $i += 2 ) {
		preg_match( '#<h2\b([^>]*)>(.*?)</h2>#is', $parts[ $i ], $h );
		$text  = trim( html_entity_decode( wp_strip_all_tags( $h[2] ), ENT_QUOTES, 'UTF-8' ), " \t\n\r\0\x0B\xC2\xA0" );
		$count = preg_match_all( '#<li\b#i', isset( $parts[ $i + 1 ] ) ? $parts[ $i + 1 ] : '' );
		if ( '' === $text ) {
			continue;
		}
		$attrs = $h[1];
		if ( preg_match( '/\sid\s*=\s*(["\'])(.*?)\1/i', $attrs, $idm ) ) {
			$id = $idm[2];
		} else {
			$base = sanitize_title( $text );
			$base = '' !== $base ? $base : 'section';
			$id   = $base;
			for ( $n = 2; isset( $used[ $id ] ); $n++ ) {
				$id = $base . '-' . $n;
			}
			$attrs .= ' id="' . esc_attr( $id ) . '"';
		}
		$used[ $id ] = true;
		$badge       = $count ? ' <span class="apt-la-count">' . (int) $count . '</span>' : '';
		$parts[ $i ] = '<h2' . $attrs . '>' . $h[2] . $badge . '</h2>';
		$links[]     = '<a href="#' . esc_attr( $id ) . '">' . esc_html( $text ) . ( $count ? ' <span>' . (int) $count . '</span>' : '' ) . '</a>';
	}
	$nav = count( $links ) > 1 ? '<nav class="apt-la-jump" aria-label="' . esc_attr__( 'Sections', 'akseki-vi' ) . '">' . implode( '', $links ) . '</nav>' : '';
	$bar = ( $nav || $logout ) ? '<div class="apt-la-bar">' . $nav . $logout . '</div>' : '';
	return $bar . implode( '', $parts );
}
add_filter( 'the_content', 'apt_limited_docs_layout', 20 );
