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
 *    and any link on a Limited Access page that points outside the folder.
 *
 * Nothing is moved or renamed; the page content stays as it is.
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

/** Links on Limited Access pages (slug or title contains "limited") to uploads outside the folder. */
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
				$found[ $url ] = $page;
			}
		}
	}
	return $found;
}

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
	$links = apt_limited_public_links();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Limited Access files', 'akseki-vi' ); ?></h1>
		<p><?php esc_html_e( 'Files in the folder below only open for logged-in visitors. Links to them on any page are sent through the theme, which checks the login first. Nothing is moved; upload new Limited Access documents into this folder.', 'akseki-vi' ); ?></p>
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
		<h2><?php esc_html_e( 'Links on Limited Access pages that are not protected', 'akseki-vi' ); ?></h2>
		<?php if ( ! $links ) : ?>
			<p><?php esc_html_e( 'None: every file linked from a Limited Access page is in the protected folder.', 'akseki-vi' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'These files are outside the protected folder, so anyone with the address can open them. Move each into the folder and update the link.', 'akseki-vi' ); ?></p>
			<table class="widefat striped" style="max-width:900px"><thead><tr><th><?php esc_html_e( 'File', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Page', 'akseki-vi' ); ?></th></tr></thead><tbody>
			<?php foreach ( $links as $url => $page ) : ?>
				<tr><td><code><?php echo esc_html( rawurldecode( $url ) ); ?></code></td><td><a href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>"><?php echo esc_html( get_the_title( $page ) ); ?></a></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>
	</div>
	<?php
}
