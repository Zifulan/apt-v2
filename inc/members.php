<?php
/**
 * Members: individual accounts for Limited Access, instead of one shared
 * password, with an access log.
 *
 * - Role "APT Member" (apt_member): can read the Limited Access pages and
 *   files, nothing else (no admin area: inc/site-tweaks.php).
 * - Permission "apt_read_limited": APT Members, Editors and Administrators.
 *   During the transition, also any account that can read private pages
 *   (the shared Limited Access account), until it is switched off in
 *   Users → Members → Settings.
 * - The Members page (shortcode [apt_members], created from Users → Members):
 *   signed out, a sign-in form and a "Request access" form; signed in, the
 *   member's home with the Limited Access documents and "My account".
 * - Requests are approved or declined by Editors and Administrators in
 *   Users → Members. Approval creates the account and emails a link to set a
 *   password. Optional yearly re-confirmation.
 * - Access log: every Limited Access file sent (member, file, time, IP with
 *   the last part removed), in its own table; kept for 2 years; CSV export.
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const APT_MEM_VERSION = 1;

/* ---------- Settings ---------- */

function apt_mem_settings() {
	$s = get_option( 'apt_mem_settings', array() );
	return array_merge(
		array(
			'domains'     => "asean.org\n.go.id\n.gov.my\n.gov.sg\n.go.th\n.gov.ph\n.gov.vn\n.gov.kh\n.gov.la\n.gov.mm\n.gov.bn\nmofa.go.jp\n.go.jp\n.go.kr\nmfa.gov.cn\n.gov.cn\n.gov.tl",
			'notify'      => get_option( 'admin_email' ),
			'reconfirm'   => 0,
			'eligibility' => 'Accounts are for officials of ASEAN Member States, China, Japan and the Republic of Korea, and the ASEAN Secretariat. Each request is checked by the ASEAN Plus Three Cooperation Division.',
			'privacy'     => 'We keep your name, email and organisation only to manage your access, and delete them when the account is closed.',
			'shared'      => 'on',  // the shared Limited Access account: on / off
			'shared_user' => '',    // its username; empty = read from the login page
			'docs_page'   => 0,     // the Limited Access documents page; 0 = /limited-access-documents-2/
		),
		is_array( $s ) ? $s : array()
	);
}

/** The shared account's username: as set, or the preset username of the old login page (4452). */
function apt_mem_shared_username() {
	$s = apt_mem_settings();
	if ( '' !== trim( $s['shared_user'] ) ) {
		return trim( $s['shared_user'] );
	}
	$p = get_post( 4452 );
	if ( $p && preg_match( '/value_username\s*=\s*["\'\x{201C}\x{201D}]*([^"\'\s\x{201C}\x{201D}\]]+)/u', $p->post_content, $m ) ) {
		return $m[1];
	}
	return '';
}

/** Whether an email's domain is on the official list. */
function apt_mem_is_official( $email ) {
	$domain = strtolower( (string) substr( strrchr( (string) $email, '@' ), 1 ) );
	foreach ( preg_split( '/[\s,]+/', strtolower( apt_mem_settings()['domains'] ) ) as $d ) {
		$d = trim( $d );
		if ( '' === $d ) {
			continue;
		}
		if ( '.' === $d[0] ? substr( $domain, -strlen( $d ) ) === $d : ( $domain === $d || substr( $domain, -strlen( '.' . $d ) ) === '.' . $d ) ) {
			return true;
		}
	}
	return false;
}

/* ---------- Role and permission ---------- */

add_action( 'init', function () {
	if ( (int) get_option( 'apt_mem_version' ) < APT_MEM_VERSION ) {
		remove_role( 'apt_member' );
		add_role( 'apt_member', 'APT Member', array( 'read' => true, 'apt_read_limited' => true ) );
		apt_mem_create_table();
		update_option( 'apt_mem_version', APT_MEM_VERSION );
	}
	if ( ! wp_next_scheduled( 'apt_mem_daily' ) ) {
		wp_schedule_event( time() + 600, 'daily', 'apt_mem_daily' );
	}
} );

/** Whether a member account is disabled or past its re-confirmation date. */
function apt_mem_user_blocked( $user_id ) {
	if ( get_user_meta( $user_id, 'apt_disabled', true ) ) {
		return 'disabled';
	}
	$exp = (int) get_user_meta( $user_id, 'apt_expires', true );
	if ( $exp && apt_mem_settings()['reconfirm'] && $exp < time() ) {
		return 'expired';
	}
	return '';
}

/** The shared account, once switched off. */
function apt_mem_is_shared_off( $user ) {
	$name = apt_mem_shared_username();
	return 'off' === apt_mem_settings()['shared'] && '' !== $name && $user && strtolower( $user->user_login ) === strtolower( $name );
}

// apt_read_limited: members (unless blocked), editors and administrators, and
// during the transition any account that reads private pages.
add_filter( 'user_has_cap', function ( $allcaps, $caps, $args, $user ) {
	if ( ! in_array( 'apt_read_limited', (array) $caps, true ) ) {
		return $allcaps;
	}
	$ok = ! empty( $allcaps['apt_read_limited'] ) || ! empty( $allcaps['edit_others_posts'] ) || ! empty( $allcaps['read_private_pages'] );
	$shared = apt_mem_shared_username();
	if ( ! $ok && 'on' === apt_mem_settings()['shared'] && '' !== $shared && strtolower( $user->user_login ) === strtolower( $shared ) ) {
		$ok = true; // the shared account, until it is switched off
	}
	if ( $ok && in_array( 'apt_member', (array) $user->roles, true ) && apt_mem_user_blocked( $user->ID ) ) {
		$ok = false;
	}
	if ( $ok && apt_mem_is_shared_off( $user ) ) {
		$ok = false;
	}
	$allcaps['apt_read_limited'] = $ok;
	return $allcaps;
}, 10, 4 );

// Who may approve requests and manage members.
function apt_mem_can_manage() {
	return current_user_can( 'edit_others_posts' );
}

