<?php
/**
 * Tools → Content check: one page listing what needs an editor's attention,
 * so gaps are fixed before visitors see them. Read only; each item links to
 * where it is fixed.
 *
 * 1. Site structure: pages, categories and the image the theme's code relies
 *    on by ID. If one is deleted or recreated, parts of the site break
 *    silently.
 * 2. Meeting categories without a Theme or a Description (the "Topic" line).
 * 3. Theme pages: missing or unknown pillar, slugs that match no category.
 * 4. Statements of the last 12 months without a photo.
 * 5. Special tags that look mistyped (sm_…, "SM 11th", hasnocontents …).
 * 6. Limited Access links outside the protected folder.
 * 7. Shortcodes: which are used where and who provides them; any showing as
 *    raw text; plugins whose shortcodes no content uses.
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_management_page( __( 'Content check', 'akseki-vi' ), __( 'Content check', 'akseki-vi' ), 'edit_others_posts', 'apt-content-check', 'apt_content_check_page' );
} );

/** What the code relies on by ID: [ type, id, what it is used for ]. */
function apt_content_check_ids() {
	return array(
		array( 'page', 5733, __( 'Themes: parent of the theme pages', 'akseki-vi' ) ),
		array( 'page', 6262, __( 'Theme page for Track 1.5 and Track 2', 'akseki-vi' ) ),
		array( 'page', 4452, __( 'Limited Access login page', 'akseki-vi' ) ),
		array( 'page', 3363, __( 'Overview of ASEAN Plus Three Cooperation (shown on theme pages)', 'akseki-vi' ) ),
		array( 'page', 3236, __( 'Category descriptors: parent of the category description pages', 'akseki-vi' ) ),
		array( 'category', 7, __( 'Statements and Declarations: parent of all levels', 'akseki-vi' ) ),
		array( 'category', 41, __( 'Summit Level Statements', 'akseki-vi' ) ),
		array( 'category', 43, __( 'Ministerial-level Statements', 'akseki-vi' ) ),
		array( 'category', 275, __( 'CPR Plus Three level', 'akseki-vi' ) ),
		array( 'category', 280, __( 'SOM level', 'akseki-vi' ) ),
		array( 'category', 377, __( 'DG level', 'akseki-vi' ) ),
		array( 'category', 373, __( 'Working/Expert Group level', 'akseki-vi' ) ),
		array( 'category', 295, __( 'Other levels', 'akseki-vi' ) ),
		array( 'category', 399, __( 'Other tracks (Track 1.5 and Track 2)', 'akseki-vi' ) ),
		array( 'category', 40, __( 'Key Documents (home page Resources section)', 'akseki-vi' ) ),
		array( 'category', 9, __( 'Work Plans (home page Resources section, its own archive template)', 'akseki-vi' ) ),
		array( 'category', 8, __( 'Home page Resources section', 'akseki-vi' ) ),
		array( 'category', 45, __( 'Agenda and the home page Resources section', 'akseki-vi' ) ),
		array( 'attachment', 1007, __( 'Flags image, shown when a statement has no photo', 'akseki-vi' ) ),
	);
}

/** Meeting categories by level: slug => level name. */
function apt_content_check_meetings() {
	$levels = array(
		'akseki_get_ministrial_categories' => 'Ministerial',
		'akseki_get_som_categories'        => 'SOM',
		'akseki_get_dg_categories'         => 'DG',
		'akseki_get_wg_categories'         => 'Working/Expert Group',
		'akseki_get_othlev_categories'     => 'Other levels',
	);
	$out = array();
	foreach ( $levels as $fn => $level ) {
		if ( function_exists( $fn ) ) {
			foreach ( (array) call_user_func( $fn ) as $slug ) {
				$out[ $slug ] = $level;
			}
		}
	}
	return $out;
}

