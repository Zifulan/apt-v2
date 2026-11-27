<?php
/**
 * Posts → Source watch: finds new ASEAN Plus Three items on other sites.
 *
 * - Reads RSS feeds twice a day (WP-Cron): by default asean.org's search
 *   feed for "ASEAN Plus Three", newest first. Any feed can be added; for
 *   sites without one, a Google Alert delivered as an RSS feed.
 * - An item is "In title" when one of the words is in its title, a
 *   "Mention" when only in its text.
 * - Words to edit out (default "Secretary-General": the site does not refer
 *   to the Secretary-General): items that contain them are listed as usual
 *   with a tag, and their drafts get a "Before publishing" box listing every
 *   sentence (and the title) that still contains them. Nothing is deleted
 *   automatically: a blind cut could break an official text.
 * - Items already on the site (same title, ignoring capitals and
 *   punctuation, or the same address in a post's "source" field) are marked
 *   as such.
 * - New items are emailed through wp_mail (APT Mail), one email per check.
 * - "Create draft" makes a draft post with the title, date, text from the
 *   feed and the source address (the "source" custom field, shown in the
 *   public "Source:" line).
 *
 * Data: options apt_sw_settings, apt_sw_items (not autoloaded) and
 * apt_sw_feeds (status of each feed).
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const APT_SW_CRON = 'apt_source_watch_check';

/** Settings, with the defaults. */
function apt_sw_settings() {
	$s = get_option( 'apt_sw_settings', array() );
	$s = is_array( $s ) ? $s : array();
	return array_merge(
		array(
			'feeds' => array(
				array( 'label' => 'asean.org: "ASEAN Plus Three"', 'url' => 'https://asean.org/?s=%22ASEAN+Plus+Three%22&orderby=date&order=DESC&feed=rss2' ),
			),
			'words' => 'ASEAN Plus Three, ASEAN+3, APT',
			'edit_out' => 'Secretary-General',
			'email' => get_option( 'admin_email' ),
		),
		$s
	);
}

/** A comma-separated list as an array. */
function apt_sw_list( $text ) {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) $text ) ), 'strlen' ) );
}

/**
 * Whether the text contains one of the words, as a whole word. A word in
 * capitals only (APT) must match its capitals, so "adapt" or "Apt." in a
 * sentence don't count; other words ignore capitals.
 */
function apt_sw_has_word( $text, $words ) {
	$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
	foreach ( $words as $w ) {
		$caps = preg_match( '/^[A-Z0-9+]+$/', $w ) && preg_match( '/[A-Z]/', $w ) && strlen( $w ) <= 4;
		$re   = '/(?<![\p{L}\p{N}])' . preg_quote( $w, '/' ) . '(?![\p{L}\p{N}])/u' . ( $caps ? '' : 'i' );
		if ( preg_match( $re, $text ) ) {
			return true;
		}
	}
	return false;
}

/** A title reduced to letters and numbers, for comparing. */
function apt_sw_norm_title( $title ) {
	$t = html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES, 'UTF-8' );
	return preg_replace( '/[^\p{L}\p{N}]+/u', '', mb_strtolower( $t, 'UTF-8' ) );
}

/**
 * An item's address without tracking: the real address of a Google Alerts
 * link, and without _rt, utm_… and similar parameters.
 */
function apt_sw_clean_url( $url ) {
	$url   = html_entity_decode( trim( (string) $url ), ENT_QUOTES, 'UTF-8' );
	$parts = wp_parse_url( $url );
	if ( ! $parts || empty( $parts['host'] ) ) {
		return $url;
	}
	parse_str( isset( $parts['query'] ) ? $parts['query'] : '', $q );
	if ( preg_match( '/(^|\.)google\.[a-z.]+$/', $parts['host'] ) && ! empty( $q['url'] ) && preg_match( '#^https?://#', $q['url'] ) ) {
		return apt_sw_clean_url( $q['url'] );
	}
	foreach ( array_keys( $q ) as $k ) {
		if ( preg_match( '/^(_rt|_rt_nonce|utm_.*|fbclid|gclid|mc_cid|mc_eid)$/i', $k ) ) {
			unset( $q[ $k ] );
		}
	}
	$out = ( isset( $parts['scheme'] ) ? $parts['scheme'] : 'https' ) . '://' . $parts['host'] . ( isset( $parts['path'] ) ? $parts['path'] : '/' );
	return $q ? $out . '?' . http_build_query( $q ) : $out;
}