// Members may read the (private) Limited Access pages, and only those.
add_filter( 'map_meta_cap', function ( $caps, $cap, $user_id, $args ) {
	if ( 'read_post' !== $cap || empty( $args[0] ) ) {
		return $caps;
	}
	$post = get_post( $args[0] );
	if ( $post && 'private' === $post->post_status && 'page' === $post->post_type && function_exists( 'apt_limited_is_limited_post' ) && apt_limited_is_limited_post( $post ) ) {
		$user = get_userdata( $user_id );
		if ( $user && in_array( 'apt_member', (array) $user->roles, true ) && ! apt_mem_user_blocked( $user_id ) ) {
			return array( 'read' );
		}
	}
	return $caps;
}, 10, 4 );
// Blocked accounts can't sign in; the shared account can't once switched off.
add_filter( 'authenticate', function ( $user ) {
	if ( $user instanceof WP_User ) {
		if ( apt_mem_is_shared_off( $user ) ) {
			return new WP_Error( 'apt_shared_off', __( 'The shared Limited Access account has been replaced by individual accounts. Please request your own access on the Members page.', 'akseki-vi' ) );
		}
		if ( in_array( 'apt_member', (array) $user->roles, true ) ) {
			$b = apt_mem_user_blocked( $user->ID );
			if ( 'disabled' === $b ) {
				return new WP_Error( 'apt_disabled', __( 'This account has been closed. Please contact the ASEAN Plus Three Cooperation Division.', 'akseki-vi' ) );
			}
		}
	}
	return $user;
}, 100 );

add_action( 'wp_login', function ( $login, $user ) {
	update_user_meta( $user->ID, 'apt_last_login', time() );
}, 10, 2 );

/* ---------- Access log ---------- */

function apt_mem_table() {
	global $wpdb;
	return $wpdb->prefix . 'apt_access_log';
}

function apt_mem_create_table() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( 'CREATE TABLE ' . apt_mem_table() . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		time datetime NOT NULL,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		file varchar(255) NOT NULL DEFAULT '',
		ip varchar(45) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY time (time),
		KEY user_id (user_id)
	) " . $wpdb->get_charset_collate() . ';' );
}

/** The visitor's IP with the last part removed (enough to tell places apart, not people). */
function apt_mem_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		return preg_replace( '/\.\d+$/', '.0', $ip );
	}
	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
		return implode( ':', array_slice( explode( ':', $ip ), 0, 3 ) ) . '::';
	}
	return '';
}

/** Record that the current user opened a Limited Access file. */
function apt_mem_log( $relative ) {
	global $wpdb;
	$wpdb->insert( apt_mem_table(), array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		'time'    => current_time( 'mysql' ),
		'user_id' => get_current_user_id(),
		'file'    => substr( (string) $relative, 0, 255 ),
		'ip'      => apt_mem_ip(),
	) );
}

// Daily: keep two years of log; remind members whose access is due for re-confirmation.
add_action( 'apt_mem_daily', function () {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . apt_mem_table() . ' WHERE time < %s', gmdate( 'Y-m-d H:i:s', strtotime( '-2 years' ) ) ) ); // phpcs:ignore WordPress.DB
	if ( ! apt_mem_settings()['reconfirm'] ) {
		return;
	}
	foreach ( get_users( array( 'role' => 'apt_member', 'meta_key' => 'apt_expires' ) ) as $u ) { // phpcs:ignore WordPress.DB.SlowDBQuery
		$exp = (int) get_user_meta( $u->ID, 'apt_expires', true );
		if ( $exp > time() && $exp < time() + 30 * DAY_IN_SECONDS && ! get_user_meta( $u->ID, 'apt_reminded', true ) ) {
			wp_mail( $u->user_email, __( '[ASEAN Plus Three] Please confirm your Limited Access account', 'akseki-vi' ),
				sprintf( /* translators: 1: name, 2: date, 3: link */ __( "Dear %1\$s,\n\nYour access to the ASEAN Plus Three Limited Access documents ends on %2\$s. If you still need it, please sign in and click \"Confirm\" on the Members page:\n%3\$s\n\nThank you.", 'akseki-vi' ), $u->display_name, wp_date( 'j F Y', $exp ), apt_mem_page_url() ) );
			update_user_meta( $u->ID, 'apt_reminded', 1 );
		}
	}
} );

/** The Limited Access documents page: as set, else the page the old login sent people to. */
function apt_mem_docs_page() {
	$id = (int) apt_mem_settings()['docs_page'];
	$p  = $id ? get_post( $id ) : get_page_by_path( 'limited-access-documents-2' );
	return ( $p && 'page' === $p->post_type && 'trash' !== $p->post_status ) ? $p : null;
}

// Members (and editors) don't need an old page password on Limited Access pages:
// their account is the key.
add_filter( 'post_password_required', function ( $required, $post ) {
	if ( $required && $post && function_exists( 'apt_limited_is_limited_post' ) && apt_limited_is_limited_post( $post ) && current_user_can( 'apt_read_limited' ) ) {
		return false;
	}
	return $required;
}, 10, 2 );
add_filter( 'protected_title_format', function ( $format, $post ) {
	return ( $post && function_exists( 'apt_limited_is_limited_post' ) && apt_limited_is_limited_post( $post ) ) ? '%s' : $format;
}, 10, 2 );

/* ---------- The Members page ---------- */

/** The Members page (the page with [apt_members]), or 0. */
function apt_mem_page_id() {
	$id = (int) get_option( 'apt_mem_page' );
	if ( $id && 'publish' === get_post_status( $id ) ) {
		return $id;
	}
	return 0;
}
function apt_mem_page_url() {
	$id = apt_mem_page_id();
	return $id ? get_permalink( $id ) : home_url( '/' );
}

// Never cached; the old shared login page leads to the Members page once the shared account is off.
add_action( 'template_redirect', function () {
	$id = apt_mem_page_id();
	if ( $id && is_page( $id ) ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	}
	if ( $id && is_page( 4452 ) && 'off' === apt_mem_settings()['shared'] ) {
		wp_safe_redirect( get_permalink( $id ) );
		exit;
	}
}, 2 );

/** A notice line. */
function apt_mem_notice( $text, $kind = 'info' ) {
	return '<p class="apt-mem-notice is-' . esc_attr( $kind ) . '" role="' . ( 'error' === $kind ? 'alert' : 'status' ) . '">' . esc_html( $text ) . '</p>';
}