/** Tags that look like a special tag but are not one: [ term, hint ]. */
function apt_content_check_bad_tags() {
	$valid_sc = array( 'SC_year', 'SC_month', 'SC_nodate' );
	$flags    = array( 'hasnocontent', 'hyperlinked', 'noshorttitle', 'marker' );
	$bad      = array();
	foreach ( get_tags( array( 'hide_empty' => false ) ) as $tag ) {
		$name = $tag->name;
		$low  = strtolower( $name );
		if ( preg_match( '/^\s*s\s*m\b|^\s*sm[\s_:\-]/i', $name ) && ! preg_match( '/^SM_\S/', $name ) ) {
			$bad[] = array( $tag, __( 'Looks like a short name, but only "SM_" followed directly by the name works (e.g. SM_11th APT JSO).', 'akseki-vi' ) );
		} elseif ( preg_match( '/^\s*sc[\s_:\-]/i', $name ) && ! in_array( $name, $valid_sc, true ) ) {
			$bad[] = array( $tag, __( 'Looks like a date setting, but only SC_year, SC_month and SC_nodate work.', 'akseki-vi' ) );
		} else {
			foreach ( $flags as $flag ) {
				$compact = preg_replace( '/[\s_\-]/', '', $low );
				// Long names only: "marker" is too close to ordinary words (market).
				if ( strlen( $flag ) >= 10 && $name !== $flag && $tag->slug !== $flag && levenshtein( $compact, $flag ) <= 2 ) {
					/* translators: %s: the tag it resembles */
					$bad[] = array( $tag, sprintf( __( 'Looks like "%s", which the theme only recognises spelled exactly.', 'akseki-vi' ), $flag ) );
					break;
				}
			}
		}
	}
	return $bad;
}

/** A status label. */
function apt_content_check_badge( $ok, $text_ok = '', $text_bad = '' ) {
	return $ok
		? '<span style="color:#00a32a;font-weight:600">' . esc_html( $text_ok ? $text_ok : __( 'OK', 'akseki-vi' ) ) . '</span>'
		: '<span style="color:#b32d2e;font-weight:600">' . esc_html( $text_bad ? $text_bad : __( 'Needs attention', 'akseki-vi' ) ) . '</span>';
}

/** Who provides a registered shortcode: array( label, plugin folder or '' ). */
function apt_shortcode_provider( $tag ) {
	global $shortcode_tags;
	static $plugins = null;
	if ( null === $plugins ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = array();
		foreach ( get_plugins() as $file => $data ) {
			$plugins[ strtok( $file, '/' ) ] = $data['Name'];
		}
	}
	$cb   = isset( $shortcode_tags[ $tag ] ) ? $shortcode_tags[ $tag ] : null;
	$file = '';
	try {
		if ( is_string( $cb ) && false !== strpos( $cb, '::' ) ) {
			$cb = explode( '::', $cb, 2 );
		}
		if ( is_array( $cb ) ) {
			$file = ( new ReflectionMethod( $cb[0], $cb[1] ) )->getFileName();
		} elseif ( $cb instanceof Closure || ( is_string( $cb ) && function_exists( $cb ) ) ) {
			$file = ( new ReflectionFunction( $cb ) )->getFileName();
		}
	} catch ( ReflectionException $e ) {
		$file = '';
	}
	$file = wp_normalize_path( (string) $file );
	$pdir = wp_normalize_path( WP_PLUGIN_DIR ) . '/';
	if ( $file && 0 === strpos( $file, $pdir ) ) {
		$folder = strtok( substr( $file, strlen( $pdir ) ), '/' );
		return array( isset( $plugins[ $folder ] ) ? $plugins[ $folder ] : $folder, $folder );
	}
	if ( $file && 0 === strpos( $file, wp_normalize_path( get_template_directory() ) ) ) {
		return array( __( 'This theme', 'akseki-vi' ), '' );
	}
	if ( $file && 0 === strpos( $file, wp_normalize_path( ABSPATH . WPINC ) ) ) {
		return array( 'WordPress', '' );
	}
	return array( __( 'Unknown', 'akseki-vi' ), '' );
}

/**
 * Where shortcodes are used: posts and pages of every status (not trash),
 * widgets, and the theme's own templates (do_shortcode).
 * Returns array( 'used' => tag => [ [label, url], ... ], 'raw' => same, for
 * shortcode-like text that nothing provides, so it shows as raw text ).
 */