/** A URL reduced for comparing (no scheme, www, trailing slash or query). */
function apt_sw_norm_url( $url ) {
	$url = strtolower( preg_replace( '/[?#].*$/', '', apt_sw_clean_url( $url ) ) );
	return rtrim( preg_replace( '#^https?://(www\.)?#', '', $url ), '/' );
}

/** What is on the site already: normalized titles and source addresses => post ID. */
function apt_sw_site_index() {
	global $wpdb;
	$titles = array();
	$rows   = $wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status IN ('publish','future','draft','pending','private')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( $rows as $r ) {
		$titles[ apt_sw_norm_title( $r->post_title ) ] = (int) $r->ID;
	}
	$urls = array();
	$rows = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'source'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( $rows as $r ) {
		if ( preg_match( '#^https?://#i', trim( $r->meta_value ) ) ) {
			$urls[ apt_sw_norm_url( $r->meta_value ) ] = (int) $r->post_id;
		}
	}
	return array( 'titles' => $titles, 'urls' => $urls );
}

/** The stored items: key => item. */
function apt_sw_items() {
	$items = get_option( 'apt_sw_items', array() );
	return is_array( $items ) ? $items : array();
}

/**
 * Check all feeds. Returns the items found new in this check.
 *
 * @param bool $send_email Email the new items.
 */
function apt_sw_check( $send_email = true ) {
	if ( ! function_exists( 'fetch_feed' ) ) {
		require_once ABSPATH . WPINC . '/feed.php';
	}
	$s      = apt_sw_settings();
	$words  = apt_sw_list( $s['words'] );
	$out_w  = apt_sw_list( $s['edit_out'] );
	$items  = apt_sw_items();
	$status = array();
	$found  = array();
	$index  = apt_sw_site_index();

	// The feeds are cached by WordPress; an hour keeps the check fresh.
	$ttl = function () {
		return HOUR_IN_SECONDS;
	};
	add_filter( 'wp_feed_cache_transient_lifetime', $ttl );
	foreach ( $s['feeds'] as $feed ) {
		$rss = fetch_feed( $feed['url'] );
		if ( is_wp_error( $rss ) ) {
			$status[ $feed['url'] ] = array( 'ok' => false, 'time' => time(), 'error' => $rss->get_error_message() );
			continue;
		}
		$list                   = $rss->get_items( 0, 30 );
		$status[ $feed['url'] ] = array( 'ok' => true, 'time' => time(), 'count' => count( $list ) );
		foreach ( $list as $it ) {
			$link = apt_sw_clean_url( $it->get_permalink() );
			$key  = md5( apt_sw_norm_url( $link ) );
			if ( '' === $link || isset( $items[ $key ] ) ) {
				continue; // seen before: keep its status
			}
			$title = trim( html_entity_decode( wp_strip_all_tags( (string) $it->get_title() ), ENT_QUOTES, 'UTF-8' ) );
			$text  = (string) $it->get_content();
			$in_t  = apt_sw_has_word( $title, $words );
			if ( ! $in_t && ! apt_sw_has_word( $text, $words ) ) {
				continue; // not about ASEAN Plus Three
			}
			$item = array(
				'title'   => $title,
				'link'    => $link,
				'host'    => preg_replace( '/^www\./', '', (string) wp_parse_url( $link, PHP_URL_HOST ) ),
				'feed'    => $feed['label'],
				'date'    => (int) $it->get_date( 'U' ),
				'found'   => time(),
				'match'   => $in_t ? 'title' : 'mention',
				'post_id' => 0,
				'edit'    => $out_w && ( apt_sw_has_word( $title, $out_w ) || apt_sw_has_word( $text, $out_w ) ),
			);
			$nt  = apt_sw_norm_title( $title );
			$have = isset( $index['urls'][ apt_sw_norm_url( $link ) ] ) ? $index['urls'][ apt_sw_norm_url( $link ) ] : ( isset( $index['titles'][ $nt ] ) ? $index['titles'][ $nt ] : 0 );
			if ( $have ) {
				$item['status']  = 'have';
				$item['post_id'] = $have;
			} else {
				$item['status'] = $in_t ? 'new' : 'mention';
				$found[]        = $item;
			}
			$items[ $key ] = $item;
		}
	}
	remove_filter( 'wp_feed_cache_transient_lifetime', $ttl );

	// Keep the list small: drop handled items older than a year.
	$year = time() - YEAR_IN_SECONDS;
	foreach ( $items as $k => $it ) {
		if ( in_array( $it['status'], array( 'have', 'ignored', 'drafted' ), true ) && $it['found'] < $year ) {
			unset( $items[ $k ] );
		}
	}
	update_option( 'apt_sw_items', $items, false );
	update_option( 'apt_sw_feeds', $status, false );
	update_option( 'apt_sw_last', time(), false );

	$new = array_filter( $found, function ( $i ) { return 'new' === $i['status']; } );
	if ( $send_email && $new && is_email( $s['email'] ) ) {
		apt_sw_email( $s['email'], $new, count( $found ) - count( $new ) );
	}
	return $found;
}
add_action( APT_SW_CRON, 'apt_sw_check' );