add_shortcode( 'apt_members', function () {
	// phpcs:disable WordPress.Security.NonceVerification -- display only; forms check their own nonces
	$msg = isset( $_GET['apt_m'] ) ? sanitize_key( wp_unslash( $_GET['apt_m'] ) ) : '';
	// phpcs:enable
	$messages = array(
		'sent'      => array( __( 'Thank you. Your request has been sent; you will receive an email once it has been checked.', 'akseki-vi' ), 'ok' ),
		'exists'    => array( __( 'There is already an account or a request for this email. Use "Forgot password?" or wait for the reply.', 'akseki-vi' ), 'error' ),
		'invalid'   => array( __( 'Please fill in every field with a valid email address.', 'akseki-vi' ), 'error' ),
		'slow'      => array( __( 'Please wait a few minutes before sending another request.', 'akseki-vi' ), 'error' ),
		'pw'        => array( __( 'Your password has been changed.', 'akseki-vi' ), 'ok' ),
		'pw_bad'    => array( __( 'The current password is not right, or the new passwords don\'t match (at least 10 characters).', 'akseki-vi' ), 'error' ),
		'confirmed' => array( __( 'Thank you: your access has been extended by one year.', 'akseki-vi' ), 'ok' ),
	);
	$out = isset( $messages[ $msg ] ) ? apt_mem_notice( $messages[ $msg ][0], $messages[ $msg ][1] ) : '';
	if ( isset( $_GET['loginf'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$out .= apt_mem_notice( __( 'The email or password is not right. Please try again.', 'akseki-vi' ), 'error' );
	}

	if ( ! is_user_logged_in() ) {
		$s     = apt_mem_settings();
		$login = wp_login_form( array(
			'echo'           => false,
			'redirect'       => apt_mem_page_url(),
			'label_username' => __( 'Email', 'akseki-vi' ),
			'label_password' => __( 'Password', 'akseki-vi' ),
			'label_remember' => __( 'Keep me signed in', 'akseki-vi' ),
			'label_log_in'   => __( 'Sign in', 'akseki-vi' ),
			'remember'       => true,
		) );
		$req = '<form method="post" class="apt-mem-form">' . wp_nonce_field( 'apt_mem_request', '_aptmem', true, false )
			. '<input type="hidden" name="apt_mem_do" value="request">'
			. '<p class="apt-mem-hp" aria-hidden="true"><label>Leave empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>'
			. '<p><label for="apt-mem-name">' . esc_html__( 'Full name', 'akseki-vi' ) . '</label><input id="apt-mem-name" name="name" required maxlength="120" autocomplete="name"></p>'
			. '<p><label for="apt-mem-email">' . esc_html__( 'Official email', 'akseki-vi' ) . '</label><input id="apt-mem-email" type="email" name="email" required maxlength="120" autocomplete="email"></p>'
			. '<p><label for="apt-mem-org">' . esc_html__( 'Organisation and position', 'akseki-vi' ) . '</label><input id="apt-mem-org" name="org" required maxlength="200" autocomplete="organization"></p>'
			. '<p><label for="apt-mem-why">' . esc_html__( 'Why you need access', 'akseki-vi' ) . '</label><textarea id="apt-mem-why" name="why" required maxlength="1000" rows="3"></textarea></p>'
			. '<p class="apt-mem-privacy">' . esc_html( $s['privacy'] ) . '</p>'
			. '<p><button type="submit" class="apt-mem-btn">' . esc_html__( 'Send request', 'akseki-vi' ) . '</button></p></form>';
		return $out . '<div class="apt-mem-two">'
			. '<section class="apt-mem-card"><h2>' . esc_html__( 'Sign in', 'akseki-vi' ) . '</h2>' . $login
			. '<p class="apt-mem-forgot"><a href="' . esc_url( wp_lostpassword_url( apt_mem_page_url() ) ) . '">' . esc_html__( 'Forgot password?', 'akseki-vi' ) . '</a></p></section>'
			. '<section class="apt-mem-card is-soft"><h2>' . esc_html__( 'Request access', 'akseki-vi' ) . '</h2><p>' . esc_html( $s['eligibility'] ) . '</p>' . $req . '</section>'
			. '</div>';
	}

	$u = wp_get_current_user();
	if ( ! current_user_can( 'apt_read_limited' ) ) {
		$why = apt_mem_user_blocked( $u->ID );
		return $out . apt_mem_notice( 'expired' === $why ? __( 'Your access has ended because it was not re-confirmed in time. Please contact the ASEAN Plus Three Cooperation Division to have it renewed.', 'akseki-vi' ) : __( 'This account does not have access to the Limited Access documents.', 'akseki-vi' ), 'error' )
			. '<p><a class="apt-mem-btn" href="' . esc_url( wp_logout_url( apt_mem_page_url() ) ) . '">' . esc_html__( 'Sign out', 'akseki-vi' ) . '</a></p>';
	}

	$org  = (string) get_user_meta( $u->ID, 'apt_org', true );
	$head = '<div class="apt-mem-head"><p>' . esc_html__( 'Signed in as', 'akseki-vi' ) . ' <strong>' . esc_html( $u->display_name ) . '</strong>' . ( $org ? ', ' . esc_html( $org ) : '' ) . '</p>'
		. '<p><a href="#apt-mem-account">' . esc_html__( 'My account', 'akseki-vi' ) . '</a> · <a href="' . esc_url( wp_logout_url( apt_mem_page_url() ) ) . '">' . esc_html__( 'Sign out', 'akseki-vi' ) . '</a></p></div>';
	$exp = (int) get_user_meta( $u->ID, 'apt_expires', true );
	if ( apt_mem_settings()['reconfirm'] && $exp && $exp < time() + 30 * DAY_IN_SECONDS ) {
		$head .= '<form method="post" class="apt-mem-notice is-info">' . wp_nonce_field( 'apt_mem_confirm', '_aptmem', true, false ) . '<input type="hidden" name="apt_mem_do" value="confirm">'
			. esc_html( sprintf( /* translators: %s: date */ __( 'Your access ends on %s. Do you still need it?', 'akseki-vi' ), wp_date( 'j F Y', $exp ) ) ) . ' <button class="apt-mem-btn is-small">' . esc_html__( 'Confirm', 'akseki-vi' ) . '</button></form>';
	}

	// The Limited Access documents page (Users → Members → Settings).
	$dp   = apt_mem_docs_page();
	$docs = $dp
		? '<p><a class="apt-mem-btn" href="' . esc_url( get_permalink( $dp ) ) . '">' . esc_html( get_post_field( 'post_title', $dp ) ) . '</a></p>'
		: '<p>' . esc_html__( 'The documents page has not been set yet (Users → Members → Settings).', 'akseki-vi' ) . '</p>';

	$account = '<section class="apt-mem-card" id="apt-mem-account"><h2>' . esc_html__( 'My account', 'akseki-vi' ) . '</h2>'
		. '<p>' . esc_html__( 'Email:', 'akseki-vi' ) . ' ' . esc_html( $u->user_email ) . '</p>'
		. '<form method="post" class="apt-mem-form">' . wp_nonce_field( 'apt_mem_pw', '_aptmem', true, false ) . '<input type="hidden" name="apt_mem_do" value="password">'
		. '<p><label for="apt-mem-cur">' . esc_html__( 'Current password', 'akseki-vi' ) . '</label><input id="apt-mem-cur" type="password" name="cur" required autocomplete="current-password"></p>'
		. '<p><label for="apt-mem-new">' . esc_html__( 'New password (at least 10 characters)', 'akseki-vi' ) . '</label><input id="apt-mem-new" type="password" name="new" required minlength="10" autocomplete="new-password"></p>'
		. '<p><label for="apt-mem-new2">' . esc_html__( 'New password again', 'akseki-vi' ) . '</label><input id="apt-mem-new2" type="password" name="new2" required minlength="10" autocomplete="new-password"></p>'
		. '<p><button class="apt-mem-btn">' . esc_html__( 'Change password', 'akseki-vi' ) . '</button></p></form></section>';

	return $out . $head
		. ( function_exists( 'apt_alf_finder_html' ) ? apt_alf_finder_html() : '' )
		. '<section class="apt-mem-card"><h2>' . esc_html__( 'Limited Access documents', 'akseki-vi' ) . '</h2>' . $docs
		. '<p class="apt-mem-privacy">' . esc_html__( 'These documents are for your official work only. Please don\'t share them outside official channels. Downloads are recorded.', 'akseki-vi' ) . '</p></section>'
		. $account;
} );

/* ---------- Form handling (front end) ---------- */

add_action( 'template_redirect', function () {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || empty( $_POST['apt_mem_do'] ) ) { // phpcs:ignore WordPress.Security
		return;
	}
	$do   = sanitize_key( wp_unslash( $_POST['apt_mem_do'] ) );
	$back = apt_mem_page_url();
	$go   = function ( $m ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'apt_m', $m, $back ) . '#main' );
		exit;
	};

	if ( 'request' === $do ) {
		check_admin_referer( 'apt_mem_request', '_aptmem' );
		if ( ! empty( $_POST['website'] ) ) {
			$go( 'sent' ); // honeypot: pretend it worked
		}
		$key = 'apt_mem_rl_' . md5( apt_mem_ip() );
		if ( get_transient( $key ) ) {
			$go( 'slow' );
		}
		$r = array(
			'name'  => sanitize_text_field( wp_unslash( isset( $_POST['name'] ) ? $_POST['name'] : '' ) ),
			'email' => sanitize_email( wp_unslash( isset( $_POST['email'] ) ? $_POST['email'] : '' ) ),
			'org'   => sanitize_text_field( wp_unslash( isset( $_POST['org'] ) ? $_POST['org'] : '' ) ),
			'why'   => sanitize_textarea_field( wp_unslash( isset( $_POST['why'] ) ? $_POST['why'] : '' ) ),
		);
		if ( '' === $r['name'] || ! is_email( $r['email'] ) || '' === $r['org'] || '' === $r['why'] ) {
			$go( 'invalid' );
		}
		$reqs = get_option( 'apt_mem_requests', array() );
		foreach ( (array) $reqs as $old ) {
			if ( strtolower( $old['email'] ) === strtolower( $r['email'] ) ) {
				$go( 'exists' );
			}
		}
		if ( email_exists( $r['email'] ) ) {
			$go( 'exists' );
		}
		$r['time']     = time();
		$r['official'] = apt_mem_is_official( $r['email'] );
		$reqs          = is_array( $reqs ) ? $reqs : array();
		$reqs[ wp_generate_password( 12, false ) ] = $r;
		update_option( 'apt_mem_requests', $reqs, false );
		set_transient( $key, 1, 2 * MINUTE_IN_SECONDS ); // short: colleagues in one office share an address
		$to = apt_mem_settings()['notify'];
		if ( is_email( $to ) ) {
			wp_mail( $to, sprintf( /* translators: %s: name */ __( '[ASEAN Plus Three] Access request from %s', 'akseki-vi' ), $r['name'] ),
				sprintf( "%s\n%s%s\n%s\n\n%s\n\n%s\n%s", $r['name'], $r['email'], $r['official'] ? ' (official domain)' : ' (domain not on the official list)', $r['org'], $r['why'], __( 'Approve or decline:', 'akseki-vi' ), apt_mem_admin_base() ) );
		}
		$go( 'sent' );
	}

	if ( ! is_user_logged_in() ) {
		return;
	}
	$u = wp_get_current_user();
	if ( 'password' === $do ) {
		check_admin_referer( 'apt_mem_pw', '_aptmem' );
		$cur  = isset( $_POST['cur'] ) ? (string) wp_unslash( $_POST['cur'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- passwords are not sanitized
		$new  = isset( $_POST['new'] ) ? (string) wp_unslash( $_POST['new'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$new2 = isset( $_POST['new2'] ) ? (string) wp_unslash( $_POST['new2'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! wp_check_password( $cur, $u->user_pass, $u->ID ) || strlen( $new ) < 10 || $new !== $new2 ) {
			$go( 'pw_bad' );
		}
		wp_set_password( $new, $u->ID );
		wp_set_auth_cookie( $u->ID, true ); // wp_set_password signs out; stay signed in
		$go( 'pw' );
	}
	if ( 'confirm' === $do && in_array( 'apt_member', (array) $u->roles, true ) ) {
		check_admin_referer( 'apt_mem_confirm', '_aptmem' );
		$exp = max( time(), (int) get_user_meta( $u->ID, 'apt_expires', true ) );
		update_user_meta( $u->ID, 'apt_expires', $exp + YEAR_IN_SECONDS );
		delete_user_meta( $u->ID, 'apt_reminded' );
		$go( 'confirmed' );
	}
} );

/* ---------- Users → Members (admin) ---------- */

add_action( 'admin_menu', function () {
	$n    = count( (array) get_option( 'apt_mem_requests', array() ) );
	$hook = add_users_page( __( 'Members', 'akseki-vi' ), __( 'Members', 'akseki-vi' ) . ( $n ? ' <span class="awaiting-mod"><span class="pending-count">' . $n . '</span></span>' : '' ), 'edit_others_posts', 'apt-members', 'apt_mem_admin_page' );
	add_action( 'load-' . $hook, 'apt_mem_admin_actions' );
} );
/** The admin screen's address; admin.php?page= works under Users (administrators) and Profile (editors). */
function apt_mem_admin_base( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'apt-members' ), $args ), admin_url( 'admin.php' ) );
}

/** Create an approved member and email them a link to set their password. Returns the user ID or a WP_Error. */
function apt_mem_create_member( $r, $approver ) {
	$login = sanitize_user( strtolower( strtok( $r['email'], '@' ) ), true );
	$base  = $login ? $login : 'member';
	for ( $i = 2; username_exists( $login ); $i++ ) {
		$login = $base . $i;
	}
	$id = wp_insert_user( array(
		'user_login'   => $login,
		'user_email'   => $r['email'],
		'display_name' => $r['name'],
		'first_name'   => $r['name'],
		'user_pass'    => wp_generate_password( 32, true, true ),
		'role'         => 'apt_member',
	) );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	update_user_meta( $id, 'apt_org', $r['org'] );
	update_user_meta( $id, 'apt_why', $r['why'] );
	update_user_meta( $id, 'apt_approved_by', (int) $approver );
	update_user_meta( $id, 'apt_approved_at', time() );
	if ( apt_mem_settings()['reconfirm'] ) {
		update_user_meta( $id, 'apt_expires', time() + YEAR_IN_SECONDS );
	}
	$user = get_userdata( $id );
	$key  = get_password_reset_key( $user );
	if ( ! is_wp_error( $key ) ) {
		$link = network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user->user_login ), 'login' );
		wp_mail( $user->user_email, __( '[ASEAN Plus Three] Your Limited Access account', 'akseki-vi' ),
			sprintf( /* translators: 1: name, 2: link, 3: members page */ __( "Dear %1\$s,\n\nYour request for access to the ASEAN Plus Three Limited Access documents has been approved.\n\nPlease set your password here (the link works once):\n%2\$s\n\nThen sign in with your email on the Members page:\n%3\$s\n\nThese documents are for official work only. Thank you.", 'akseki-vi' ), $user->display_name, $link, apt_mem_page_url() ) );
	}
	return $id;
}