function apt_shortcode_usage() {
	global $wpdb;
	$used = array();
	$raw  = array();
	$add  = function ( $content, $where ) use ( &$used, &$raw ) {
		if ( false === strpos( (string) $content, '[' ) ) {
			return;
		}
		preg_match_all( '/\[(\[?)([a-zA-Z][\w\-]*)(\s[^\]]*)?\/?\]/', (string) $content, $m, PREG_SET_ORDER );
		$seen = array();
		foreach ( $m as $x ) {
			$tag = strtolower( $x[2] );
			if ( '[' === $x[1] || isset( $seen[ $tag ] ) ) {
				continue; // [[escaped]] or already counted here
			}
			$seen[ $tag ] = true;
			if ( shortcode_exists( $tag ) ) {
				$used[ $tag ][] = $where;
			} elseif ( false !== strpbrk( $tag, '_-' ) || ( isset( $x[3] ) && false !== strpos( $x[3], '=' ) ) ) {
				// Looks like a shortcode (name with _ or -, or attributes); plain
				// words in brackets such as [sic] or [1] are not counted.
				$raw[ $tag ][] = $where;
			}
		}
	};
	$last = 0;
	do {
		$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT ID, post_title, post_type, post_content FROM {$wpdb->posts}
			 WHERE ID > %d AND post_content LIKE %s
			 AND post_type NOT IN ('revision','attachment','nav_menu_item','customize_changeset','oembed_cache')
			 AND post_status NOT IN ('trash','auto-draft','inherit')
			 ORDER BY ID LIMIT 300",
			$last,
			'%[%'
		) );
		foreach ( $rows as $r ) {
			$last = (int) $r->ID;
			$add( $r->post_content, array( ( '' !== $r->post_title ? $r->post_title : '#' . $r->ID ) . ( 'post' === $r->post_type ? '' : ' (' . $r->post_type . ')' ), get_edit_post_link( $r->ID, 'raw' ) ) );
		}
	} while ( count( $rows ) === 300 );
	foreach ( array( 'widget_text' => 'text', 'widget_custom_html' => 'content', 'widget_block' => 'content' ) as $opt => $key ) {
		foreach ( (array) get_option( $opt, array() ) as $w ) {
			if ( is_array( $w ) && ! empty( $w[ $key ] ) ) {
				$add( $w[ $key ], array( __( 'A widget', 'akseki-vi' ), admin_url( 'widgets.php' ) ) );
			}
		}
	}
	$dir   = get_template_directory();
	$files = array_merge( (array) glob( $dir . '/*.php' ), (array) glob( $dir . '/template-parts/*.php' ), (array) glob( $dir . '/inc/*.php' ) );
	foreach ( $files as $f ) {
		if ( preg_match_all( '/do_shortcode\(\s*[\'"]\[([a-zA-Z][\w\-]*)/', (string) file_get_contents( $f ), $m ) ) {
			foreach ( array_unique( $m[1] ) as $tag ) {
				$where = array( sprintf( /* translators: %s: file name */ __( 'Theme template %s', 'akseki-vi' ), basename( $f ) ), '' );
				if ( shortcode_exists( strtolower( $tag ) ) ) {
					$used[ strtolower( $tag ) ][] = $where;
				} else {
					$raw[ strtolower( $tag ) ][] = $where;
				}
			}
		}
	}
	ksort( $used );
	ksort( $raw );
	return compact( 'used', 'raw' );
}

/** Links to where a shortcode is used: the first few, then "and N more". */
function apt_shortcode_where_html( $places ) {
	$out = array();
	foreach ( array_slice( $places, 0, 8 ) as $p ) {
		$out[] = $p[1] ? '<a href="' . esc_url( $p[1] ) . '">' . esc_html( $p[0] ) . '</a>' : esc_html( $p[0] );
	}
	$more = count( $places ) - 8;
	return implode( ', ', $out ) . ( $more > 0 ? ' ' . esc_html( sprintf( /* translators: %d: count */ __( 'and %d more', 'akseki-vi' ), $more ) ) : '' );
}