// Twice a day; removed again if the theme is switched off.
add_action( 'init', function () {
	if ( ! wp_next_scheduled( APT_SW_CRON ) ) {
		wp_schedule_event( time() + 300, 'twicedaily', APT_SW_CRON );
	}
} );
add_action( 'switch_theme', function () {
	wp_clear_scheduled_hook( APT_SW_CRON );
} );

/** The email about new items. */
function apt_sw_email( $to, $new, $mentions ) {
	$url  = admin_url( 'edit.php?page=apt-source-watch' );
	/* translators: %d: count */
	$subj = sprintf( _n( '[ASEAN Plus Three] %d new item to check', '[ASEAN Plus Three] %d new items to check', count( $new ), 'akseki-vi' ), count( $new ) );
	$body = '<p>' . esc_html__( 'Source watch found these new ASEAN Plus Three items:', 'akseki-vi' ) . '</p><ul>';
	foreach ( $new as $i ) {
		$note  = ! empty( $i['edit'] ) ? ' · ' . sprintf( /* translators: %s: words */ __( 'edit out: %s', 'akseki-vi' ), apt_sw_settings()['edit_out'] ) : '';
		$body .= '<li style="margin:0 0 10px"><a href="' . esc_url( $i['link'] ) . '">' . esc_html( $i['title'] ) . '</a><br><span style="color:#5f6368">' . esc_html( $i['host'] . ( $i['date'] ? ' · ' . wp_date( 'j M Y', $i['date'] ) : '' ) . $note ) . '</span></li>';
	}
	$body .= '</ul>';
	if ( $mentions ) {
		/* translators: %d: count */
		$body .= '<p style="color:#5f6368">' . esc_html( sprintf( _n( 'Also %d item that only mentions ASEAN Plus Three in the text.', 'Also %d items that only mention ASEAN Plus Three in the text.', $mentions, 'akseki-vi' ), $mentions ) ) . '</p>';
	}
	$body .= '<p><a href="' . esc_url( $url ) . '" style="display:inline-block;background:#2271b1;color:#fff;text-decoration:none;padding:9px 16px;border-radius:4px;font-weight:bold">' . esc_html__( 'Open Source watch', 'akseki-vi' ) . '</a></p>'
		. '<p style="color:#5f6368;font-size:13px">' . esc_html__( 'Sent by the APT website. Change the address under Posts → Source watch.', 'akseki-vi' ) . '</p>';
	return wp_mail( $to, $subj, '<div style="font:14px/1.5 Arial,sans-serif;color:#202124">' . $body . '</div>', array( 'Content-Type: text/html; charset=UTF-8' ) );
}

/**
 * The text of a feed item for a draft: paragraphs, headings, lists, tables,
 * bold and links only. Photos and galleries are dropped (they would load
 * from the other site; add the post's own featured image instead), and so is
 * the feed's "The post … appeared first on …" line.
 */
function apt_sw_clean_content( $html ) {
	$html = preg_replace( '#<(figure|script|style|iframe|noscript)\b.*?</\1>#is', '', (string) $html );
	$html = wp_kses( $html, array(
		'p' => array(), 'br' => array(), 'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(),
		'a' => array( 'href' => true ), 'ul' => array(), 'ol' => array(), 'li' => array(), 'blockquote' => array(),
		'h2' => array(), 'h3' => array(), 'h4' => array(), 'table' => array(), 'thead' => array(), 'tbody' => array(),
		'tr' => array(), 'th' => array(), 'td' => array(),
	) );
	$html = preg_replace( '#<p>\s*The post .*?appeared first on.*?</p>#is', '', $html );
	$html = preg_replace( '#<p>(\s|&nbsp;)*</p>#i', '', $html );
	return trim( $html );
}