function apt_mem_admin_actions() {
	if ( empty( $_REQUEST['apt_mem_a'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	check_admin_referer( 'apt_mem_admin' );
	if ( ! apt_mem_can_manage() ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'akseki-vi' ) );
	}
	$a    = sanitize_key( wp_unslash( $_REQUEST['apt_mem_a'] ) );
	$tab  = isset( $_REQUEST['tab'] ) ? sanitize_key( wp_unslash( $_REQUEST['tab'] ) ) : 'requests';
	$args = array( 'page' => 'apt-members', 'tab' => $tab );
	$reqs = (array) get_option( 'apt_mem_requests', array() );
	$key  = isset( $_REQUEST['req'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_REQUEST['req'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$uid  = isset( $_REQUEST['user'] ) ? absint( $_REQUEST['user'] ) : 0;
	$is_member = function ( $id ) {
		$u = get_userdata( $id );
		return $u && in_array( 'apt_member', (array) $u->roles, true );
	};

	if ( 'approve' === $a && isset( $reqs[ $key ] ) ) {
		$id = apt_mem_create_member( $reqs[ $key ], get_current_user_id() );
		if ( ! is_wp_error( $id ) ) {
			unset( $reqs[ $key ] );
			update_option( 'apt_mem_requests', $reqs, false );
		}
		$args['msg'] = is_wp_error( $id ) ? 'error' : 'approved';
	} elseif ( 'decline' === $a && isset( $reqs[ $key ] ) ) {
		$r = $reqs[ $key ];
		unset( $reqs[ $key ] );
		update_option( 'apt_mem_requests', $reqs, false );
		if ( ! empty( $_REQUEST['tell'] ) ) {
			wp_mail( $r['email'], __( '[ASEAN Plus Three] Your access request', 'akseki-vi' ),
				sprintf( /* translators: %s: name */ __( "Dear %s,\n\nThank you for your interest. We are unable to give access to the Limited Access documents for this request. For questions, please contact the ASEAN Plus Three Cooperation Division.", 'akseki-vi' ), $r['name'] ) );
		}
		$args['msg'] = 'declined';
	} elseif ( in_array( $a, array( 'disable', 'enable', 'extend' ), true ) && $is_member( $uid ) ) {
		if ( 'disable' === $a ) {
			update_user_meta( $uid, 'apt_disabled', time() );
			WP_Session_Tokens::get_instance( $uid )->destroy_all(); // signed out everywhere
		} elseif ( 'enable' === $a ) {
			delete_user_meta( $uid, 'apt_disabled' );
		} else {
			update_user_meta( $uid, 'apt_expires', time() + YEAR_IN_SECONDS );
			delete_user_meta( $uid, 'apt_reminded' );
		}
		$args['msg'] = 'saved';
	} elseif ( 'delete' === $a && $is_member( $uid ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $uid ); // their log rows stay, shown as "deleted account"
		$args['msg'] = 'deleted';
	} elseif ( 'settings' === $a ) {
		$s = apt_mem_settings();
		$p = function ( $k ) {
			return isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : ''; // phpcs:ignore WordPress.Security
		};
		update_option( 'apt_mem_settings', array(
			'domains'     => sanitize_textarea_field( $p( 'domains' ) ),
			'notify'      => sanitize_email( $p( 'notify' ) ),
			'reconfirm'   => $p( 'reconfirm' ) ? 1 : 0,
			'eligibility' => sanitize_textarea_field( $p( 'eligibility' ) ) ? sanitize_textarea_field( $p( 'eligibility' ) ) : $s['eligibility'],
			'privacy'     => sanitize_textarea_field( $p( 'privacy' ) ) ? sanitize_textarea_field( $p( 'privacy' ) ) : $s['privacy'],
			'shared'      => 'off' === $p( 'shared' ) ? 'off' : 'on',
			'shared_user' => sanitize_user( $p( 'shared_user' ) ),
			'docs_page'   => absint( $p( 'docs_page' ) ),
		), false );
		if ( 'off' === $p( 'shared' ) && 'off' !== $s['shared'] ) {
			$shared = get_user_by( 'login', apt_mem_shared_username() );
			if ( $shared ) {
				WP_Session_Tokens::get_instance( $shared->ID )->destroy_all();
			}
		}
		$args['msg'] = 'saved';
	} elseif ( 'create_page' === $a && ! apt_mem_page_id() ) {
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Members', 'post_name' => 'members', 'post_content' => '[apt_members]' ) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_option( 'apt_mem_page', (int) $id );
		}
		$args['msg'] = 'page';
	} elseif ( 'alf_run' === $a && function_exists( 'apt_alf_run' ) ) {
		$left             = apt_alf_run( 8 );
		$args['continue'] = $left ? 1 : 0; // pause a few seconds between steps (firewall)
	} elseif ( 'alf_rebuild' === $a && function_exists( 'apt_alf_enqueue' ) ) {
		update_option( 'apt_alf_queue', array( 'all' ), false );
		delete_option( 'apt_alf_skipped' );
		$left             = apt_alf_run( 8 );
		$args['continue'] = $left ? 1 : 0;
	} elseif ( 'export' === $a ) {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT time, user_id, file, ip FROM ' . apt_mem_table() . ' ORDER BY id DESC LIMIT 50000' ); // phpcs:ignore WordPress.DB
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="limited-access-log-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$h = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $h, array( 'Time', 'Member', 'Email', 'Organisation', 'File', 'IP (shortened)' ), ',', '"', '\\' );
		foreach ( $rows as $r ) {
			$u = get_userdata( $r->user_id );
			fputcsv( $h, array( $r->time, $u ? $u->display_name : 'deleted account', $u ? $u->user_email : '', $u ? get_user_meta( $u->ID, 'apt_org', true ) : '', $r->file, $r->ip ), ',', '"', '\\' );
		}
		exit;
	}
	wp_safe_redirect( apt_mem_admin_base( $args ) );
	exit;
}

function apt_mem_admin_url( $a, $extra = array() ) {
	return wp_nonce_url( apt_mem_admin_base( array_merge( array( 'apt_mem_a' => $a ), $extra ) ), 'apt_mem_admin' );
}

function apt_mem_admin_page() {
	global $wpdb;
	$tabs = array(
		'requests' => __( 'Requests', 'akseki-vi' ),
		'members'  => __( 'Members', 'akseki-vi' ),
		'log'      => __( 'Access log', 'akseki-vi' ),
		'alf'      => __( 'Agreed Language', 'akseki-vi' ),
		'settings' => __( 'Settings', 'akseki-vi' ),
	);
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'requests'; // phpcs:ignore WordPress.Security.NonceVerification
	$tab  = isset( $tabs[ $tab ] ) ? $tab : 'requests';
	$msg  = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$s    = apt_mem_settings();
	$reqs = (array) get_option( 'apt_mem_requests', array() );
	$msgs = array(
		'approved' => __( 'Approved: the account was created and an email with a link to set the password was sent.', 'akseki-vi' ),
		'declined' => __( 'Request declined.', 'akseki-vi' ),
		'saved'    => __( 'Saved.', 'akseki-vi' ),
		'deleted'  => __( 'Account deleted.', 'akseki-vi' ),
		'page'     => __( 'The Members page was created.', 'akseki-vi' ),
		'error'    => __( 'Something went wrong: the account could not be created (is the email already used?).', 'akseki-vi' ),
	);
	?>
	<div class="wrap apt-mem-admin">
		<h1><?php esc_html_e( 'Members', 'akseki-vi' ); ?></h1>
		<p><?php esc_html_e( 'Individual accounts for the Limited Access documents: requests, members, the access log and settings.', 'akseki-vi' ); ?></p>
		<?php if ( isset( $msgs[ $msg ] ) ) : ?>
			<div class="notice notice-<?php echo 'error' === $msg ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( $msgs[ $msg ] ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! apt_mem_page_id() ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'There is no Members page yet (the page where members sign in and request access).', 'akseki-vi' ); ?> <a class="button button-primary" href="<?php echo esc_url( apt_mem_admin_url( 'create_page' ) ); ?>"><?php esc_html_e( 'Create the Members page', 'akseki-vi' ); ?></a></p></div>
		<?php else : ?>
			<p><?php esc_html_e( 'Members page:', 'akseki-vi' ); ?> <a href="<?php echo esc_url( apt_mem_page_url() ); ?>" target="_blank"><?php echo esc_html( apt_mem_page_url() ); ?></a></p>
		<?php endif; ?>

		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $k => $l ) : ?>
				<a class="nav-tab<?php echo $k === $tab ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( apt_mem_admin_base( array( 'tab' => $k ) ) ); ?>"><?php echo esc_html( $l ); ?><?php echo 'requests' === $k && $reqs ? ' (' . count( $reqs ) . ')' : ''; ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( 'requests' === $tab ) : ?>
			<?php if ( ! $reqs ) : ?>
				<p><?php esc_html_e( 'No requests waiting.', 'akseki-vi' ); ?></p>
			<?php else : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Person', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Organisation and reason', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Sent', 'akseki-vi' ); ?></th><th></th></tr></thead><tbody>
			<?php foreach ( $reqs as $k => $r ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $r['name'] ); ?></strong><br><?php echo esc_html( $r['email'] ); ?><br>
						<?php echo $r['official'] ? '<span class="apt-mem-tag is-ok">' . esc_html__( 'Official domain', 'akseki-vi' ) . '</span>' : '<span class="apt-mem-tag is-warn">' . esc_html__( 'Domain not on the official list: check', 'akseki-vi' ) . '</span>'; ?></td>
					<td><?php echo esc_html( $r['org'] ); ?><br><em><?php echo esc_html( $r['why'] ); ?></em></td>
					<td><?php echo esc_html( wp_date( 'j M Y, H:i', $r['time'] ) ); ?></td>
					<td class="apt-mem-acts">
						<a class="button button-primary" href="<?php echo esc_url( apt_mem_admin_url( 'approve', array( 'req' => $k ) ) ); ?>"><?php esc_html_e( 'Approve', 'akseki-vi' ); ?></a>
						<a class="button" href="<?php echo esc_url( apt_mem_admin_url( 'decline', array( 'req' => $k, 'tell' => 1 ) ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Decline and tell them by email?', 'akseki-vi' ) ); ?>')"><?php esc_html_e( 'Decline', 'akseki-vi' ); ?></a>
						<a class="apt-mem-quiet" href="<?php echo esc_url( apt_mem_admin_url( 'decline', array( 'req' => $k ) ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this request without an email (e.g. spam)?', 'akseki-vi' ) ); ?>')"><?php esc_html_e( 'Delete (spam)', 'akseki-vi' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
			<?php endif; ?>

		<?php elseif ( 'members' === $tab ) : ?>
			<?php $members = get_users( array( 'role' => 'apt_member', 'orderby' => 'display_name' ) ); ?>
			<?php if ( ! $members ) : ?>
				<p><?php esc_html_e( 'No members yet.', 'akseki-vi' ); ?></p>
			<?php else : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Member', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Organisation', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Approved', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Last sign-in', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Status', 'akseki-vi' ); ?></th><th></th></tr></thead><tbody>
			<?php foreach ( $members as $m ) : ?>
				<?php
				$by   = get_userdata( (int) get_user_meta( $m->ID, 'apt_approved_by', true ) );
				$at   = (int) get_user_meta( $m->ID, 'apt_approved_at', true );
				$last = (int) get_user_meta( $m->ID, 'apt_last_login', true );
				$bl   = apt_mem_user_blocked( $m->ID );
				$exp  = (int) get_user_meta( $m->ID, 'apt_expires', true );
				?>
				<tr>
					<td><strong><?php echo esc_html( $m->display_name ); ?></strong><br><?php echo esc_html( $m->user_email ); ?></td>
					<td><?php echo esc_html( (string) get_user_meta( $m->ID, 'apt_org', true ) ); ?></td>
					<td><?php echo $at ? esc_html( wp_date( 'j M Y', $at ) . ( $by ? ' · ' . $by->display_name : '' ) ) : '&mdash;'; ?></td>
					<td><?php echo $last ? esc_html( wp_date( 'j M Y', $last ) ) : esc_html__( 'Never', 'akseki-vi' ); ?></td>
					<td><?php
						if ( 'disabled' === $bl ) {
							echo '<span class="apt-mem-tag is-warn">' . esc_html__( 'Closed', 'akseki-vi' ) . '</span>';
						} elseif ( 'expired' === $bl ) {
							echo '<span class="apt-mem-tag is-warn">' . esc_html__( 'Not re-confirmed', 'akseki-vi' ) . '</span>';
						} else {
							echo '<span class="apt-mem-tag is-ok">' . esc_html__( 'Active', 'akseki-vi' ) . '</span>' . ( $exp && $s['reconfirm'] ? ' <span class="description">' . esc_html( sprintf( /* translators: %s: date */ __( 'until %s', 'akseki-vi' ), wp_date( 'j M Y', $exp ) ) ) . '</span>' : '' );
						}
					?></td>
					<td class="apt-mem-acts">
						<a href="<?php echo esc_url( apt_mem_admin_base( array( 'tab' => 'log', 'member' => $m->ID ) ) ); ?>"><?php esc_html_e( 'Log', 'akseki-vi' ); ?></a> ·
						<?php if ( 'disabled' === $bl ) : ?>
							<a href="<?php echo esc_url( apt_mem_admin_url( 'enable', array( 'user' => $m->ID, 'tab' => 'members' ) ) ); ?>"><?php esc_html_e( 'Reopen', 'akseki-vi' ); ?></a>
						<?php else : ?>
							<a href="<?php echo esc_url( apt_mem_admin_url( 'disable', array( 'user' => $m->ID, 'tab' => 'members' ) ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Close this account? They are signed out at once.', 'akseki-vi' ) ); ?>')"><?php esc_html_e( 'Close', 'akseki-vi' ); ?></a>
						<?php endif; ?>
						<?php if ( $s['reconfirm'] ) : ?> · <a href="<?php echo esc_url( apt_mem_admin_url( 'extend', array( 'user' => $m->ID, 'tab' => 'members' ) ) ); ?>"><?php esc_html_e( 'Extend 1 year', 'akseki-vi' ); ?></a><?php endif; ?>
						· <a class="apt-mem-danger" href="<?php echo esc_url( apt_mem_admin_url( 'delete', array( 'user' => $m->ID, 'tab' => 'members' ) ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this account and its personal data? Its log entries remain, as a deleted account.', 'akseki-vi' ) ); ?>')"><?php esc_html_e( 'Delete', 'akseki-vi' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
			<?php endif; ?>

		<?php elseif ( 'log' === $tab ) : ?>
			<?php
			$member = isset( $_GET['member'] ) ? absint( $_GET['member'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
			$where  = $member ? $wpdb->prepare( ' WHERE user_id = %d', $member ) : '';
			$rows   = $wpdb->get_results( 'SELECT time, user_id, file, ip FROM ' . apt_mem_table() . $where . ' ORDER BY id DESC LIMIT 300' ); // phpcs:ignore WordPress.DB
			?>
			<p><?php esc_html_e( 'Every Limited Access file sent, newest first (the last 300; kept for 2 years). IP addresses are shortened.', 'akseki-vi' ); ?>
				<a class="button" href="<?php echo esc_url( apt_mem_admin_url( 'export', array( 'tab' => 'log' ) ) ); ?>"><?php esc_html_e( 'Download as CSV', 'akseki-vi' ); ?></a>
				<?php if ( $member ) : ?> <a href="<?php echo esc_url( apt_mem_admin_base( array( 'tab' => 'log' ) ) ); ?>"><?php esc_html_e( 'Show everyone', 'akseki-vi' ); ?></a><?php endif; ?></p>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Time', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Account', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Document', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'IP', 'akseki-vi' ); ?></th></tr></thead><tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="4"><?php esc_html_e( 'Nothing recorded yet.', 'akseki-vi' ); ?></td></tr><?php endif; ?>
			<?php foreach ( (array) $rows as $r ) : ?>
				<?php $u = get_userdata( $r->user_id ); ?>
				<tr>
					<td><?php echo esc_html( mysql2date( 'j M Y, H:i', $r->time ) ); ?></td>
					<td><?php echo $u ? '<a href="' . esc_url( apt_mem_admin_base( array( 'tab' => 'log', 'member' => $u->ID ) ) ) . '">' . esc_html( $u->display_name ) . '</a>' . ( apt_mem_is_shared_off( $u ) || strtolower( $u->user_login ) === strtolower( apt_mem_shared_username() ) ? ' <span class="apt-mem-tag is-warn">' . esc_html__( 'shared account', 'akseki-vi' ) . '</span>' : '' ) : esc_html__( 'deleted account', 'akseki-vi' ); ?></td>
					<td><code><?php echo esc_html( $r->file ); ?></code></td>
					<td><?php echo esc_html( $r->ip ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>

		<?php elseif ( 'alf' === $tab ) : ?>
			<?php echo function_exists( 'apt_alf_admin_box' ) ? apt_alf_admin_box() : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?>
			<?php if ( ! empty( $_GET['continue'] ) && get_option( 'apt_alf_queue' ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<p class="description"><?php esc_html_e( 'Building… this page continues by itself every few seconds; keep it open, or close it and it carries on in the background.', 'akseki-vi' ); ?></p>
				<meta http-equiv="refresh" content="3;url=<?php echo esc_url( apt_mem_admin_url( 'alf_run', array( 'tab' => 'alf' ) ) ); ?>">
			<?php endif; ?>

		<?php else : ?>
			<form method="post" class="apt-mem-settings">
				<?php wp_nonce_field( 'apt_mem_admin' ); ?>
				<input type="hidden" name="apt_mem_a" value="settings"><input type="hidden" name="tab" value="settings">
				<table class="form-table" role="presentation">
					<tr><th><label for="m-notify"><?php esc_html_e( 'Email new requests to', 'akseki-vi' ); ?></label></th><td><input id="m-notify" type="email" class="regular-text" name="notify" value="<?php echo esc_attr( $s['notify'] ); ?>"></td></tr>
					<tr><th><label for="m-domains"><?php esc_html_e( 'Official email domains', 'akseki-vi' ); ?></label></th><td><textarea id="m-domains" name="domains" rows="6" class="large-text code"><?php echo esc_textarea( $s['domains'] ); ?></textarea><p class="description"><?php esc_html_e( 'One per line. "asean.org" also matches its subdomains; a line starting with a dot (".go.id") matches any address ending with it. Requests from other domains are flagged for a closer check; every request is still approved by a person.', 'akseki-vi' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Yearly re-confirmation', 'akseki-vi' ); ?></th><td><label><input type="checkbox" name="reconfirm" value="1" <?php checked( $s['reconfirm'] ); ?>> <?php esc_html_e( 'Members confirm once a year that they still need access (reminder 30 days before; otherwise access ends until renewed here).', 'akseki-vi' ); ?></label></td></tr>
					<tr><th><label for="m-elig"><?php esc_html_e( 'Who is eligible (shown on the request form)', 'akseki-vi' ); ?></label></th><td><textarea id="m-elig" name="eligibility" rows="3" class="large-text"><?php echo esc_textarea( $s['eligibility'] ); ?></textarea></td></tr>
					<tr><th><label for="m-priv"><?php esc_html_e( 'Privacy note (shown on the request form)', 'akseki-vi' ); ?></label></th><td><textarea id="m-priv" name="privacy" rows="2" class="large-text"><?php echo esc_textarea( $s['privacy'] ); ?></textarea></td></tr>
					<tr><th><label for="m-docs"><?php esc_html_e( 'Documents page', 'akseki-vi' ); ?></label></th><td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'docs_page',
							'id'                => 'm-docs',
							'selected'          => (int) $s['docs_page'],
							'post_status'       => array( 'publish', 'private' ),
							'show_option_none'  => __( 'Default: /limited-access-documents-2/', 'akseki-vi' ),
							'option_none_value' => 0,
						) );
						?>
						<p class="description"><?php esc_html_e( 'The page with the list of Limited Access documents; members reach it from the Members page.', 'akseki-vi' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Shared Limited Access account', 'akseki-vi' ); ?></th><td>
						<p><label><input type="radio" name="shared" value="on" <?php checked( 'on', $s['shared'] ); ?>> <?php esc_html_e( 'Still works (during the move to individual accounts)', 'akseki-vi' ); ?></label><br>
						<label><input type="radio" name="shared" value="off" <?php checked( 'off', $s['shared'] ); ?>> <?php esc_html_e( 'Switched off: it can no longer sign in, and the old login page leads to the Members page', 'akseki-vi' ); ?></label></p>
						<p><label for="m-su"><?php esc_html_e( 'Its username:', 'akseki-vi' ); ?></label> <input id="m-su" name="shared_user" value="<?php echo esc_attr( $s['shared_user'] ); ?>" placeholder="<?php echo esc_attr( apt_mem_shared_username() ); ?>"> <span class="description"><?php esc_html_e( 'Empty: taken from the old login page.', 'akseki-vi' ); ?></span></p>
					</td></tr>
				</table>
				<p><button class="button button-primary"><?php esc_html_e( 'Save settings', 'akseki-vi' ); ?></button></p>
			</form>
		<?php endif; ?>
	</div>
	<style>
		.apt-mem-admin .widefat td { vertical-align: top; }
		.apt-mem-tag { display: inline-block; font-size: 11px; font-weight: 600; padding: 0 7px; border-radius: 9px; border: 1px solid; margin-top: 4px; }
		.apt-mem-tag.is-ok { background: #edfaef; color: #00450c; border-color: #68de7c; }
		.apt-mem-tag.is-warn { background: #fcf0d1; color: #6e4b00; border-color: #f0c33c; }
		.apt-mem-acts { white-space: nowrap; }
		.apt-mem-quiet { display: block; margin-top: 6px; color: #646970; }
		.apt-mem-danger { color: #b32d2e; }
	</style>
	<?php
}