function apt_content_check_page() {
	// 1. Fixed IDs.
	$ids = array();
	foreach ( apt_content_check_ids() as $row ) {
		list( $type, $id, $what ) = $row;
		if ( 'category' === $type ) {
			$term  = get_category( $id );
			$ok    = $term && ! is_wp_error( $term );
			$name  = $ok ? $term->name : '';
			$link  = $ok ? get_edit_term_link( $id, 'category' ) : '';
		} else {
			$post = get_post( $id );
			$ok   = $post && $type === $post->post_type && 'trash' !== $post->post_status;
			$name = $ok ? get_the_title( $post ) : '';
			$link = $ok ? get_edit_post_link( $id ) : '';
		}
		$ids[] = compact( 'type', 'id', 'what', 'ok', 'name', 'link' );
	}
	$ids_bad = count( array_filter( $ids, function ( $r ) { return ! $r['ok']; } ) );

	// 2. Meeting categories.
	$meetings = array();
	foreach ( apt_content_check_meetings() as $slug => $level ) {
		$cat = get_category_by_slug( $slug );
		if ( ! $cat ) {
			continue;
		}
		$no_desc  = '' === trim( wp_strip_all_tags( $cat->description ) );
		$no_theme = function_exists( 'apt_category_theme_id' ) && ! apt_category_theme_id( $slug );
		if ( $no_desc || $no_theme ) {
			$meetings[] = compact( 'cat', 'level', 'no_desc', 'no_theme' );
		}
	}

	// 3. Theme pages.
	$themes = array();
	if ( function_exists( 'apt_theme_pages' ) ) {
		foreach ( apt_theme_pages() as $page ) {
			if ( 6262 === (int) $page->ID ) {
				continue; // Track 1.5 and Track 2 lists its categories itself
			}
			$pillar   = trim( (string) get_post_meta( $page->ID, 'tpc_pillar', true ) );
			$problems = array();
			if ( '' === $pillar ) {
				$problems[] = __( 'No pillar (tpc_pillar is missing).', 'akseki-vi' );
			} elseif ( ! get_category_by_slug( $pillar ) ) {
				/* translators: %s: slug */
				$problems[] = sprintf( __( 'Pillar "%s" is not a category slug.', 'akseki-vi' ), $pillar );
			}
			$topics = apt_theme_topics( $page->ID );
			if ( ! $topics ) {
				$problems[] = __( 'No meeting categories (pick this theme on the categories\' edit screens).', 'akseki-vi' );
			}
			$unknown = array_filter( $topics, function ( $s ) { return ! get_category_by_slug( $s ); } );
			if ( $unknown ) {
				/* translators: %s: slugs */
				$problems[] = sprintf( __( 'These slugs in tpc_topics match no category: %s.', 'akseki-vi' ), implode( ', ', $unknown ) );
			}
			if ( $problems ) {
				$themes[] = compact( 'page', 'problems' );
			}
		}
	}

	// 4. Recent statements without a photo.
	$no_photo = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'cat'            => 7,
		'date_query'     => array( array( 'after' => '12 months ago' ) ),
		'meta_query'     => array( array( 'key' => '_thumbnail_id', 'compare' => 'NOT EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	// 5. Mistyped special tags.
	$bad_tags = apt_content_check_bad_tags();

	// 6. Limited Access links outside the protected folder.
	$la_links = function_exists( 'apt_limited_public_links' ) ? count( apt_limited_public_links() ) : null;

	// 7. Shortcodes.
	$sc        = apt_shortcode_usage();
	$sc_unused = array(); // plugin => its shortcodes, none used in content
	foreach ( array_keys( $GLOBALS['shortcode_tags'] ) as $tag ) {
		list( $who, $folder ) = apt_shortcode_provider( $tag );
		if ( $folder ) {
			$sc_unused[ $who ][ $tag ] = isset( $sc['used'][ $tag ] );
		}
	}
	$sc_unused = array_filter( $sc_unused, function ( $tags ) { return ! in_array( true, $tags, true ); } );
	ksort( $sc_unused );

	$summary = array(
		array( '#apt-cc-ids', __( 'Site structure', 'akseki-vi' ), 0 === $ids_bad, $ids_bad ),
		array( '#apt-cc-meetings', __( 'Meeting categories', 'akseki-vi' ), ! $meetings, count( $meetings ) ),
		array( '#apt-cc-themes', __( 'Theme pages', 'akseki-vi' ), ! $themes, count( $themes ) ),
		array( '#apt-cc-photos', __( 'Statements without a photo (last 12 months)', 'akseki-vi' ), ! $no_photo, count( $no_photo ) ),
		array( '#apt-cc-tags', __( 'Mistyped special tags', 'akseki-vi' ), ! $bad_tags, count( $bad_tags ) ),
		array( '#apt-cc-limited', __( 'Limited Access links outside the protected folder', 'akseki-vi' ), ! $la_links, (int) $la_links ),
		array( '#apt-cc-shortcodes', __( 'Shortcodes showing as raw text', 'akseki-vi' ), ! $sc['raw'], count( $sc['raw'] ) ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Content check', 'akseki-vi' ); ?></h1>
		<p><?php esc_html_e( 'What needs attention on the site. Nothing here is changed automatically; each item links to where it is fixed.', 'akseki-vi' ); ?></p>

		<table class="widefat striped" style="max-width:760px">
			<tbody>
			<?php foreach ( $summary as $s ) : ?>
				<tr><td><a href="<?php echo esc_attr( $s[0] ); ?>"><?php echo esc_html( $s[1] ); ?></a></td>
				<td style="width:200px"><?php echo $s[2] ? apt_content_check_badge( true ) : apt_content_check_badge( false, '', sprintf( /* translators: %d: count */ _n( '%d item', '%d items', $s[3], 'akseki-vi' ), $s[3] ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2 id="apt-cc-ids"><?php esc_html_e( '1. Site structure', 'akseki-vi' ); ?></h2>
		<p><?php esc_html_e( 'The theme\'s code finds these by their ID. If one is deleted, or deleted and created again (which gives it a new ID), the part of the site that uses it breaks. Keep them; rename them freely.', 'akseki-vi' ); ?></p>
		<table class="widefat striped" style="max-width:1000px">
			<thead><tr><th><?php esc_html_e( 'Type', 'akseki-vi' ); ?></th><th>ID</th><th><?php esc_html_e( 'Used for', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Now', 'akseki-vi' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $ids as $r ) : ?>
				<tr>
					<td><?php echo esc_html( ucfirst( $r['type'] ) ); ?></td>
					<td><?php echo (int) $r['id']; ?></td>
					<td><?php echo esc_html( $r['what'] ); ?></td>
					<td><?php echo $r['ok'] ? ( $r['link'] ? '<a href="' . esc_url( $r['link'] ) . '">' . esc_html( $r['name'] ) . '</a>' : esc_html( $r['name'] ) ) : apt_content_check_badge( false, '', __( 'Missing', 'akseki-vi' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2 id="apt-cc-meetings"><?php esc_html_e( '2. Meeting categories', 'akseki-vi' ); ?></h2>
		<?php if ( ! $meetings ) : ?>
			<p><?php echo apt_content_check_badge( true, __( 'Every meeting category has a Theme and a Description.', 'akseki-vi' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'The Description is shown as "Topic" on news cards; the Theme links statements to their theme page. Set both on the category\'s edit screen.', 'akseki-vi' ); ?></p>
			<table class="widefat striped" style="max-width:1000px">
				<thead><tr><th><?php esc_html_e( 'Category', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Level', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Statements', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Missing', 'akseki-vi' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $meetings as $m ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_term_link( $m['cat']->term_id, 'category' ) ); ?>"><?php echo esc_html( $m['cat']->name ); ?></a></td>
						<td><?php echo esc_html( $m['level'] ); ?></td>
						<td><?php echo (int) $m['cat']->count; ?></td>
						<td><?php echo esc_html( implode( ', ', array_filter( array( $m['no_theme'] ? __( 'Theme', 'akseki-vi' ) : '', $m['no_desc'] ? __( 'Description', 'akseki-vi' ) : '' ) ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2 id="apt-cc-themes"><?php esc_html_e( '3. Theme pages', 'akseki-vi' ); ?></h2>
		<?php if ( ! $themes ) : ?>
			<p><?php echo apt_content_check_badge( true, __( 'Every theme page has a pillar, and every slug in its list matches a category.', 'akseki-vi' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:1000px">
				<thead><tr><th><?php esc_html_e( 'Theme page', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Problem', 'akseki-vi' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $themes as $t ) : ?>
					<tr><td><a href="<?php echo esc_url( get_edit_post_link( $t['page']->ID ) ); ?>"><?php echo esc_html( get_the_title( $t['page'] ) ); ?></a></td><td><?php echo esc_html( implode( ' ', $t['problems'] ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description"><?php esc_html_e( 'A page under Themes that is not a real theme (e.g. a page for one event) shows up here too: move it out from under Themes.', 'akseki-vi' ); ?></p>
		<?php endif; ?>

		<h2 id="apt-cc-photos"><?php esc_html_e( '4. Statements without a photo (last 12 months)', 'akseki-vi' ); ?></h2>
		<?php if ( ! $no_photo ) : ?>
			<p><?php echo apt_content_check_badge( true, __( 'Every statement of the last 12 months has a photo.', 'akseki-vi' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'These show the flags image instead of a photo. Set a Featured image if there is one.', 'akseki-vi' ); ?></p>
			<ul style="list-style:disc;padding-left:1.5em">
			<?php foreach ( $no_photo as $p ) : ?>
				<li><a href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a> <span class="description">(<?php echo esc_html( get_the_date( 'j F Y', $p ) ); ?>)</span></li>
			<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<h2 id="apt-cc-tags"><?php esc_html_e( '5. Mistyped special tags', 'akseki-vi' ); ?></h2>
		<?php if ( ! $bad_tags ) : ?>
			<p><?php echo apt_content_check_badge( true, __( 'No mistyped special tags.', 'akseki-vi' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'The theme ignores these, so the setting they were meant for is not applied. Open the statements and set it in their Statement settings box instead, then delete the tag.', 'akseki-vi' ); ?></p>
			<table class="widefat striped" style="max-width:1000px">
				<thead><tr><th><?php esc_html_e( 'Tag', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Statements', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Why', 'akseki-vi' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $bad_tags as $b ) : ?>
					<tr><td><code><?php echo esc_html( $b[0]->name ); ?></code></td>
					<td><a href="<?php echo esc_url( admin_url( 'edit.php?tag=' . rawurlencode( $b[0]->slug ) ) ); ?>"><?php echo (int) $b[0]->count; ?></a></td>
					<td><?php echo esc_html( $b[1] ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2 id="apt-cc-limited"><?php esc_html_e( '6. Limited Access', 'akseki-vi' ); ?></h2>
		<?php if ( null === $la_links ) : ?>
			<p><?php esc_html_e( 'Not available.', 'akseki-vi' ); ?></p>
		<?php elseif ( ! $la_links ) : ?>
			<p><?php echo apt_content_check_badge( true, __( 'Every file linked from a Limited Access page is in the protected folder.', 'akseki-vi' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php else : ?>
			<p><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d file linked from a Limited Access page is still public.', '%d files linked from Limited Access pages are still public.', $la_links, 'akseki-vi' ), $la_links ) ); ?>
			<a href="<?php echo esc_url( admin_url( 'upload.php?page=apt-limited-files' ) ); ?>"><?php esc_html_e( 'Move them into the protected folder', 'akseki-vi' ); ?></a></p>
		<?php endif; ?>

		<h2 id="apt-cc-shortcodes"><?php esc_html_e( '7. Shortcodes', 'akseki-vi' ); ?></h2>
		<p><?php esc_html_e( 'Shortcodes are the [name …] commands in pages, posts and widgets. A plugin whose shortcodes are used must stay active, or those pages show the raw command.', 'akseki-vi' ); ?></p>

		<h3><?php esc_html_e( 'Showing as raw text', 'akseki-vi' ); ?></h3>
		<?php if ( ! $sc['raw'] ) : ?>
			<p><?php echo apt_content_check_badge( true, __( 'None: every shortcode in the content is provided by an active plugin or the theme.', 'akseki-vi' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing active provides these, so visitors see the command itself. Remove it from the page, or reactivate the plugin it came from.', 'akseki-vi' ); ?></p>
			<table class="widefat striped" style="max-width:1000px">
				<thead><tr><th><?php esc_html_e( 'Shortcode', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Used on', 'akseki-vi' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $sc['raw'] as $tag => $places ) : ?>
					<tr><td><code>[<?php echo esc_html( $tag ); ?>]</code></td><td><?php echo apt_shortcode_where_html( $places ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h3><?php esc_html_e( 'In use', 'akseki-vi' ); ?></h3>
		<?php if ( ! $sc['used'] ) : ?>
			<p><?php esc_html_e( 'No shortcodes are used.', 'akseki-vi' ); ?></p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:1000px">
				<thead><tr><th><?php esc_html_e( 'Shortcode', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Provided by', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Places', 'akseki-vi' ); ?></th><th><?php esc_html_e( 'Used on', 'akseki-vi' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $sc['used'] as $tag => $places ) : ?>
					<tr><td><code>[<?php echo esc_html( $tag ); ?>]</code></td>
					<td><?php echo esc_html( apt_shortcode_provider( $tag )[0] ); ?></td>
					<td><?php echo count( $places ); ?></td>
					<td><?php echo apt_shortcode_where_html( $places ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Plugins whose shortcodes no content uses', 'akseki-vi' ); ?></h3>
		<?php if ( ! $sc_unused ) : ?>
			<p><?php esc_html_e( 'None.', 'akseki-vi' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'No page, post, widget or theme template uses these plugins\' shortcodes. If a plugin does nothing else for the site, it can be removed; check its other features first.', 'akseki-vi' ); ?></p>
			<ul style="list-style:disc;padding-left:1.5em">
			<?php foreach ( $sc_unused as $who => $tags ) : ?>
				<li><strong><?php echo esc_html( $who ); ?></strong>: <?php echo esc_html( implode( ', ', array_map( function ( $t ) { return '[' . $t . ']'; }, array_keys( $tags ) ) ) ); ?></li>
			<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}