/** Make a draft post from an item. Returns the post ID or 0. */
function apt_sw_create_draft( $key ) {
	$items = apt_sw_items();
	if ( ! isset( $items[ $key ] ) ) {
		return 0;
	}
	$it   = $items[ $key ];
	$text = '';
	// The text from the feed, if it is still there (asean.org gives the full text).
	foreach ( apt_sw_settings()['feeds'] as $feed ) {
		if ( $feed['label'] !== $it['feed'] ) {
			continue;
		}
		if ( ! function_exists( 'fetch_feed' ) ) {
			require_once ABSPATH . WPINC . '/feed.php';
		}
		$rss = fetch_feed( $feed['url'] );
		if ( ! is_wp_error( $rss ) ) {
			foreach ( $rss->get_items( 0, 30 ) as $fi ) {
				if ( apt_sw_norm_url( $fi->get_permalink() ) === apt_sw_norm_url( $it['link'] ) ) {
					$text = apt_sw_clean_content( (string) $fi->get_content() );
					break;
				}
			}
		}
	}
	$date = $it['date'] ? $it['date'] : time();
	$id   = wp_insert_post(
		array(
			'post_type'     => 'post',
			'post_status'   => 'draft',
			'post_title'    => $it['title'],
			'post_content'  => $text,
			'post_date'     => wp_date( 'Y-m-d H:i:s', $date ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $date ),
		),
		true
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	add_post_meta( $id, 'source', $it['link'] );
	add_post_meta( $id, '_apt_sw_draft', 1 ); // shows the "Before publishing" box
	$items[ $key ]['status']  = 'drafted';
	$items[ $key ]['post_id'] = (int) $id;
	update_option( 'apt_sw_items', $items, false );
	return (int) $id;
}

/* ---------- The screen ---------- */

/** Statuses as shown: key => label. */
function apt_sw_views() {
	return array(
		'all'     => __( 'All', 'akseki-vi' ),
		'new'     => __( 'New', 'akseki-vi' ),
		'mention' => __( 'Mentions', 'akseki-vi' ),
		'have'    => __( 'Already on site', 'akseki-vi' ),
		'drafted' => __( 'Drafted', 'akseki-vi' ),
		'ignored' => __( 'Ignored', 'akseki-vi' ),
	);
}

add_action( 'admin_menu', function () {
	$new  = count( array_filter( apt_sw_items(), function ( $i ) { return 'new' === $i['status']; } ) );
	$menu = __( 'Source watch', 'akseki-vi' ) . ( $new ? ' <span class="awaiting-mod count-' . $new . '"><span class="pending-count">' . $new . '</span></span>' : '' );
	$hook = add_posts_page( __( 'Source watch', 'akseki-vi' ), $menu, 'edit_others_posts', 'apt-source-watch', 'apt_sw_page' );
	add_action( 'load-' . $hook, 'apt_sw_actions' );
} );

/** The buttons and the settings form (before the screen is shown). */
function apt_sw_actions() {
	if ( empty( $_REQUEST['apt_sw'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	check_admin_referer( 'apt_sw' );
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'akseki-vi' ) );
	}
	$do   = sanitize_key( wp_unslash( $_REQUEST['apt_sw'] ) );
	$key  = isset( $_REQUEST['key'] ) ? preg_replace( '/[^a-f0-9]/', '', wp_unslash( $_REQUEST['key'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$view = isset( $_REQUEST['view'] ) ? sanitize_key( wp_unslash( $_REQUEST['view'] ) ) : 'all';
	$args = array( 'page' => 'apt-source-watch', 'view' => $view );
	$items = apt_sw_items();

	if ( 'check' === $do ) {
		$found       = apt_sw_check( false );
		$args['msg'] = 'checked';
		$args['n']   = count( $found );
	} elseif ( 'draft' === $do && $key ) {
		$id          = apt_sw_create_draft( $key );
		$args['msg'] = $id ? 'drafted' : 'error';
		$args['post'] = $id;
	} elseif ( in_array( $do, array( 'ignore', 'restore' ), true ) && isset( $items[ $key ] ) ) {
		$items[ $key ]['status'] = 'ignore' === $do ? 'ignored' : ( 'title' === $items[ $key ]['match'] ? 'new' : 'mention' );
		update_option( 'apt_sw_items', $items, false );
	} elseif ( 'save' === $do ) {
		$s     = apt_sw_settings();
		$feeds = array();
		foreach ( (array) ( isset( $_POST['feeds'] ) ? wp_unslash( $_POST['feeds'] ) : array() ) as $f ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized below
			$url = esc_url_raw( trim( isset( $f['url'] ) ? $f['url'] : '' ), array( 'http', 'https' ) );
			if ( $url && empty( $f['remove'] ) ) {
				$label   = sanitize_text_field( isset( $f['label'] ) ? $f['label'] : '' );
				$feeds[] = array( 'label' => '' !== $label ? $label : (string) wp_parse_url( $url, PHP_URL_HOST ), 'url' => $url );
			}
		}
		$add = esc_url_raw( trim( isset( $_POST['new_feed'] ) ? wp_unslash( $_POST['new_feed'] ) : '' ), array( 'http', 'https' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( $add ) {
			$label   = sanitize_text_field( isset( $_POST['new_label'] ) ? wp_unslash( $_POST['new_label'] ) : '' );
			$feeds[] = array( 'label' => '' !== $label ? $label : (string) wp_parse_url( $add, PHP_URL_HOST ), 'url' => $add );
		}
		$email = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' );
		update_option( 'apt_sw_settings', array(
			'feeds' => $feeds,
			'words' => sanitize_text_field( isset( $_POST['words'] ) ? wp_unslash( $_POST['words'] ) : $s['words'] ),
			'edit_out' => sanitize_text_field( isset( $_POST['edit_out'] ) ? wp_unslash( $_POST['edit_out'] ) : '' ),
			'email' => $email,
		), false );
		$args['msg'] = $add ? 'feed-added' : 'saved';
		if ( $add ) {
			apt_sw_check( false ); // read the new feed straight away
		}
	}
	wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php' ) ) );
	exit;
}

/** A button that runs an action. */
function apt_sw_button( $do, $label, $key, $view, $primary = false ) {
	$url = wp_nonce_url( add_query_arg( array( 'page' => 'apt-source-watch', 'apt_sw' => $do, 'key' => $key, 'view' => $view ), admin_url( 'edit.php' ) ), 'apt_sw' );
	return '<a class="button button-small' . ( $primary ? ' button-primary' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
}

function apt_sw_page() {
	$s      = apt_sw_settings();
	$items  = apt_sw_items();
	$feeds  = get_option( 'apt_sw_feeds', array() );
	$last   = (int) get_option( 'apt_sw_last' );
	$next   = wp_next_scheduled( APT_SW_CRON );
	$views  = apt_sw_views();
	$view   = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification
	$view   = isset( $views[ $view ] ) ? $view : 'all';
	$msg    = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$counts = array_count_values( wp_list_pluck( $items, 'status' ) );
	$shown  = array_filter( $items, function ( $i ) use ( $view ) {
		return 'all' === $view || $view === $i['status'];
	} );
	uasort( $shown, function ( $a, $b ) {
		$order = array( 'new' => 0, 'mention' => 1, 'drafted' => 2, 'have' => 3, 'ignored' => 4 );
		return array( $order[ $a['status'] ], -$a['date'] ) <=> array( $order[ $b['status'] ], -$b['date'] );
	} );
	$base = admin_url( 'edit.php?page=apt-source-watch' );
	?>
	<div class="wrap apt-sw">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Source watch', 'akseki-vi' ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'apt_sw' => 'check', 'view' => $view ), $base ), 'apt_sw' ) ); ?>"><?php esc_html_e( 'Check now', 'akseki-vi' ); ?></a>
		<hr class="wp-header-end">
		<p class="apt-sw-lead">
			<?php esc_html_e( 'New ASEAN Plus Three items on the sites below, checked twice a day.', 'akseki-vi' ); ?>
			<?php if ( $last ) { echo esc_html( sprintf( /* translators: %s: date */ __( 'Last check: %s.', 'akseki-vi' ), wp_date( 'j M Y, H:i', $last ) ) ); } ?>
			<?php if ( $next ) { echo esc_html( sprintf( /* translators: %s: date */ __( 'Next: %s.', 'akseki-vi' ), wp_date( 'j M Y, H:i', $next ) ) ); } ?>
		</p>

		<?php if ( 'drafted' === $msg && ! empty( $_GET['post'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<?php $pid = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Draft created:', 'akseki-vi' ); ?> <strong><?php echo esc_html( get_the_title( $pid ) ); ?></strong>. <a href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>"><?php esc_html_e( 'Edit the draft', 'akseki-vi' ); ?></a> <?php esc_html_e( '(add the category, short name, photo; check the text; publish).', 'akseki-vi' ); ?></p></div>
		<?php elseif ( 'checked' === $msg ) : ?>
			<?php $n = isset( $_GET['n'] ) ? absint( $_GET['n'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-info is-dismissible"><p><?php echo esc_html( $n ? sprintf( /* translators: %d: count */ _n( 'Checked: %d new item.', 'Checked: %d new items.', $n, 'akseki-vi' ), $n ) : __( 'Checked: nothing new.', 'akseki-vi' ) ); ?></p></div>
		<?php elseif ( in_array( $msg, array( 'saved', 'feed-added' ), true ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( 'feed-added' === $msg ? __( 'Feed added and read.', 'akseki-vi' ) : __( 'Settings saved.', 'akseki-vi' ) ); ?></p></div>
		<?php elseif ( 'error' === $msg ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'The draft could not be created.', 'akseki-vi' ); ?></p></div>
		<?php endif; ?>

		<ul class="subsubsub">
			<?php
			$links = array();
			foreach ( $views as $k => $label ) {
				$c       = 'all' === $k ? count( $items ) : ( isset( $counts[ $k ] ) ? $counts[ $k ] : 0 );
				$links[] = '<li><a href="' . esc_url( add_query_arg( 'view', $k, $base ) ) . '"' . ( $k === $view ? ' class="current" aria-current="page"' : '' ) . '>' . esc_html( $label ) . ' <span class="count">(' . (int) $c . ')</span></a>';
			}
			echo implode( ' |</li>', $links ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above
			?>
		</ul>

		<div class="apt-sw-cols">
			<div>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Item', 'akseki-vi' ); ?></th><th style="width:110px"><?php esc_html_e( 'Published', 'akseki-vi' ); ?></th><th style="width:210px"><?php esc_html_e( 'Action', 'akseki-vi' ); ?></th></tr></thead>
					<tbody>
					<?php if ( ! $shown ) : ?>
						<tr><td colspan="3"><?php echo esc_html( $last ? __( 'Nothing here.', 'akseki-vi' ) : __( 'Not checked yet. Click "Check now".', 'akseki-vi' ) ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $shown as $key => $it ) : ?>
						<tr>
							<td>
								<a class="apt-sw-title" href="<?php echo esc_url( $it['link'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $it['title'] ); ?></a>
								<div class="apt-sw-meta">
									<?php
									$tags = array(
										'title'   => array( 'is-title', __( 'In title', 'akseki-vi' ) ),
										'mention' => array( 'is-mention', __( 'Mention', 'akseki-vi' ) ),
									);
									if ( 'have' === $it['status'] ) {
										echo '<span class="apt-sw-tag is-have">' . esc_html__( 'Already on site', 'akseki-vi' ) . '</span>';
									} else {
										echo '<span class="apt-sw-tag ' . esc_attr( $tags[ $it['match'] ][0] ) . '">' . esc_html( $tags[ $it['match'] ][1] ) . '</span>';
									}
									if ( ! empty( $it['edit'] ) && 'have' !== $it['status'] ) {
										/* translators: %s: words */
										echo '<span class="apt-sw-tag is-edit">' . esc_html( sprintf( __( 'Edit out: %s', 'akseki-vi' ), $s['edit_out'] ) ) . '</span>';
									}
									echo esc_html( $it['host'] . ( $it['feed'] && false === strpos( $it['feed'], $it['host'] ) ? ' · ' . $it['feed'] : '' ) );
									if ( 'have' === $it['status'] && $it['post_id'] ) {
										echo ' · ' . esc_html__( 'matches', 'akseki-vi' ) . ' <a href="' . esc_url( get_edit_post_link( $it['post_id'] ) ) . '">' . esc_html( get_the_title( $it['post_id'] ) ) . '</a>';
									} elseif ( 'mention' === $it['match'] && in_array( $it['status'], array( 'mention', 'ignored' ), true ) ) {
										echo ' · ' . esc_html__( 'the words appear in the text, not the title', 'akseki-vi' );
									}
									?>
								</div>
							</td>
							<td><?php echo $it['date'] ? esc_html( wp_date( 'j M Y', $it['date'] ) ) : '&mdash;'; ?></td>
							<td>
								<?php
								// phpcs:disable WordPress.Security.EscapeOutput -- escaped inside apt_sw_button()
								if ( in_array( $it['status'], array( 'new', 'mention' ), true ) ) {
									echo apt_sw_button( 'draft', __( 'Create draft', 'akseki-vi' ), $key, $view, 'new' === $it['status'] ) . ' ' . apt_sw_button( 'ignore', __( 'Ignore', 'akseki-vi' ), $key, $view );
								} elseif ( 'drafted' === $it['status'] ) {
									echo esc_html__( 'Drafted', 'akseki-vi' ) . ( get_post( $it['post_id'] ) ? ' · <a href="' . esc_url( get_edit_post_link( $it['post_id'] ) ) . '">' . esc_html__( 'Edit', 'akseki-vi' ) . '</a>' : '' );
								} elseif ( 'ignored' === $it['status'] ) {
									echo apt_sw_button( 'restore', __( 'Move back to the list', 'akseki-vi' ), $key, $view );
								} else {
									echo '<span class="apt-sw-muted">' . esc_html__( 'Nothing to do', 'akseki-vi' ) . '</span>';
								}
								// phpcs:enable
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description"><?php esc_html_e( '"Create draft" makes a draft post with the title, the date, the text from the feed and the source address (shown in the public "Source:" line). Add the category, short name and photo, check the text, then publish.', 'akseki-vi' ); ?></p>
			</div>

			<form method="post" class="apt-sw-side">
				<?php wp_nonce_field( 'apt_sw' ); ?>
				<input type="hidden" name="apt_sw" value="save">
				<input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>">
				<div class="postbox"><h2 class="hndle"><?php esc_html_e( 'Feeds', 'akseki-vi' ); ?></h2><div class="inside">
					<?php foreach ( $s['feeds'] as $i => $f ) : ?>
						<?php $st = isset( $feeds[ $f['url'] ] ) ? $feeds[ $f['url'] ] : null; ?>
						<div class="apt-sw-feed">
							<input type="text" name="feeds[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $f['label'] ); ?>" aria-label="<?php esc_attr_e( 'Name', 'akseki-vi' ); ?>">
							<input type="url" name="feeds[<?php echo (int) $i; ?>][url]" value="<?php echo esc_attr( $f['url'] ); ?>" aria-label="<?php esc_attr_e( 'Feed address', 'akseki-vi' ); ?>">
							<?php if ( $st ) : ?>
								<span class="<?php echo $st['ok'] ? 'apt-sw-ok' : 'apt-sw-err'; ?>"><?php echo esc_html( $st['ok'] ? sprintf( /* translators: 1: count, 2: time */ __( '✓ %1$d items, checked %2$s', 'akseki-vi' ), $st['count'], wp_date( 'j M, H:i', $st['time'] ) ) : sprintf( /* translators: %s: error */ __( 'Could not read it: %s', 'akseki-vi' ), $st['error'] ) ); ?></span>
							<?php endif; ?>
							<label class="apt-sw-remove"><input type="checkbox" name="feeds[<?php echo (int) $i; ?>][remove]" value="1"> <?php esc_html_e( 'Remove', 'akseki-vi' ); ?></label>
						</div>
					<?php endforeach; ?>
					<p><label for="apt-sw-new"><strong><?php esc_html_e( 'Add a feed', 'akseki-vi' ); ?></strong></label>
						<input type="url" id="apt-sw-new" name="new_feed" placeholder="<?php esc_attr_e( 'Paste an RSS feed address', 'akseki-vi' ); ?>">
						<input type="text" name="new_label" placeholder="<?php esc_attr_e( 'Name (optional)', 'akseki-vi' ); ?>"></p>
					<p class="description"><?php esc_html_e( 'For sites without a feed: create a Google Alert (google.com/alerts), choose "Show options" → "Deliver to: RSS feed", then copy the feed\'s address here.', 'akseki-vi' ); ?></p>
				</div></div>
				<div class="postbox"><h2 class="hndle"><?php esc_html_e( 'Settings', 'akseki-vi' ); ?></h2><div class="inside">
					<p><label for="apt-sw-words"><strong><?php esc_html_e( 'Words to look for', 'akseki-vi' ); ?></strong></label>
						<input type="text" id="apt-sw-words" name="words" value="<?php echo esc_attr( $s['words'] ); ?>">
						<span class="description"><?php esc_html_e( 'Separated by commas. "In title" when one is in the title; otherwise a "Mention". Short words in capitals (APT) must match their capitals.', 'akseki-vi' ); ?></span></p>
					<p><label for="apt-sw-out"><strong><?php esc_html_e( 'Words to edit out', 'akseki-vi' ); ?></strong></label>
						<input type="text" id="apt-sw-out" name="edit_out" value="<?php echo esc_attr( $s['edit_out'] ); ?>">
						<span class="description"><?php esc_html_e( 'Items that contain them get a tag, and their drafts list every sentence that still contains them, to remove or reword before publishing.', 'akseki-vi' ); ?></span></p>
					<p><label for="apt-sw-email"><strong><?php esc_html_e( 'Email new items to', 'akseki-vi' ); ?></strong></label>
						<input type="email" id="apt-sw-email" name="email" value="<?php echo esc_attr( $s['email'] ); ?>">
						<span class="description"><?php esc_html_e( 'One email per check that finds something new (through APT Mail). Leave empty for no email.', 'akseki-vi' ); ?></span></p>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'akseki-vi' ); ?></button></p>
				</div></div>
			</form>
		</div>
	</div>
	<style>
		.apt-sw-lead { font-size: 14px; color: #50575e; }
		.apt-sw .subsubsub { float: none; margin: 8px 0 10px; }
		.apt-sw-cols { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 20px; max-width: 1360px; }
		@media (max-width: 1100px) { .apt-sw-cols { grid-template-columns: 1fr; } }
		.apt-sw-title { font-weight: 600; font-size: 14px; text-decoration: none; }
		.apt-sw-meta { margin-top: 4px; color: #646970; font-size: 12px; }
		.apt-sw-tag { display: inline-block; font-size: 11px; font-weight: 600; padding: 0 7px; border-radius: 9px; margin-right: 6px; border: 1px solid; }
		.apt-sw-tag.is-title { background: #edfaef; color: #00450c; border-color: #68de7c; }
		.apt-sw-tag.is-mention { background: #f0f0f1; color: #50575e; border-color: #c3c4c7; }
		.apt-sw-tag.is-have { background: #f0f6fc; color: #0a4b78; border-color: #72aee6; }
		.apt-sw-tag.is-edit { background: #fcf0d1; color: #6e4b00; border-color: #f0c33c; margin-left: 6px; }
		.apt-sw-muted { color: #646970; }
		.apt-sw-side .postbox .hndle { font-size: 14px; padding: 10px 12px; margin: 0; border-bottom: 1px solid #c3c4c7; }
		.apt-sw-side input[type=text], .apt-sw-side input[type=url], .apt-sw-side input[type=email] { width: 100%; margin: 2px 0; }
		.apt-sw-feed { border: 1px solid #dcdcde; border-radius: 4px; padding: 8px; margin-bottom: 8px; }
		.apt-sw-ok { color: #008a20; font-weight: 600; display: block; margin-top: 4px; }
		.apt-sw-err { color: #b32d2e; font-weight: 600; display: block; margin-top: 4px; }
		.apt-sw-remove { display: block; margin-top: 4px; color: #646970; }
	</style>
	<?php
}

/* ---------- "Before publishing" box on drafts from Source watch ---------- */

/** The title and the sentences of a post that contain the words to edit out. */
function apt_sw_review_hits( $post ) {
	$words = apt_sw_list( apt_sw_settings()['edit_out'] );
	if ( ! $words ) {
		return array();
	}
	$hits = array();
	if ( apt_sw_has_word( $post->post_title, $words ) ) {
		$hits[] = array( __( 'Title', 'akseki-vi' ), $post->post_title );
	}
	$text = html_entity_decode( wp_strip_all_tags( str_replace( array( '</p>', '<br>', '<br />', '</li>' ), "\n", $post->post_content ) ), ENT_QUOTES, 'UTF-8' );
	foreach ( preg_split( '/(?<=[.!?])\s+|\R+/u', $text ) as $sentence ) {
		$sentence = trim( $sentence );
		if ( '' !== $sentence && apt_sw_has_word( $sentence, $words ) ) {
			$hits[] = array( __( 'Text', 'akseki-vi' ), $sentence );
		}
	}
	return $hits;
}

add_action( 'add_meta_boxes_post', function ( $post ) {
	if ( get_post_meta( $post->ID, '_apt_sw_draft', true ) && apt_sw_review_hits( $post ) ) {
		add_meta_box( 'apt-sw-review', __( 'Before publishing', 'akseki-vi' ), 'apt_sw_review_box', 'post', 'side', 'high' );
	}
} );

function apt_sw_review_box( $post ) {
	$hits = apt_sw_review_hits( $post );
	/* translators: %s: words */
	echo '<p><strong>' . esc_html( sprintf( __( 'Remove or reword "%s" in:', 'akseki-vi' ), apt_sw_settings()['edit_out'] ) ) . '</strong></p><ol style="margin-left:18px">';
	foreach ( $hits as $h ) {
		echo '<li style="margin-bottom:8px"><em>' . esc_html( $h[0] ) . ':</em> ' . esc_html( wp_html_excerpt( $h[1], 220, '…' ) ) . '</li>';
	}
	echo '</ol><p class="description">' . esc_html__( 'Updated when the draft is saved. The box goes away when none are left.', 'akseki-vi' ) . '</p>';
}
