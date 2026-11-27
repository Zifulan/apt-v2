<?php
// PHP 7.4 support: WordPress 5.9+ provides these, older WordPress does not.
if ( ! function_exists( 'str_starts_with' ) ) {
	function str_starts_with( $haystack, $needle ) {
		return '' === (string) $needle || 0 === strncmp( (string) $haystack, (string) $needle, strlen( (string) $needle ) );
	}
}
if ( ! function_exists( 'str_contains' ) ) {
	function str_contains( $haystack, $needle ) {
		return '' === (string) $needle || false !== strpos( (string) $haystack, (string) $needle );
	}
}


//CHILD THEMING FROM TWENTY NINETEEN

function avi_enqueue() {
    //BOOTSTRAP
	wp_register_style('bootstrap', get_stylesheet_directory_uri() . '/vendor/bootstrap/css/bootstrap.min.css' );
    $dependencies = array('bootstrap');
    $theme = wp_get_theme();
	$ver = $theme->Version; //gets version written in your style.css
    wp_enqueue_style( 'bootstrapstarter-style', get_stylesheet_uri(), $dependencies, $ver ); 

	//jQUERY
    $dependencies = array('jquery');
	// In the footer: it only wires up collapse/modal/tab components after load,
	// so it must not block the first paint.
	wp_register_script( 'gdgt-base', get_stylesheet_directory_uri() . '/vendor/bootstrap/js/bootstrap.bundle.min.js', array( 'jquery' ), '4.6', true );
    wp_enqueue_script( 'gdgt-base' );

	//AKSEKI
	wp_register_script( 'akseki-js', get_stylesheet_directory_uri() . '/akseki.js', array( 'jquery', 'gdgt-base' ), filemtime( get_stylesheet_directory() . '/akseki.js' ), true );
    wp_enqueue_script( 'akseki-js' );

	//APT DESIGN SYSTEM (loaded last so it wins over bootstrap + style.css)
	// The Outfit font is self-hosted: @font-face at the top of apt-design.css.
	$apt_css = '/assets/css/apt-design.css';
	wp_enqueue_style( 'apt-design', get_stylesheet_directory_uri() . $apt_css, array( 'bootstrapstarter-style' ), filemtime( get_stylesheet_directory() . $apt_css ) );
	$apt_js = '/assets/js/apt-nav.js';
	wp_enqueue_script( 'apt-nav', get_stylesheet_directory_uri() . $apt_js, array(), filemtime( get_stylesheet_directory() . $apt_js ), true );
	$apt_carousel = '/assets/js/apt-carousel.js';
	if ( is_front_page() ) {
		wp_enqueue_script( 'apt-carousel', get_stylesheet_directory_uri() . $apt_carousel, array(), filemtime( get_stylesheet_directory() . $apt_carousel ), true );
	}
}

add_action( 'wp_enqueue_scripts', 'avi_enqueue' );

/**
 * Off-canvas mega menu: drop icon-only items (the old "home" icon) and the
 * repeated "parent as first child" entries the Bootstrap navbar needed.
 */
function apt_mega_menu_items( $items, $args ) {
	if ( empty( $args->apt_mega ) && empty( $args->apt_footer ) ) {
		return $items;
	}
	$by_id = array();
	foreach ( $items as $item ) {
		$by_id[ $item->ID ] = $item;
	}
	// A parent the old Bootstrap navbar only used to open its dropdown may
	// point nowhere (e.g. "News" -> /news, which doesn't exist). In the drawer
	// every item is a real link, so send such a parent to its first child.
	foreach ( $items as $item ) {
		if ( (int) $item->menu_item_parent || apt_menu_url_resolves( $item->url ) ) {
			continue;
		}
		foreach ( $items as $child ) {
			if ( (int) $child->menu_item_parent === (int) $item->ID && apt_menu_url_resolves( $child->url ) ) {
				$item->url = $child->url;
				break;
			}
		}
	}
	return array_values( array_filter( $items, function ( $item ) use ( $by_id ) {
		if ( '' === trim( wp_strip_all_tags( $item->title ) ) ) {
			return false;
		}
		$parent_id = (int) $item->menu_item_parent;
		if ( $parent_id && isset( $by_id[ $parent_id ] ) && untrailingslashit( $by_id[ $parent_id ]->url ) === untrailingslashit( $item->url ) ) {
			return false;
		}
		return true;
	} ) );
}
add_filter( 'wp_nav_menu_objects', 'apt_mega_menu_items', 10, 2 );

/**
 * Footer menu: no "Contact" column. The ASEAN Secretariat's address next to
 * it already gives the contact details. The header menu keeps "Contact".
 */
add_filter( 'wp_nav_menu_objects', function ( $items, $args ) {
	if ( empty( $args->apt_footer ) ) {
		return $items;
	}
	$hidden = array();
	foreach ( $items as $item ) {
		if ( ! (int) $item->menu_item_parent && 'contact' === strtolower( trim( wp_strip_all_tags( $item->title ) ) ) ) {
			$hidden[] = (int) $item->ID;
		}
	}
	return array_values( array_filter( $items, function ( $item ) use ( $hidden ) {
		return ! in_array( (int) $item->ID, $hidden, true ) && ! in_array( (int) $item->menu_item_parent, $hidden, true );
	} ) );
}, 20, 2 );

/**
 * The ASEAN Secretariat's contact details, used by the footer and the Contact
 * page (page-contact.php), so a change is made in one place.
 */
function apt_secretariat_contact() {
	// Edited under Appearance → Home page content → Contact (inc/home-content.php).
	return apt_home_contact();
}

/**
 * The full-screen site menu (header.php): numbered sections from the
 * "primary" menu (Appearance > Menus), each with a panel of its links.
 * Desktop: sections on the left, the open section's links on the right.
 * Phones: an accordion. Descriptions come from the menu item's Description
 * field, else from the linked category's description.
 */
function apt_site_menu_html() {
	$locations = get_nav_menu_locations();
	if ( empty( $locations['primary'] ) ) {
		return '';
	}
	$items = wp_get_nav_menu_items( $locations['primary'], array( 'update_post_term_cache' => false ) );
	if ( ! $items ) {
		return '';
	}
	if ( function_exists( '_wp_menu_item_classes_by_context' ) ) {
		_wp_menu_item_classes_by_context( $items ); // sets ->current, ->current_item_ancestor
	}
	$items = apt_mega_menu_items( $items, (object) array( 'apt_mega' => true ) );

	$kids = array();
	foreach ( $items as $item ) {
		$kids[ (int) $item->menu_item_parent ][] = $item;
	}
	if ( empty( $kids[0] ) ) {
		return '';
	}
	$is_here = function ( $item ) {
		return ! empty( $item->current ) || ! empty( $item->current_item_ancestor ) || ! empty( $item->current_item_parent );
	};
	$desc = function ( $item ) {
		$text = trim( wp_strip_all_tags( (string) $item->description ) );
		if ( '' === $text && 'taxonomy' === $item->type && 'category' === $item->object ) {
			$text = trim( wp_strip_all_tags( (string) category_description( (int) $item->object_id ) ) );
		}
		return '' === $text ? '' : wp_trim_words( $text, 12, '…' );
	};

	// The section open by default on desktop: the one holding this page, else the first with links.
	$open = 0;
	foreach ( $kids[0] as $top ) {
		if ( $is_here( $top ) && ! empty( $kids[ $top->ID ] ) ) {
			$open = $top->ID;
			break;
		}
	}
	if ( ! $open ) {
		foreach ( $kids[0] as $top ) {
			if ( ! empty( $kids[ $top->ID ] ) ) {
				$open = $top->ID;
				break;
			}
		}
	}

	$out = '<ol class="apt-menu-sections" style="--apt-menu-n:' . count( $kids[0] ) . '">';
	$n   = 0;
	foreach ( $kids[0] as $top ) {
		++$n;
		$num   = '<span class="apt-menu-num" aria-hidden="true">' . sprintf( '%02d', $n ) . '</span>';
		$title = esc_html( wp_strip_all_tags( $top->title ) );
		$here  = $is_here( $top ) ? ' is-here' : '';
		if ( empty( $kids[ $top->ID ] ) ) {
			$out .= '<li><a class="apt-menu-tab' . $here . '" href="' . esc_url( $top->url ) . '"' . ( ! empty( $top->current ) ? ' aria-current="page"' : '' ) . '>' . $num . '<span>' . $title . '</span></a></li>';
			continue;
		}
		$pid  = 'apt-menu-p' . (int) $top->ID;
		$out .= '<li data-open-default="' . ( $top->ID === $open ? '1' : '0' ) . '">';
		$out .= '<button type="button" class="apt-menu-tab' . $here . '" aria-expanded="false" aria-controls="' . $pid . '">' . $num . '<span>' . $title . '</span></button>';
		$out .= '<div class="apt-menu-panel" id="' . $pid . '" hidden>';
		// The section's own page, unless it is only a stand-in for its first link.
		$own  = untrailingslashit( $top->url ) !== untrailingslashit( $kids[ $top->ID ][0]->url );
		$out .= '<p class="apt-menu-kicker">' . ( $own ? '<a class="apt-arrow" href="' . esc_url( $top->url ) . '"' . ( ! empty( $top->current ) ? ' aria-current="page"' : '' ) . '>' . $title . '</a>' : $title ) . '</p>';
		$out .= '<ul class="apt-menu-links">';
		foreach ( $kids[ $top->ID ] as $child ) {
			$d    = $desc( $child );
			$out .= '<li><a href="' . esc_url( $child->url ) . '"' . ( ! empty( $child->current ) ? ' aria-current="page"' : '' ) . '><b>' . esc_html( wp_strip_all_tags( $child->title ) ) . '</b>' . ( $d ? '<span>' . esc_html( $d ) . '</span>' : '' ) . '</a>';
			if ( ! empty( $kids[ $child->ID ] ) ) {
				$out .= '<ul class="apt-menu-sublinks">';
				foreach ( $kids[ $child->ID ] as $g ) {
					$out .= '<li><a href="' . esc_url( $g->url ) . '"' . ( ! empty( $g->current ) ? ' aria-current="page"' : '' ) . '>' . esc_html( wp_strip_all_tags( $g->title ) ) . '</a></li>';
				}
				$out .= '</ul>';
			}
			$out .= '</li>';
		}
		$out .= '</ul></div></li>';
	}
	$out .= '</ol>';
	return $out;
}

/** The newest homepage feature (tag "front") for the menu's "Latest" line. */
function apt_site_menu_latest_html() {
	$q = new WP_Query( array(
		'tag'                 => 'front',
		'posts_per_page'      => 1,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	if ( ! $q->posts ) {
		return '';
	}
	$p = $q->posts[0];
	return '<a class="apt-menu-latest" href="' . esc_url( get_permalink( $p ) ) . '"><span class="apt-menu-latest-k">' . esc_html__( 'Latest', 'akseki-vi' ) . '</span><span class="apt-menu-latest-t">' . esc_html( wp_strip_all_tags( get_the_title( $p ) ) ) . '</span><span class="apt-menu-latest-d">' . esc_html( get_the_date( '', $p ) ) . '</span></a>';
}

/**
 * Whether a menu address leads somewhere: other websites are assumed fine;
 * on this site it must be the home page, a post or page, a category or tag,
 * or a post type archive. "#" and empty links don't lead anywhere.
 */
function apt_menu_url_resolves( $url ) {
	static $seen = array();
	$url = trim( (string) $url );
	if ( isset( $seen[ $url ] ) ) {
		return $seen[ $url ];
	}
	$ok   = true;
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( '' === $url || '#' === $url[0] ) {
		$ok = false;
	} elseif ( ! $host || 0 === strcasecmp( preg_replace( '/^www\./i', '', $host ), preg_replace( '/^www\./i', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) ) {
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		$ok   = '' === $path
			|| url_to_postid( $host ? $url : home_url( '/' . $path . '/' ) )
			|| get_page_by_path( $path )
			|| apt_menu_path_is_archive( $path );
	}
	$seen[ $url ] = (bool) $ok;
	return $seen[ $url ];
}

/** A category/tag/term or post type archive path, e.g. category/news or news. */
function apt_menu_path_is_archive( $path ) {
	$slug = sanitize_title( basename( $path ) );
	foreach ( get_taxonomies( array( 'public' => true ) ) as $taxonomy ) {
		if ( false !== strpos( $path, '/' ) && get_term_by( 'slug', $slug, $taxonomy ) ) {
			return true;
		}
	}
	foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
		$archive = $type->has_archive;
		if ( $archive ) {
			$archive_slug = is_string( $archive ) ? $archive : ( ! empty( $type->rewrite['slug'] ) ? $type->rewrite['slug'] : $type->name );
			if ( trim( $archive_slug, '/' ) === $path ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Turn an ordinal ("29th", "twenty-ninth", "Twenty Ninth") into an integer, or 0.
 */
function apt_ordinal_to_int( $ordinal ) {
	$ordinal = strtolower( trim( $ordinal ) );
	if ( preg_match( '/^(\d{1,3})(?:st|nd|rd|th)$/', $ordinal, $m ) ) {
		return (int) $m[1];
	}
	$units = array(
		'first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5, 'sixth' => 6,
		'seventh' => 7, 'eighth' => 8, 'ninth' => 9, 'tenth' => 10, 'eleventh' => 11, 'twelfth' => 12,
		'thirteenth' => 13, 'fourteenth' => 14, 'fifteenth' => 15, 'sixteenth' => 16,
		'seventeenth' => 17, 'eighteenth' => 18, 'nineteenth' => 19, 'twentieth' => 20,
		'thirtieth' => 30, 'fortieth' => 40, 'fiftieth' => 50,
	);
	$tens = array( 'twenty' => 20, 'thirty' => 30, 'forty' => 40, 'fifty' => 50 );
	if ( isset( $units[ $ordinal ] ) ) {
		return $units[ $ordinal ];
	}
	if ( preg_match( '/^(twenty|thirty|forty|fifty)[\s-]+(first|second|third|fourth|fifth|sixth|seventh|eighth|ninth)$/', $ordinal, $m ) ) {
		return $tens[ $m[1] ] + $units[ $m[2] ];
	}
	return 0;
}

/**
 * Number of ASEAN Plus Three Summits held so far: the highest summit ordinal
 * ("29th ASEAN Plus Three Summit", "Twenty-Ninth APT Summit", "27th ASEAN+3
 * Summit") found in published posts dated today or earlier. Scheduled agenda
 * entries are not published, so upcoming summits are not counted.
 * Cached for 12 hours and refreshed whenever a post is saved.
 */
/** Regex fragment for a meeting ordinal: "29th", "29 th", "Twenty-Ninth", "fiftieth". */
function apt_ordinal_pattern() {
	return '(\\d{1,3}\\s?(?:st|nd|rd|th)\\b|(?:(?:twenty|thirty|forty|fifty)[\\s-]+)?(?:first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth|eleventh|twelfth|thirteenth|fourteenth|fifteenth|sixteenth|seventeenth|eighteenth|nineteenth)|twentieth|thirtieth|fortieth|fiftieth)';
}

/** 1 -> "1st", 2 -> "2nd", 11 -> "11th", 23 -> "23rd". */
function apt_ordinal_suffix( $n ) {
	$n = (int) $n;
	if ( in_array( $n % 100, array( 11, 12, 13 ), true ) ) {
		return $n . 'th';
	}
	$ends = array( 1 => 'st', 2 => 'nd', 3 => 'rd' );
	return $n . ( isset( $ends[ $n % 10 ] ) ? $ends[ $n % 10 ] : 'th' );
}

/**
 * Label of a summit tab on the Summit Level Statements page: the year plus
 * the summit number from its short name ("2007 · 10th"), "Special" for a
 * special summit, or just the year when neither is in the name.
 */
function apt_summit_tab_label( $year, $shortname ) {
	$plain = html_entity_decode( wp_strip_all_tags( (string) $shortname ), ENT_QUOTES, 'UTF-8' );
	$plain = preg_replace( '/\s+/u', ' ', $plain );
	if ( preg_match( '/\b' . apt_ordinal_pattern() . '/i', $plain, $m ) ) {
		$n = apt_ordinal_to_int( preg_replace( '/(\d)\s+(?=st|nd|rd|th)/i', '$1', $m[1] ) );
		if ( $n ) {
			return $year . ' · ' . apt_ordinal_suffix( $n );
		}
	}
	if ( false !== stripos( $plain, 'special' ) ) {
		return $year . ' · Special';
	}
	return (string) $year;
}

function apt_get_summit_count() {
	$cached = get_transient( 'apt_summit_count' );
	if ( false !== $cached ) {
		return (int) $cached;
	}

	$ids = get_posts( array(
		'post_type'        => 'post',
		'post_status'      => 'publish',
		's'                => 'Summit',
		'fields'           => 'ids',
		'posts_per_page'   => -1,
		'no_found_rows'    => true,
		'suppress_filters' => true,
		'date_query'       => array( array( 'before' => 'tomorrow', 'inclusive' => false ) ),
	) );

	$ordinal_re = '(\d{1,3}(?:st|nd|rd|th)|(?:(?:twenty|thirty|forty|fifty)[\s-]+)?(?:first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth|eleventh|twelfth|thirteenth|fourteenth|fifteenth|sixteenth|seventeenth|eighteenth|nineteenth)|twentieth|thirtieth|fortieth|fiftieth)';
	// "29th ASEAN Plus Three Summit", "29th ASEAN Plus Three (APT) Summit", "29th APT Summit", "27th ASEAN+3 Summit"
	$summit_re  = '/\b' . $ordinal_re . '\s+(?:APT|ASEAN\s*(?:Plus\s*Three|\+\s*3))(?:\s*\((?:APT|ASEAN\s*\+\s*3)\))?\s+Summit\b/i';

	$max = 0;
	foreach ( $ids as $id ) {
		$texts = array( wp_strip_all_tags( get_the_title( $id ) ), akseki_get_sm( $id ) );
		foreach ( $texts as $text ) {
			if ( $text && preg_match_all( $summit_re, html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ), $m ) ) {
				foreach ( $m[1] as $ordinal ) {
					$max = max( $max, apt_ordinal_to_int( $ordinal ) );
				}
			}
		}
	}

	set_transient( 'apt_summit_count', $max, 12 * HOUR_IN_SECONDS );
	return $max;
}
/**
 * Ministerial body categories: the grandchildren of "Ministerial-level
 * Statements" (category 43), e.g. Economic Cooperations > AMAF+3.
 *
 * @return int[]
 */
function apt_ministerial_body_ids() {
	$ids = array();
	foreach ( get_categories( array( 'parent' => 43, 'hide_empty' => false ) ) as $pillar ) {
		foreach ( get_categories( array( 'parent' => $pillar->term_id, 'hide_empty' => false ) ) as $body ) {
			$ids[] = (int) $body->term_id;
		}
	}
	return $ids;
}

/**
 * Number of ministerial-level MEETINGS held (not posts): posts are grouped by
 * ministerial body and by the meeting number in the title or short name
 * ("26th AMAF Plus Three", "Twenty-Sixth Meeting"), so a joint statement and
 * a press release of the same meeting count once. Posts with no meeting
 * number are grouped by body and month. Only meetings dated today or earlier.
 * Cached for 12 hours and refreshed whenever a post is saved.
 */
function apt_get_ministerial_meeting_count() {
	$cached = get_transient( 'apt_ministerial_count' );
	if ( false !== $cached ) {
		return (int) $cached;
	}
	$bodies = apt_ministerial_body_ids();
	if ( ! $bodies ) {
		return 0;
	}
	$ids = get_posts( array(
		'post_type'        => 'post',
		'post_status'      => 'publish',
		'category__in'     => $bodies,
		'fields'           => 'ids',
		'posts_per_page'   => -1,
		'no_found_rows'    => true,
		'suppress_filters' => true,
		'date_query'       => array( array( 'before' => 'tomorrow', 'inclusive' => false ) ),
	) );

	$meetings = array();
	foreach ( $ids as $id ) {
		$text = html_entity_decode( wp_strip_all_tags( get_the_title( $id ) . ' ' . akseki_get_sm( $id ) ), ENT_QUOTES, 'UTF-8' );
		$key  = preg_match( '/\\b' . apt_ordinal_pattern() . '/i', $text, $m )
			? 'n' . apt_ordinal_to_int( preg_replace( '/(\\d)\\s+(?=st|nd|rd|th)/i', '$1', $m[1] ) )
			: 'm' . get_the_date( 'Y-m', $id );
		foreach ( array_intersect( wp_get_post_categories( $id ), $bodies ) as $body ) {
			$meetings[ $body . '|' . $key ] = true;
		}
	}
	$count = count( $meetings );
	set_transient( 'apt_ministerial_count', $count, 12 * HOUR_IN_SECONDS );
	return $count;
}

add_action( 'save_post', function () {
	delete_transient( 'apt_summit_count' );
	delete_transient( 'apt_ministerial_count' );
	delete_transient( 'apt_pillar_quotes' );
	delete_transient( 'apt_archive_years' );
} );

/*
 * ---------------------------------------------------------------------------
 * Homepage "Latest from Each Pillar": the newest statement under each pillar,
 * quoted word for word from the statement itself and cited.
 *
 * The quote is the post's Excerpt when an editor has filled it in AND it
 * appears word for word in the statement; otherwise the statement's opening
 * sentences (whole sentences only). Nothing is reworded.
 * ---------------------------------------------------------------------------
 */

/** Pillars, with headings from the ASEC "Overview of ASEAN Plus Three Cooperation". */
function apt_pillars() {
	return array(
		'ps' => array( 'title' => 'Political-Security Cooperation', 'slug' => 'political-cooperations' ),
		'ec' => array( 'title' => 'Economic and Finance Cooperation', 'slug' => 'economic-cooperations' ),
		'sc' => array( 'title' => 'Socio-Cultural Cooperation', 'slug' => 'socio-cultural-cooperations' ),
	);
}

/**
 * Category IDs for a pillar: every category whose slug starts with the pillar
 * slug (each level has its own, e.g. "political-cooperations-som-level"),
 * plus their children.
 */
function apt_pillar_category_ids( $slug ) {
	$ids = array();
	foreach ( get_categories( array( 'hide_empty' => false ) ) as $cat ) {
		if ( 0 === strpos( $cat->slug, $slug ) ) {
			$ids[] = (int) $cat->term_id;
			$children = get_term_children( $cat->term_id, 'category' );
			if ( is_array( $children ) ) {
				$ids = array_merge( $ids, array_map( 'intval', $children ) );
			}
		}
	}
	return array_values( array_unique( $ids ) );
}

/** Post HTML to plain text, one paragraph per line. */
function apt_plain_text( $html ) {
	$html = strip_shortcodes( (string) $html );
	$html = preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html );
	$html = preg_replace( '#</?(p|div|br|li|ul|ol|h[1-6]|tr|td|th|table|blockquote|figure|figcaption|section|hr)\b[^>]*>#i', "\n", $html );
	$text = html_entity_decode( wp_strip_all_tags( $html, false ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return str_replace( "\xC2\xA0", ' ', $text );
}

/** Text normalised for a word-for-word comparison (spacing and quote marks only). */
function apt_quote_key( $text ) {
	$text = str_replace(
		array( "\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{2013}", "\u{2014}" ),
		array( "'", "'", '"', '"', '-', '-' ),
		(string) $text
	);
	return trim( preg_replace( '/\s+/u', ' ', $text ) );
}

/** Split a paragraph into sentences, keeping abbreviations such as "H.E." and "Ma." intact. */
function apt_split_sentences( $para ) {
	$parts = preg_split( '/(?<=[.!?])(["\x{201D}\x{2019})]?)\s+(?=["\x{201C}(]?[A-Z0-9])/u', $para, -1, PREG_SPLIT_DELIM_CAPTURE );
	$abbr  = '/(?:^|[\s(])(?:H\.E|U\.S|Dr|Mr|Mrs|Ms|Prof|Amb|Hon|No|Nos|St|Jr|Sr|Ma|Gen|Lt|Col|e\.g|i\.e|vs|Inc|Co|Ltd|Jan|Feb|Mar|Apr|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec|[A-Z])\.$/';
	$sentences = array();
	$current   = $parts[0];
	for ( $i = 1; $i < count( $parts ); $i += 2 ) {
		$current .= $parts[ $i ];
		$next     = isset( $parts[ $i + 1 ] ) ? $parts[ $i + 1 ] : '';
		// Keep abbreviations and list markers ("1.", "(a)") with the sentence that follows.
		if ( preg_match( $abbr, $current ) || preg_match( '/^\(?[0-9a-z]{1,3}[.)]$/i', trim( $current ) ) ) {
			$current .= ' ' . $next;
			continue;
		}
		$sentences[] = $current;
		$current     = $next;
	}
	if ( '' !== trim( $current ) ) {
		$sentences[] = $current;
	}
	return array_map( 'trim', $sentences );
}

/**
 * The quote for a statement: array( 'text' => ..., 'source' => 'excerpt'|'opening' ),
 * or null when there is nothing suitable (e.g. a post that is only a PDF link).
 */
function apt_statement_quote( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$content = apt_plain_text( $post->post_content );

	if ( has_excerpt( $post ) ) {
		$excerpt = preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $post->post_excerpt ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		$excerpt = trim( preg_replace( '/^["\x{201C}\s]+|["\x{201D}\s]+$/u', '', $excerpt ) );
		if ( '' !== $excerpt && false !== strpos( apt_quote_key( $content ), apt_quote_key( $excerpt ) ) ) {
			return array( 'text' => $excerpt, 'source' => 'excerpt' );
		}
	}

	// Opening sentences: whole sentences from the first real paragraph, carrying
	// on into the next paragraph when the first is a single short sentence.
	$quote = '';
	foreach ( preg_split( '/\n+/', $content ) as $para ) {
		$para = trim( preg_replace( '/\s+/u', ' ', $para ) );
		if ( mb_strlen( $para ) < 80 || ! preg_match( '/[.!?]["\x{201D}\x{2019})]?$/u', $para ) ) {
			if ( '' !== $quote ) {
				break; // a heading or list after the opening: stop here
			}
			continue;
		}
		foreach ( apt_split_sentences( $para ) as $sentence ) {
			$candidate = '' === $quote ? $sentence : $quote . ' ' . $sentence;
			if ( mb_strlen( $candidate ) > 360 ) {
				break 2;
			}
			$quote = $candidate;
			if ( mb_strlen( $quote ) >= 140 ) {
				break 2;
			}
		}
	}
	if ( mb_strlen( $quote ) >= 60 ) {
		return array( 'text' => $quote, 'source' => 'opening' );
	}
	return null;
}

/** Newest quotable statement per pillar, cached until a post is saved. */
function apt_pillar_quotes() {
	$cached = get_transient( 'apt_pillar_quotes' );
	if ( false !== $cached ) {
		return $cached;
	}
	$out = array();
	foreach ( apt_pillars() as $key => $pillar ) {
		$ids = apt_pillar_category_ids( $pillar['slug'] );
		if ( ! $ids ) {
			continue;
		}
		$query = new WP_Query( array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'category__in'        => $ids,
			'posts_per_page'      => 10,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		) );
		foreach ( $query->posts as $p ) {
			if ( has_tag( 'hasnocontent', $p ) ) {
				continue;
			}
			$quote = apt_statement_quote( $p );
			if ( ! $quote ) {
				continue;
			}
			$more     = get_category_by_slug( $pillar['slug'] );
			$out[ $key ] = array(
				'pillar' => $pillar['title'],
				'quote'  => $quote['text'],
				'title'  => html_entity_decode( wp_strip_all_tags( get_the_title( $p ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
				'date'   => get_the_date( 'j F Y', $p ),
				'url'    => get_permalink( $p ),
				'more'   => $more ? get_category_link( $more->term_id ) : '',
			);
			break;
		}
	}
	set_transient( 'apt_pillar_quotes', $out, 12 * HOUR_IN_SECONDS );
	return $out;
}

/**
 * Editor hint: warn on a pillar statement whose Excerpt is not word for word
 * in the statement (the homepage then quotes the opening sentences instead).
 */
add_action( 'admin_notices', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'post' !== $screen->base || 'post' !== $screen->post_type ) {
		return;
	}
	$post = get_post();
	if ( ! $post || ! has_excerpt( $post ) ) {
		return;
	}
	$in_pillar = false;
	foreach ( apt_pillars() as $pillar ) {
		if ( in_category( apt_pillar_category_ids( $pillar['slug'] ), $post ) ) {
			$in_pillar = true;
			break;
		}
	}
	if ( ! $in_pillar ) {
		return;
	}
	$quote = apt_statement_quote( $post );
	if ( $quote && 'excerpt' === $quote['source'] ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'The Excerpt of this statement does not appear word for word in its text, so the homepage will quote the opening sentences instead. To choose the homepage quote, copy a sentence exactly from the statement into the Excerpt.', 'akseki-vi' ) . '</p></div>';
} );

/**
 * Map a pillar category name to a colour key used by the design system
 * (.pillar-ps / .pillar-ec / .pillar-sc / .pillar-other).
 */
function apt_pillar_key( $name ) {
	$name = strtolower( (string) $name );
	if ( false !== strpos( $name, 'political' ) || false !== strpos( $name, 'security' ) ) {
		return 'ps';
	}
	if ( false !== strpos( $name, 'econom' ) ) {
		return 'ec';
	}
	if ( false !== strpos( $name, 'socio' ) || false !== strpos( $name, 'cultur' ) ) {
		return 'sc';
	}
	return 'other';
}

register_nav_menus( array(
    'primary' => __( 'Primary Menu', 'akseki-vi' ),
) );

add_theme_support( 'post-thumbnails' );
// Modern markup for galleries and captions (figure/figcaption instead of the
// old dl/dt/dd layout, which screen readers announce as a definition list).
add_theme_support( 'html5', array( 'gallery', 'caption', 'style', 'script' ) );

/*MEC LOCATION CUSTOM TAXONOMY*/
/*get locations in add new posts*/
add_action( 'init', 'add_custom_taxonomies', 0 );
function add_custom_taxonomies() {
	register_taxonomy('mec_location', 'post', array(
    // Hierarchical taxonomy (like categories)
    'hierarchical' => true,
	'public'       => true,
	// No public page per location (/mec_location/pattaya/): they listed one
	// or two statements each and Google rejected them as thin. The place
	// still shows on statements and cards (akseki_get_mec_location()).
	'publicly_queryable' => false,
	'show_in_nav_menus'  => false,
	'show_tagcloud'      => false,
    'show_in_rest' => true,
    // This array of options controls the labels displayed in the WordPress Admin UI
    'labels' => array(
      'name' => _x( 'Locations', 'taxonomy general name' ),
      'singular_name' => _x( 'Location', 'taxonomy singular name' ),
      'search_items' =>  __( 'Search Locations' ),
      'all_items' => __( 'All Locations' ),
      'parent_item' => __( 'Parent Location' ),
      'parent_item_colon' => __( 'Parent Location:' ),
      'edit_item' => __( 'Edit Location' ),
      'update_item' => __( 'Update Location' ),
      'add_new_item' => __( 'Add New Location' ),
      'new_item_name' => __( 'New Location Name' ),
      'menu_name' => __( 'Locations' ),
    ),
    // No URL rule: old /mec_location/… addresses return 404 (see above).
    'rewrite' => false,
  ));
}

//load svg
/**
 * A flag as an <img> pointing at the SVG file. Earlier versions inlined the
 * SVG markup, which added hundreds of elements per page (slow layout on
 * phones) and exposed the files' <title>/<desc> notes to search engines as
 * page text; as an image the browser caches the file once and none of its
 * text is read as page content. With $label the flag is an image named by it;
 * without, it is decorative (the country is already written beside it).
 */
function load_flags_svg( $filename, $label = '' ) {
	$key = strtolower( (string) $filename );
	if ( '' === $key || ! file_exists( get_stylesheet_directory() . '/img/flags/' . $key . '.svg' ) ) {
		return '';
	}
	return '<img src="' . esc_url( get_stylesheet_directory_uri() . '/img/flags/' . $key . '.svg' ) . '"'
		. ' alt="' . esc_attr( $label ) . '" width="16" height="16" loading="lazy" decoding="async">';
}
/**
 * ASEAN emblem (updated 11-stalk version after Timor-Leste joined) for the
 * header and footer. Supplied artwork, so it ships as sized WebP rather than a
 * redrawn SVG. The name is kept because header.php and footer.php call it.
 */
function load_logo_svg() {
	$dir = get_stylesheet_directory_uri() . '/img/asean/';
	return '<img class="apt-emblem" src="' . esc_url( $dir . 'asean-emblem-128.webp' ) . '" srcset="'
		. esc_url( $dir . 'asean-emblem-128.webp' ) . ' 128w, ' . esc_url( $dir . 'asean-emblem-256.webp' ) . ' 256w"'
		. ' sizes="52px" width="128" height="128" alt="">';
}

//display country name and flag
function get_the_country($city){

	switch ($city) {
	case "Bandar Seri Begawan":
	case "Jerudong":
	case "Kuala Belait":
	case "Brunei Darussalam":
        $country="Brunei Darussalam";
		$flag="br";
        break;
    case "Phnom Penh":
	case "Siem Reap":
    case "Sihanoukville":
	case "Cambodia":
        $country="Cambodia";
		$flag="kh";
        break;
	case "Jakarta":
	case "Bogor":
	case "Medan":
	case "Yogyakarta":
	case "Surabaya":
	case "Bali":
	case "Bandung":
	case "Lombok":
	case "Mataram":
	case "Manado":
	case "Labuan Bajo":
	case "Jayapura":
	case "Semarang":
	case "Serpong":
	case "Indonesia":
        $country="Indonesia";
		$flag="id";
        break;
	case "Vientiane":
	case "Vang Vieng":
	case "Luang Prabang":
	case "Laos":
        $country="Laos";
		$flag="la";
        break;	
    case "Kuala Lumpur":
    case "Langkawi":
    case "Putrajaya":
    case "Kedah":
    case "Kuching":
    case "Penang":
    case "Selangor":
	case "Malaysia":
	case "Port Dickson":
	case "Johor":
	case "Melaka":
        $country="Malaysia";
		$flag="ml";
        break;
	case "Yangon":
	case "Naypyitaw":
	case "Nay Pyi Taw":
	case "Mandalay":
    case "Bagan":
	case "Myanmar":
        $country="Myanmar";
		$flag="mm";
        break;
	case "Manila":
	case "Metro Manila":
	case "Makati":
	case "Lapu-lapu City":
	case "Makati City":
	case "Laguna":
	case "Legazpi City":
	case "Los Baños":
	case "Paranaque City":
	case "Tagaytay":
	case "Davao":
	case "Bohol":
	case "Cebu":
	case "Pampanga":
	case "Pasay City":
	case "Philippines":
        $country="Philippines";
		$flag="ph";
        break;
	case "Singapore":
        $country="Singapore";
		$flag="sg";
        break;
    case "Bangkok":	
    case "Buriram":
    case "Chiang Mai":
    case "Khlong Luang":
    case "Nonthaburi":
    case "Phuket":
    case "Pattaya":
    case "Cha-am Hua Hin":	
	case "Thailand":
        $country="Thailand";
		$flag="th";
        break;
    case "Hanoi":
    case "Ha Noi":
    case "Ha Long City":
    case "Ho Chi Minh":
    case "Da Lat":
    case "Vinh Phuc":
    case "Da Nan":
    case "Da Nang":
    case "Hue City":
    case "Nha Trang":
	case "Vietnam":
        $country="Vietnam";
		$flag="vn";
        break;
	case "Shanghai":
	case "Changsa":
	case "Chengdu":
	case "Yunnan":
	case "Guiyang":
	case "Hainan":
	case "Beijing":
    case "Nanning":
    case "Xiamen":
    case "Xian":
    case "Sichuan":
    case "Shenzen":
    case "Hong Kong":
	case "China":
        $country="China";
		$flag="ch";
        break;
	case "Tokyo":
	case "Yokohama":
	case "Kyoto":
    case "Kanazawa":
    case "Fukuoka":
    case "Sendai":
    case "Nara":
    case "Niigata":
    case "Odawara":
    case "Osaka":
	case "Japan":
        $country="Japan";
		$flag="jp";
        break;
	case "Seoul":
	case "Busan":
	case "Changwon":
	case "Gyeongnam":
	case "Seongnam":
	case "Jeju":
	case "Daejeon":
	case "ROK":
	case "Incheon":
        $country="Korea";
		$flag="kr";
        break;
	case "Honolulu":
        $country="USA";
		$flag="us";
        break;
	case "Istanbul":
        $country="Turkey";
		$flag="tr";
        break;
	case "Madrid":
        $country="Spain";
		$flag="es";
        break;
	case "Tashkent":
	case "Samarkand":
        $country="Uzbekistan";
		$flag="uz";
        break;
	case "Delhi":
	case "Hyderabad":
        $country="India";
		$flag="in";
        break;	
	case "Astana":
        $country="Kazakhstan";
		$flag="kz";
        break;
    case "Tbilisi":
        $country="Georgia";
		$flag="ge";
        break;
	case "Baku":
        $country="Azerbaijan";
		$flag="az";
        break;	
	case "Frankfurt":
        $country="Germany";
		$flag="de";
        break;
	case "Nacka":
        $country="Sweden";
		$flag="sw";
        break;
    case "Milan":
        $country="Italy";
		$flag="it";
        break;
	case "Nadi":
		$country="Fiji";
		$flag="fj";
		break;
	case "Colombo":
		$country="Sri Lanka";
		$flag="sl";
		break;
	case "Online Conference":
        $country="Online Conference";
		$flag="asean";
        break;
    default:
        $country="";
		$flag="";
	}
	$return = ['country'=>$country, 'flag'=>$flag];
	return ($return);
}
function akseki_get_mec_location($postID){

	if(!empty(get_the_terms( $postID, 'mec_location' ))){
	    $mec_location_obj = get_the_terms( $postID, 'mec_location' )[0];
	}else{
	    $mec_location_obj = get_the_terms( $postID, 'mec_location' );
	}
	if(!empty($mec_location_obj)){
		$mec_location = get_the_terms( $postID, 'mec_location' )[0]->name;
	}else{
		$mec_location = get_post_meta($postID, 'mec_location', true);	
	}
	return $mec_location;
}
function akseki_get_the_meta($postID){

	$mec_location = akseki_get_mec_location($postID);
	$return = '<span class="meta-location">';
	if($mec_location){
		if(is_numeric($mec_location)){
			$mec_city = get_term($mec_location)->name;
		}else{
			$mec_city = $mec_location;
		}
		//if the city is the country no need to display both		
		$mec_country = get_the_country($mec_city);		
		if($mec_country['country'] !== $mec_city){
			$return .= '<span class="meta-city">'.$mec_city.'</span>, ';
		}		
		$return .= '<span class="meta-country">'.$mec_country['country'].'</span>&ensp;';

		$return .= '<span class="roundflag">'.load_flags_svg($mec_country['flag']).'</span>';
		$return .= '</span>';
	}													
	$return .= '<span class="meta-comma"> </span>';
	//$return .= '<span class="meta-date">'.get_the_date().'</span>';
	//if article is future, look for SC. if not, just get the date
	$articledate = get_the_date('Y-m-d',$postID);
	$nowdate =  date('Y-m-d');
	$futurearticle = true;
	if($articledate < $nowdate){
		$futurearticle = false;
	}
	if($futurearticle == true){
		$return .= '<span class="meta-date">'.akseki_get_sc_noclass($postID).'</span>';
	}else{

		$return .= '<span class="meta-date">'.akseki_get_sc($postID).'</span>';
	}
	return $return;
}
function akseki_get_the_flag($postID){

	$mec_location = akseki_get_mec_location($postID);
	if($mec_location){
		if(is_numeric($mec_location)){
			$mec_city = get_term($mec_location)->name;
		}else{
			$mec_city = $mec_location;
		}
		$mec_country = get_the_country($mec_city);
		$return = '<span class="roundflag">'.load_flags_svg($mec_country['flag'], $mec_country['country']).'</span>';
		return $return;
	}
}

function akseki_get_shortname($longname, $post_ID, $limit=3){
	$longtitle = strip_tags($longname);
	$display_output = "";
	$shorttitle3 = '';
	$fixedtitle = '';
	$noshorttitle = false;
	preg_match('/\d\d*(:?st|nd|rd|th)/', $longtitle, $shorttitle);
	if(!empty($shorttitle)){
		$shorttitle3 = trim(strtolower($shorttitle[0]));		
	}else{
		preg_match('/(:?first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth|eleventh|twelfth|thirteenth|fourteenth|fifteenth|sixteenth|seventeenth|eighteenth|nineteenth|twentieth)/i', $longtitle, $shorttitle2);
		if(!empty($shorttitle2)){
			$shorttitle3 = trim(strtolower($shorttitle2[0]));
		}else{
			$shorttitle3 = $longtitle;
			$noshorttitle = true;
		}
	}
	if($noshorttitle == false) :
	switch ($shorttitle3) {
		case "1st":
		case "first":
			$fixedtitle="1st";			
			break;
		case "2nd":
		case "second":
			$fixedtitle="2nd";
			break;
		case "3rd":
		case "third":
			$fixedtitle="3rd";
			break;
		case "4th":
		case "fourth":
			$fixedtitle="4th";
			break;	
		case "5th":
		case "fifth":
			$fixedtitle="5th";
			break;
		case "6th":
		case "sixth":
			$fixedtitle="6th";
			break;
		case "7th":
		case "seventh":
			$fixedtitle="7th";
			break;
		case "8th":
		case "eighth":
			$fixedtitle="8th";
			break;
		case "9th":	
		case "ninth":	
			$fixedtitle="9th";
			break;
		case "10th":
		case "tenth":
			$fixedtitle="10th";
			break;
		case "11th":
		case "eleventh":
			$fixedtitle="11th";
			break;
		case "12th":
		case "twelfth":
			$fixedtitle="12th";
			break;
		case "13th":
		case "thirteenth":
			$fixedtitle="13th";
			break;
		case "14th":
		case "fourteenth":
			$fixedtitle="14th";
			break;
		case "15th":
		case "fifteenth":
			$fixedtitle="15th";
			break;
		case "16th":
		case "sixteenth":
			$fixedtitle="16th";
			break;
		case "17th":
		case "seventeenth":
			$fixedtitle="17th";
			break;
		case "18th":
		case "eighteenth":
			$fixedtitle="18th";
			break;
		case "19th":
		case "nineteenth":
			$fixedtitle="19th";
			break;
		case "20th":
		case "twentieth":
			$fixedtitle="20th";
			break;
		default:
			$fixedtitle=$shorttitle3;
		}
	endif;
	if($noshorttitle==false){
		$display_output .= '<span class="numberer">';
		$display_output .= preg_replace('/(st|nd|rd|th)/', '<sup>$1</sup>', $fixedtitle);
		$display_output .= "</span>";

	    
		$min_list = akseki_get_ministrial_categories();
		$dg_list = akseki_get_dg_categories();
		$som_list = akseki_get_som_categories();
		$wg_list = akseki_get_wg_categories();
		$otl_list = akseki_get_othlev_categories();
		$ott_list = akseki_get_ott_categories();
		$sumcpr = ['summit-level-statements','cpr-level'];
		$merge_list = array_merge($min_list,$dg_list,$som_list,$wg_list,$otl_list,$ott_list,$sumcpr);
		$curcats = get_the_category($post_ID);
		$curslugs = wp_list_pluck($curcats,'slug');
		$current_categories = array_intersect($merge_list,$curslugs);

		$sm = akseki_get_sm($post_ID);
		foreach($current_categories as $cat){
			if($cat === 'summit-level-statements'){
				$display_output .= ' APT Summit';
			}elseif($cat === 'cpr-level'){
				$display_output .= ' CPR+3';
			}elseif($cat === 'asean3-student-camp' && !empty($sm)){
				// fix ordinal				
				$int_var = (int)filter_var($sm, FILTER_SANITIZE_NUMBER_INT); 
				if(!empty($int_var)){
					$display_output .= ' '.preg_replace('/(st|nd|rd|th)/', '<sup>$1</sup>', $sm);
				}else{
					$display_output .= ' '.$sm;
				}
			}else{
				$catobj = get_category_by_slug($cat);
				$display_output .= ' '.$catobj->name;
			}
		}
	}else{
		//special case for APT SOM
		$current_categories = get_the_category($post_ID);
		//insert preparatory text if exist
		preg_match('/(:?preparatory|preparation)/i', $longtitle, $prep);
		if(!empty($prep)){
			$prep = 'Prep ';		
		}else{
			$prep = '';
		}
		if(in_array("apt-som",wp_list_pluck($current_categories,'slug'))){
			$text = "APT SOM ".$prep."<span class='aptsom_month'>".get_the_date( 'F' )."</span>";
		}else{
			$text = $longname;
			if ($limit > 0 && str_word_count($text, 0) > $limit && has_tag("noshorttitle",$post_ID)==false) {
				//if has tag SM_
				$sm = akseki_get_sm($post_ID);
				if(!empty($sm)){
					$text = $sm;
				}else{
					$words = str_word_count($text, 2);
					$pos   = array_keys($words);
					$text  = substr($text, 0, $pos[$limit]) . '&hellip;';
				}
			}
		}
		$display_output .= $text;
	}

	return $display_output;
}

//list of ministrial level categories slug

function akseki_get_ministrial_categories(){
	$somcatid = 43;//cat id of ministrial categories
	$appear = [];
	$children = get_categories(array('parent'=>$somcatid));
	foreach($children as $child){
		$grandchildren = get_categories(array('parent'=>$child->term_id));
		foreach($grandchildren as $grandchild){
			$appear[] = $grandchild->slug;
		}		
	}	
	return $appear;
}
function akseki_get_dg_categories(){
	$somcatid = 377;//cat id of dg categories
	$appear = [];
	$children = get_categories(array('parent'=>$somcatid));
	foreach($children as $child){
		$grandchildren = get_categories(array('parent'=>$child->term_id));
		foreach($grandchildren as $grandchild){
			$appear[] = $grandchild->slug;
		}		
	}	
	return $appear;
}
function akseki_get_som_categories(){
	$somcatid = 280;//cat id of som categories
	$appear = [];
	$children = get_categories(array('parent'=>$somcatid));
	foreach($children as $child){
		$grandchildren = get_categories(array('parent'=>$child->term_id));
		foreach($grandchildren as $grandchild){
			$appear[] = $grandchild->slug;
		}		
	}	
	return $appear;
}
function akseki_get_wg_categories(){
	$somcatid = 373;//cat id of wg ctegories
	$appear = [];
	$children = get_categories(array('parent'=>$somcatid));
	foreach($children as $child){
		$grandchildren = get_categories(array('parent'=>$child->term_id));
		foreach($grandchildren as $grandchild){
			$appear[] = $grandchild->slug;
		}		
	}	
	return $appear;
}
function akseki_get_othlev_categories(){
	$otlcatid = 295;//cat id of other levels categories
	$appear = [];
	$children = get_categories(array('parent'=>$otlcatid));
	foreach($children as $child){
		$grandchildren = get_categories(array('parent'=>$child->term_id));
		foreach($grandchildren as $grandchild){
			$appear[] = $grandchild->slug;
		}		
	}	
	return $appear;
}
function akseki_get_ott_categories(){
	$otlcatid = 399;//cat id of other trecks categories
	$appear = [];
	$children = get_categories(array('parent'=>$otlcatid));
	foreach($children as $child){
		$grandchildren = get_categories(array('parent'=>$child->term_id));
		foreach($grandchildren as $grandchild){
			$appear[] = $grandchild->slug;
		}		
	}	
	return $appear;
}
function akseki_post_is_in_a_subcategory( $categories, $_post = null ) {
    foreach ( (array) $categories as $category ) {
        // get_term_children() only accepts integer ID
        $subcats = get_term_children( (int) $category, 'category' );
        if ( $subcats && in_category( $subcats, $_post ) )
            return true;
    }
    return false;
}

//get category descendents of statements category
function akseki_get_descat($curcatidarr){
	$descats = get_categories(array('child_of' => 7));
	$descatspluck =  wp_list_pluck($descats, 'term_id');
	$current_cat = array_intersect($curcatidarr, $descatspluck);
	if(!empty($current_cat)){
		$return = $current_cat[0];
	}else{
		$return = '';
	}
	//return the id of the found category
	return $return;
}

//get theme of a post in a category, return id of page of the theme
function akseki_get_theme_from_id($id,$idtype){
	if($idtype=="post"){
		$ftn_categories = get_the_category($id);

		$sumcat = array("summit-level-statements");
		$mincat = akseki_get_ministrial_categories();
		$dgcat = akseki_get_dg_categories();
		$cprcat = array("cpr-level");
		$somcat = akseki_get_som_categories();
		$wgcat = akseki_get_wg_categories();
		$otlcat = akseki_get_othlev_categories();
		$ottcat = akseki_get_ott_categories();
		$descats = array_merge($sumcat,$mincat,$dgcat,$cprcat,$somcat,$wgcat,$otlcat,$ottcat);
		foreach($ftn_categories as $cat){
			$aa[] = $cat->slug;
		}		
		$current_slug = array_intersect($aa, $descats);
		$current_slug = reset($current_slug);
		if(empty($current_slug)){
			$return = '';
		}		
		$target_slug = $current_slug;
	}elseif($idtype=="category"){
		$targetcat = get_category($id);
		$target_slug = $targetcat->slug;
	}else{
		$target_slug = "";
	}
	$themes = get_children(5733);
	$topics_arr = [];	
	foreach($themes as $theme){		
		$tpc_topics = get_post_custom_values( "tpc_topics", $theme->ID);
		$extos = array_map( 'trim', explode( ",", (string) ( $tpc_topics[0] ?? '' ) ) ); // "a, b" works too
		foreach($extos as $exto){

			$topics_arr[$exto] = $theme->ID;
		}		
	}	
	if(array_key_exists($target_slug, $topics_arr)) {
		$return = $topics_arr[$target_slug];
	} else {
		$return = '';
	}	
	return $return;
}

//get theme/topic of a post in a category, return id of the category
function akseki_get_level_from_id($id,$idtype,$pillarorlevel="level"){
	//get the category objects
	if($idtype=="post"){
		$descats = get_categories(array('child_of' => 7));
		$cats = wp_list_pluck($descats, 'slug');

		$ftn_categories = get_the_category($id);

		foreach($ftn_categories as $cat){
			$aa[] = $cat->slug;
		}		
		$current_slug = array_intersect($aa, $cats);
		if(empty($current_slug)){
			$return = '';
		}
		$gpa = get_category_by_slug($current_slug[0]);

		
	}elseif($idtype=="category"){
		$gpa = get_category($id);
	}else{
		$return = '';
	}

	$gpa_anc = get_ancestors($gpa->term_id,'category');
	//for non multivelvel, add its original category
	if($gpa->term_id == 41){//41 is summit level statements
		$gpa_anc[] = 41;
	}

	if($pillarorlevel=="pillar"){
		$parent = get_category($gpa->parent);
		$pn = $parent->slug;
		if(str_contains($pn,"political")){
			$levid=63;
		}elseif(str_contains($pn,"economic")){
			$levid=47;
		}elseif(str_contains($pn,"socio")){
			$levid=54;
		}
	}else{
		if(in_array(41,$gpa_anc)){//summit
			$levid=41;
		}elseif(in_array(43,$gpa_anc)){//ministerial
			$levid=43;
		}elseif(in_array(280,$gpa_anc)){//som
			$levid=280;
		}elseif(in_array(295,$gpa_anc)){//other
			$levid=295;
		}

		if(in_array(41,$gpa_anc)){//summit
			$levid=41;
		}elseif(in_array(43,$gpa_anc)){//ministerial
			$levid=43;
		}elseif(in_array(377,$gpa_anc)){//dg
			$levid=377;
		}elseif(in_array(409,$gpa_anc)){//cpr
			$levid=409;
		}elseif(in_array(280,$gpa_anc)){//som
			$levid=280;
		}elseif(in_array(373,$gpa_anc)){//wg
			$levid=373;
		}elseif(in_array(295,$gpa_anc)){//other
			$levid=295;
		}elseif(in_array(399,$gpa_anc)){//other tracks
			$levid=399;
		}

	}
	$return = '';
	if(isset($levid)){
		$return=$levid;
	}
	return $return;
}

//shortcode to display list of categories
function akseki_aoc($atts){
	$cats = get_categories( array(        
					'parent' => $atts['parent'],
				) );	
	$return = '';
	foreach($cats as $index=>$cat){
		switch($cat->term_id){
			//find 	ASEAN Plus Three Ministerial Meeting on
			case 42:
				$area = 'Political Security';
				break;
			case 46:
			    $area = 'Transnational Crime';
				break;
		    case 48:
		        $area = 'Economic Ministers';
				break;
			case 49:
			    $area = 'Finance Ministers and Central Bank Governors';
				break;
			case 50:
			    $area = 'Tourism';
				break;
			case 51:			
			case 52:
			case 53:
			case 55:			
			case 56:			
			case 57:
			case 59:			
				$exploded = explode(' on ', $cat->description);
				$area = $exploded[1];
				break;
			case 58:
			    $area = 'Labour';
				break;
		    case 60:
		        $area = 'Information';
				break;
			case 61:
			    $area = 'Education';
				break;
			case 62:
				$exploded = explode(' Ministers', $cat->description);
				$exploded2 = explode(' ', $exploded[0]);
				$area = end($exploded2);
				break;
			case 259:
			    $area = 'Immigration';
				break;
			default:
				$area = $cat->term_id;
		}

		$return .= '<tr>';
		//the area column
		$return .= '<td class="w-25">';
		$return .= $area;		
		$return .= '</td>';
		//the summit collapsible, only call on first iteration		

		
		//the ministerial collapsible
		$return .= '<td class="" data-parent="#accordionLevel">';
		$return .= '<a href="'.get_category_link($cat->term_id).'">'.$cat->description .' ('.$cat->name.')</a>';
		$return .= '</td>';
		$return .= '</tr>';

	}
	return '<table class="w-100">'.$return.'</table>';	
}
add_shortcode('akseki_aoc','akseki_aoc');

//handle special meetings
function akseki_get_sm($id){
	$thetags = get_the_tags($id);
	$SM = '';
	if(!empty($thetags)){
		foreach($thetags as $thetag){
			if(preg_match('/^SM_.+/',$thetag->name)){
				$SM = $thetag->name;
			}
		}
	}
	return substr($SM,3);
}

//handle dates in agenda (future posts)
function akseki_get_sc_raw($id){
	$thetags = get_the_tags($id);
	$SC = '';
	if(!empty($thetags)){
		foreach($thetags as $thetag){
			if(preg_match('/^SC_.+/',$thetag->name)){
				$SC = $thetag->name;
			}
		}
	}
	return $SC;
}

function akseki_get_sc($id){
	$SC = akseki_get_sc_raw($id);
	$thedate = '';
	if($SC=="SC_year"){
		$thedate .= '<span class="">'.get_the_date('Y', $id).'</span>';	
	}elseif($SC=="SC_month"){
		$thedate .= '<span class="">'.get_the_date('F', $id).'</span>&nbsp;';
		$thedate .= '<span class="">'.get_the_date('Y', $id).'</span>';
	}elseif($SC=="SC_nodate"){
		$thedate .= '<span class="align-self-center">tbd</span>';
	}else{
		$thedate .= '<span class="">'.get_the_date('j', $id).'</span>&nbsp;';
		$thedate .= '<span class="">'.get_the_date('F', $id).'</span>&nbsp;';
		$thedate .= '<span class="">'.get_the_date('Y', $id).'</span>';
	}
	return $thedate;
}
function akseki_get_sc_noclass($id){
	$SC = akseki_get_sc_raw($id);
	$thedate = '';
	if($SC=="SC_year"){
		$thedate .= '<span>'.get_the_date('Y', $id).'</span>';	
	}elseif($SC=="SC_month"){
		$thedate .= '<span>'.get_the_date('F', $id).'</span>&nbsp;';
		$thedate .= '<span>'.get_the_date('Y', $id).'</span>';
	}elseif($SC=="SC_nodate"){
		$thedate .= '<span>tbd</span>';
	}else{
		$thedate .= '<span>'.get_the_date('j', $id).'</span>&nbsp;';
		$thedate .= '<span>'.get_the_date('F', $id).'</span>&nbsp;';
		$thedate .= '<span>'.get_the_date('Y', $id).'</span>';
	}
	return $thedate;
}
//get future post in the category
function akseki_get_future_post_in_category($catid){
	$query = new WP_Query(array(
		'cat'=> $catid,
		'post_status' => 'future',
		'orderby'           => 'date',
		'order'             => 'ASC'
		)
	);
	$return = [];
	if( $query->have_posts() ) :
		while ( $query->have_posts() ):
			$query->the_post();
			array_push($return, get_the_ID());
		endwhile;
	endif;
	return $return;
}

// Remove pagination on listing per category index page
// may need to enable / disable for certain category later
function no_nopaging($query) {
    if (is_category() && $query->is_main_query()) {
        $query->set('nopaging', 1);
    }
}
add_action('parse_query', 'no_nopaging');

/*
 * Shortcodes used in page content (Overview, Resources …). Same names and
 * attributes as before, so existing content keeps working; the markup is the
 * new design.
 */

/** Photo of a category's latest statement (or the default image). */
function apt_latest_photo_html( $cat_id, $size = 'medium_large' ) {
	$latest = get_posts( array( 'cat' => $cat_id, 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids' ) );
	if ( ! $latest ) {
		return '';
	}
	return apt_post_image_html( $latest[0], $size, '(max-width: 767px) 92vw, 360px' );
}

/**
 * [akseki_cat_children parent="ID" style="card-group|buttons|withlatestpost-economic|withlatestpost-socio"]
 * The child categories (meetings, mechanisms) of a category: a link list
 * ("card-group"), or cards with name and full name, with the latest photo
 * for the "withlatestpost" styles.
 */
function akseki_cat_children( $atts ) {
	$atts = shortcode_atts( array( 'parent' => 0, 'style' => 'card-group' ), $atts );
	$cats = get_categories( array( 'parent' => (int) $atts['parent'] ) );
	if ( ! $cats ) {
		return '';
	}
	if ( 'card-group' === $atts['style'] ) {
		$out = '<nav class="apt-sidebar-list" aria-label="' . esc_attr( get_cat_name( $atts['parent'] ) ) . '"><p class="apt-sidebar-title">' . esc_html( get_cat_name( $atts['parent'] ) ) . '</p><ul>';
		foreach ( $cats as $cat ) {
			$out .= '<li><a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a></li>';
		}
		return $out . '</ul></nav>';
	}
	$with_photo = 0 === strpos( $atts['style'], 'withlatestpost' );
	$out        = '<ul class="apt-mechanisms' . ( $with_photo ? ' has-photos' : '' ) . '">';
	foreach ( $cats as $cat ) {
		$out .= '<li><a class="apt-mechanism" href="' . esc_url( get_category_link( $cat->term_id ) ) . '">'
			. ( $with_photo ? apt_latest_photo_html( $cat->term_id ) : '' )
			. '<span class="apt-mechanism-name">' . esc_html( $cat->name ) . '</span>'
			. ( trim( (string) $cat->description ) ? '<span class="apt-mechanism-desc">' . esc_html( wp_strip_all_tags( $cat->description ) ) . '</span>' : '' )
			. '</a></li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'akseki_cat_children', 'akseki_cat_children' );

/**
 * [akseki_get_recent_posts cat="ID" limit="3" tag=""]
 * The latest statements of a category as cards: photo, short name, place and date.
 */
function akseki_get_recent_posts( $atts ) {
	$atts  = shortcode_atts( array( 'cat' => 0, 'limit' => 0, 'tag' => '' ), $atts );
	$posts = get_posts( array( 'cat' => $atts['cat'], 'numberposts' => $atts['limit'], 'tag' => $atts['tag'] ) );
	if ( ! $posts ) {
		return '';
	}
	$out = '<ul class="apt-recent-cards">';
	foreach ( $posts as $p ) {
		$out  .= '<li><a class="apt-recent-card" href="' . esc_url( get_permalink( $p->ID ) ) . '">'
			. apt_post_image_html( $p->ID, 'medium', '(max-width: 600px) 45vw, 180px' )
			. '<span class="apt-recent-name">' . akseki_get_shortname( $p->post_title, $p->ID, 3 ) . '</span>'
			. '<span class="akseki-meta">' . akseki_get_the_meta( $p->ID ) . '</span></a></li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'akseki_get_recent_posts', 'akseki_get_recent_posts' );

/*
 * [akseki_limited_access]: the old password form posted to a handler that
 * never logged anyone in (it only printed "$alapass"). The form and handler
 * are removed; the shortcode now renders nothing so a page that still
 * contains it doesn't show the raw shortcode. Limited Access uses the page
 * password and the WordPress login form instead.
 */
add_shortcode( 'akseki_limited_access', '__return_empty_string' );

/*
 * Limited Access login pages: page 4452 (the login page) and any page with the
 * [wp_login_form] or [logincheck] shortcode.
 * - Never cached, by WP Super Cache (DONOTCACHEPAGE) or by the hosting
 *   firewall/CDN (no-cache headers): a cached copy can show a stale or broken
 *   form, and login pages should not be stored anyway.
 * - Already logged in on the login page: straight to the documents. This runs
 *   before the page is sent (it was inside the [logincheck] shortcode, too late
 *   for a redirect to work).
 */
function apt_is_login_form_page() {
	if ( ! is_singular() ) {
		return false;
	}
	$post = get_queried_object();
	return $post instanceof WP_Post && ( 4452 === (int) $post->ID || has_shortcode( $post->post_content, 'wp_login_form' ) || has_shortcode( $post->post_content, 'logincheck' ) );
}
add_action( 'template_redirect', function () {
	if ( ! apt_is_login_form_page() ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	if ( is_user_logged_in() && is_page( 4452 ) ) {
		wp_safe_redirect( home_url( '/limited-access-documents-2/' ) );
		exit;
	}
}, 1 );

// [logincheck]: kept so pages that still contain it don't show the raw
// shortcode. Its redirect is above; its "Failed password" message is now
// part of [wp_login_form].
add_shortcode( 'logincheck', '__return_empty_string' );

/*
 * On a failed front-end login (the Limited Access login form), go back to
 * the page the form was on with ?loginf=failed, so it can say "Failed
 * password". Only pages on this site: wp_get_referer() and
 * wp_safe_redirect() refuse other hosts, so the referrer can't be used to
 * send people to another website (open redirect).
 */
add_action( 'wp_login_failed', 'my_front_end_login_fail' );
function my_front_end_login_fail( $username ) {
	$referrer = wp_get_referer(); // false unless it is a URL on this site
	if ( ! $referrer || false !== strpos( $referrer, 'wp-login' ) || false !== strpos( $referrer, 'wp-admin' ) ) {
		return;
	}
	wp_safe_redirect( add_query_arg( 'loginf', 'failed', $referrer ) );
	exit;
}
/*
 * [wp_login_form …]: the Limited Access login form, built into the theme so it
 * no longer needs the "WP Login Form" plugin. Same name and options as the
 * plugin, so the page content stays as it is:
 *   value_username, redirect, label_username, label_password, label_log_in,
 *   remember, value_remember, lost_password (+ form_id, id_* as in WordPress).
 * It uses WordPress's own login form (wp_login_form()), so security and
 * reCAPTCHA plugins that hook into that form keep working.
 * With a preset username and the label "-" (how Limited Access is set up),
 * the username is sent as a hidden field and only the password is asked for.
 * When the visitor is already logged in, it shows a log-out button instead.
 * Registered late and only if no plugin provides the shortcode, so while the
 * plugin is still active it keeps working as before.
 */
function apt_login_form_shortcode( $atts ) {
	// Already logged in: no form, a log-out button instead. Afterwards, back to
	// the Limited Access login page (4452) if it is public, else the home page.
	if ( is_user_logged_in() ) {
		$after = 'publish' === get_post_status( 4452 ) ? get_permalink( 4452 ) : home_url( '/' );
		return '<p class="apt-logout"><span>' . esc_html__( 'You are logged in.', 'akseki-vi' ) . '</span>'
			. '<a class="apt-logout-link" href="' . esc_url( wp_logout_url( $after ) ) . '">' . esc_html__( 'Log out', 'akseki-vi' ) . '</a></p>';
	}
	$atts = (array) $atts;
	// Back from a wrong password (see my_front_end_login_fail()).
	$failed = isset( $_GET['loginf'] ) ? '<p class="apt-login-failed" role="alert">' . esc_html__( 'Failed password. Please try again.', 'akseki-vi' ) . '</p>' : ''; // phpcs:ignore WordPress.Security.NonceVerification -- display only
	// Quotes typed in the visual editor can arrive as curly quotes.
	foreach ( $atts as $k => $v ) {
		$atts[ $k ] = preg_replace( '/^[\s"\'\x{201C}\x{201D}\x{2018}\x{2019}\x{2033}]+|[\s"\'\x{201C}\x{201D}\x{2018}\x{2019}\x{2033}]+$/u', '', (string) $v );
	}
	$off  = function ( $v ) {
		return in_array( strtolower( (string) $v ), array( '0', 'false', 'no', 'off', '' ), true );
	};
	$args = array(
		'echo'           => false,
		'redirect'       => isset( $atts['redirect'] ) ? $atts['redirect'] : get_permalink(),
		'label_username' => isset( $atts['label_username'] ) ? $atts['label_username'] : __( 'Username or Email Address' ),
		'label_password' => isset( $atts['label_password'] ) ? $atts['label_password'] : __( 'Password' ),
		'label_remember' => isset( $atts['label_remember'] ) ? $atts['label_remember'] : __( 'Remember Me' ),
		'label_log_in'   => isset( $atts['label_log_in'] ) ? $atts['label_log_in'] : __( 'Log In' ),
		'value_username' => isset( $atts['value_username'] ) ? $atts['value_username'] : '',
		'remember'       => isset( $atts['remember'] ) ? ! $off( $atts['remember'] ) : true,
		'value_remember' => isset( $atts['value_remember'] ) ? ! $off( $atts['value_remember'] ) : false,
	);
	foreach ( array( 'form_id', 'id_username', 'id_password', 'id_remember', 'id_submit' ) as $key ) {
		if ( ! empty( $atts[ $key ] ) ) {
			$args[ $key ] = $atts[ $key ];
		}
	}
	$args['redirect'] = wp_validate_redirect( $args['redirect'], home_url( '/' ) );
	$form             = wp_login_form( $args );

	// Preset username without a real label: send it hidden, ask only for the password.
	if ( '' !== $args['value_username'] && in_array( trim( $args['label_username'] ), array( '', '-' ), true ) ) {
		$form = preg_replace(
			'#<p class="login-username">.*?</p>#s',
			'<input type="hidden" name="log" value="' . esc_attr( $args['value_username'] ) . '" autocomplete="username">',
			$form,
			1
		);
	}
	if ( isset( $atts['lost_password'] ) && ! $off( $atts['lost_password'] ) ) {
		$form .= '<p class="login-lost-password"><a href="' . esc_url( wp_lostpassword_url( $args['redirect'] ) ) . '">' . esc_html__( 'Lost your password?' ) . '</a></p>';
	}
	return $failed . $form;
}
add_action( 'init', function () {
	if ( ! shortcode_exists( 'wp_login_form' ) ) {
		add_shortcode( 'wp_login_form', 'apt_login_form_shortcode' );
	}
}, 99 );

// Limited Access files: only logged-in visitors can open uploads/private/.
require_once get_template_directory() . '/inc/limited-access-files.php';
// Posts → Categories: a "Theme" dropdown that edits the theme pages' tpc_topics.
require_once get_template_directory() . '/inc/category-themes.php';
// Post edit screen: a "Statement settings" box for the special tags (SM_, SC_, hasnocontent …).
require_once get_template_directory() . '/inc/statement-settings.php';
// Tools → Content check: what needs an editor's attention.
require_once get_template_directory() . '/inc/content-check.php';
// No emoji script; admin area for editors only (were plugins).
require_once get_template_directory() . '/inc/site-tweaks.php';
require_once get_template_directory() . '/inc/news-page.php';
// Appearance → Home page content: About text, figures, cooperation cards, contact.
require_once get_template_directory() . '/inc/home-content.php';
// Posts → Source watch: new ASEAN Plus Three items on asean.org and other feeds.
require_once get_template_directory() . '/inc/source-watch.php';
// Keep thin automatic pages (tags, feeds, shortlinks…) out of Google.
require_once get_template_directory() . '/inc/indexing.php';

//custom field dropdown display limit
add_filter( 'postmeta_form_limit', 'meta_limit_increase' );
function meta_limit_increase( $limit ) {
    return 200;
}
// No icon font (dashicons) for visitors: the theme's few icons are drawn as
// SVG (footer social icons, sidebar arrows, external-link and date icons).
// WordPress still loads it for logged-in users' admin bar.
//add extra field to category
function addExtraFieldToCat(){
    $cat_extra = get_term_meta($_GET['tag_ID'], '_catextra', true);
    ?> 
    <tr class="form-field">
        <th scope="row" valign="top"><label for="cat_extra"><?php _e('Category Extra Property'); ?></label></th>
        <td>

		<select name="cat_extra" id="cat_extra">
			<option value="">none</option>
			<option value="has_no_listing"<?php echo(($cat_extra=="has_no_listing")?" selected":""); ?>>Has no Listing</option>
			<option value="inactive"<?php echo(($cat_extra=="inactive")?" selected":""); ?>>Inactive</option>
		</select>
            <span class="description"><?php _e('Extra Property for category '); ?></span>
        </td>
    </tr>
    <?php

}
add_action ( 'edit_category_form_fields', 'addExtraFieldToCat');

function saveCategoryFields() {
    if ( isset( $_POST['cat_extra'] ) ) {
        update_term_meta($_POST['tag_ID'], '_catextra', $_POST['cat_extra']);
    }
}
add_action ( 'edited_category', 'saveCategoryFields');

//handle ajax for archives
//get category descriptors
function echo_category_descriptor($current_category_id){
	$catdesc_echo = '';
	$catdesc_children = get_children(3236);
	$current_category_obj = get_category($current_category_id);
	foreach($catdesc_children as $child):

	    //if(urlencode(get_queried_object()->name) == urlencode($child->post_title)):
		if($current_category_obj->name === $child->post_title):

			$catdesc_content = $child->post_content;
			// The descriptor's featured image used to sit faded behind the page title;
			// the redesigned header is plain, so it is no longer used.
		endif;
	endforeach;	
	if(!empty($catdesc_content)):
		$catdesc_echo .= '<div class="catdesc apt-catdesc">';
		$catdesc_echo .= $catdesc_content;			
		$catdesc_echo .= '</div>';				
	endif;
	return($catdesc_echo);
	//end cat desc
}

/**
 * Level page (Ministerial-level, SOM, Director-General …): one collapsible
 * section per pillar, with its themes and a card per meeting showing the
 * latest statements. Built directly, without plugin shortcodes.
 */
function akseki_get_level_resources( $level_id ) {
	$pillars = get_categories( array( 'parent' => $level_id ) );
	$out     = '<div class="apt-level-pillars">';
	foreach ( $pillars as $n => $pillar ) {
		$topics = get_categories( array( 'parent' => $pillar->term_id ) );
		if ( ! $topics ) {
			continue;
		}
		$out .= '<details class="apt-level-pillar pillar-' . esc_attr( apt_pillar_key( $pillar->name ) ) . '"' . ( 0 === $n ? ' open' : '' ) . '>';
		$out .= '<summary><h2 class="apt-level-pillar-name">' . esc_html( $pillar->name ) . '</h2></summary><div class="apt-level-pillar-body">';

		$themes = akseki_get_themes_in_pillar( $pillar->term_id );
		if ( $themes ) {
			$out .= '<p class="apt-pillar-themes"><span>Themes:</span> ';
			foreach ( $themes as $theme_id => $theme ) {
				$out .= '<a class="badge badge-secondary" href="' . esc_url( get_permalink( $theme_id ) ) . '">' . esc_html( $theme ) . '</a> ';
			}
			$out .= '</p>';
		}

		$out .= '<div class="apt-meeting-grid">';
		foreach ( $topics as $topic ) {
			$url  = get_category_link( $topic->term_id );
			$out .= '<section class="apt-meeting"><h3 class="apt-meeting-name"><a href="' . esc_url( $url ) . '">' . esc_html( $topic->name ) . '</a></h3>';
			if ( trim( (string) $topic->description ) ) {
				$out .= '<p class="apt-meeting-full">' . esc_html( wp_strip_all_tags( $topic->description ) ) . '</p>';
			}
			if ( 'has_no_listing' !== get_term_meta( $topic->term_id, '_catextra', true ) ) {
				$latest = get_posts( array( 'cat' => $topic->term_id, 'numberposts' => 3, 'post_status' => 'publish' ) );
				if ( $latest ) {
					$out .= '<ul class="apt-meeting-latest">';
					foreach ( $latest as $p ) {
						$title = get_the_title( $p );
						$item  = has_tag( 'hasnocontent', $p ) ? $title : '<a href="' . esc_url( get_permalink( $p ) ) . '">' . $title . '</a>';
						$out  .= '<li>' . $item . '<span class="akseki-meta">' . akseki_get_the_meta( $p->ID ) . '</span></li>';
					}
					$out .= '</ul>';
				}
			}
			$out .= '<a class="apt-meeting-all apt-arrow" href="' . esc_url( $url ) . '">' . esc_html( sprintf( 'All %s', $topic->name ) ) . '</a></section>';
		}
		$out .= '</div></div></details>';
	}
	return $out . '</div>';
}

function akseki_get_themes_in_pillar($pillar_id){
	//returns an associative array of id=>name
	$themes = get_children(5733);
	$pil_obj = get_category($pillar_id);
	$return = '';
	$themes_arr = [];
	foreach($themes as $themepage){
		$tpc_pillar = get_post_custom_values( "tpc_pillar", $themepage->ID);

		if(!empty($tpc_pillar[0]) && str_starts_with($pil_obj->slug,$tpc_pillar[0])){
			$themes_arr[$themepage->ID] = $themepage->post_title;
		}
	}

	return $themes_arr;

}

function notifyauthor($post_id) {
    // Check if notification already sent for this post
    if (get_post_meta($post_id, '_notification_sent', true)) {
        return; // Email already sent, exit
    }

    $postm = get_post($post_id);

    // Only send for published posts
    if ($postm->post_status !== 'publish' || $postm->post_type !== 'post') {
        return;
    }

    if(has_tag("hasnocontent",$post_id)){
        $hastag = "<li>This post has no content. It will display only its title, location and date.</li>";
    }elseif(has_tag("hyperlinked",$post_id)){
        $hastag = "<li>This post redirects to an external url.</li>";
    }else{
        $hastag = "";
    }

    // FIX: Use $postm instead of undefined $post
    $author = get_userdata($postm->post_author);

    $subject = "[Notification] A post has been published on the ASEAN Plus Three website: ".$postm->post_title;

    // Recipients can be edited on Settings > Email (APT Mail plugin); this
    // list is used when that is empty or the plugin is off.
    $multiple_recipients = apply_filters( 'apt_publish_notify_recipients', array(
        'fifi@asean.org',
        'pham.thu@asean.org',
        'andi.rozin@asean.org',
        'fatahillah.rachman@asean.org'
    ) );

    $headers = array('Content-Type: text/html; charset=UTF-8');

    $message = "
        <p>Good day,</p>
        <p>This is an automated notification e-mail. Please do not reply to sender.</p>
        <p>A new post has been published in asean+3 website:</p>
        <ul>
        <li>Title: <b>\"".esc_html( $postm->post_title )."\"</b></li>
        <li>View: ".get_permalink( $post_id )."</li>
        ".$hastag."
        </ul>
        <p>Thank you and best regards.</p>";

    $sent = false;
    foreach($multiple_recipients as $recipient){
        if ( wp_mail($recipient, $subject, $message, $headers) ) {
            $sent = true;
        }
    }

    // Mark as sent (no second email later) only if it went out; a failed
    // notification can be sent again from Settings > Email.
    if ( $sent ) {
        update_post_meta($post_id, '_notification_sent', true);
    }
}

// Use better hook that only fires when post becomes published
add_action('transition_post_status', 'check_post_published', 10, 3);

function check_post_published($new_status, $old_status, $post) {
    // Only send email when post transitions TO 'publish' (first time published)
    if ($new_status === 'publish' && $old_status !== 'publish' && $post->post_type === 'post') {
        notifyauthor($post->ID);
    }
}

/*
 * SEO
 * Yoast SEO (active on the live site) owns titles, canonical URLs, robots
 * rules, breadcrumbs, structured data and the XML sitemap. The theme only:
 * - lets WordPress/Yoast print the <title> (title-tag support);
 * - fills in a meta description where none was written, from facts already on
 *   the page (homepage: ASEC's own wording; statements: title, place and date;
 *   pages: their opening text; categories: their description);
 * - prints Open Graph / Twitter tags for link previews when Yoast's own are
 *   switched off, with a default image for pages without a featured image.
 */
add_theme_support( 'title-tag' );

/** Default image for link previews (1200x630, official colours, flag line-up). */
function apt_seo_default_image() {
	return array(
		'url'    => get_stylesheet_directory_uri() . '/img/share/apt-share-1200x630.jpg',
		'width'  => 1200,
		'height' => 630,
	);
}

/** Shorten to at most $max characters at a word boundary. */
function apt_seo_trim( $text, $max = 160 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) ) );
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $max - 1 );
	$sp  = mb_strrpos( $cut, ' ' );
	return rtrim( $sp > $max * 0.6 ? mb_substr( $cut, 0, $sp ) : $cut, " ,;:.-\u{2013}\u{2014}" ) . "\u{2026}";
}

/** "Kuala Lumpur, Malaysia, 27 October 2025" for a statement, from its location and date. */
function apt_seo_place_and_date( $post_id ) {
	$parts    = array();
	$location = akseki_get_mec_location( $post_id );
	if ( $location ) {
		$city    = is_numeric( $location ) ? get_term( (int) $location )->name : $location;
		$country = get_the_country( $city );
		if ( ! empty( $country['country'] ) && $country['country'] !== $city ) {
			$parts[] = $city;
		}
		$parts[] = ! empty( $country['country'] ) ? $country['country'] : $city;
	}
	$parts[] = get_the_date( 'j F Y', $post_id );
	return implode( ', ', array_filter( $parts ) );
}

/** A factual description for the current page, or '' when there is nothing good to say. */
function apt_seo_auto_description() {
	if ( is_front_page() ) {
		// ASEAN Secretariat wording, as on the home page (its first sentence, verbatim).
		return "ASEAN Plus Three (APT) consists of the 11 ASEAN Member States and the People\u{2019}s Republic of China, Japan and the Republic of Korea.";
	}
	if ( is_singular( 'post' ) ) {
		$id = get_queried_object_id();
		return apt_seo_trim( wp_strip_all_tags( get_the_title( $id ) ) . '. ' . apt_seo_place_and_date( $id ) . '.' );
	}
	if ( is_singular() ) {
		$post = get_queried_object();
		$text = has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content );
		return apt_seo_trim( $text );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		$desc = trim( wp_strip_all_tags( term_description( $term ) ) );
		return apt_seo_trim( $desc ? $term->name . ': ' . $desc : $term->name . ': ASEAN Plus Three statements and documents, newest first.' );
	}
	return '';
}

/** Whether Yoast SEO is printing its own Open Graph tags. */
function apt_seo_yoast_social_on() {
	return defined( 'WPSEO_VERSION' ) && class_exists( 'WPSEO_Options' ) && WPSEO_Options::get( 'opengraph', true );
}

// With Yoast: fill empty descriptions, and give link previews a default image.
add_filter( 'wpseo_metadesc', function ( $desc ) { return $desc ? $desc : apt_seo_auto_description(); } );
add_filter( 'wpseo_opengraph_desc', function ( $desc ) { return $desc ? $desc : apt_seo_auto_description(); } );
add_filter( 'wpseo_twitter_description', function ( $desc ) { return $desc ? $desc : apt_seo_auto_description(); } );
add_action( 'wpseo_add_opengraph_images', function ( $images ) {
	if ( is_object( $images ) && method_exists( $images, 'has_images' ) && ! $images->has_images() ) {
		$img = apt_seo_default_image();
		$images->add_image( array( 'url' => $img['url'], 'width' => $img['width'], 'height' => $img['height'] ) );
	}
} );

// Without Yoast (or with its social tags off): print the description and preview tags ourselves.
add_action( 'wp_head', function () {
	if ( is_search() || is_404() ) {
		return;
	}
	$desc = apt_seo_auto_description();
	if ( ! defined( 'WPSEO_VERSION' ) && $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( apt_seo_yoast_social_on() ) {
		return;
	}
	if ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_singular() ) {
		$url = get_permalink( get_queried_object_id() );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$url = get_term_link( get_queried_object() );
	} else {
		return;
	}
	$img = apt_seo_default_image();
	if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id( get_queried_object_id() ), 'large' );
		if ( $src ) {
			$img = array( 'url' => $src[0], 'width' => $src[1], 'height' => $src[2] );
		}
	}
	$title = wp_get_document_title();
	$tags  = array(
		'og:locale'      => 'en_US',
		'og:type'        => is_singular( 'post' ) ? 'article' : 'website',
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:title'       => $title,
		'og:description' => $desc,
		'og:url'         => is_wp_error( $url ) ? '' : $url,
		'og:image'       => $img['url'],
		'og:image:width' => $img['width'],
		'og:image:height'=> $img['height'],
	);
	foreach ( $tags as $property => $content ) {
		if ( '' !== (string) $content ) {
			echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( $content ) . '">' . "\n";
		}
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}, 5 );

/**
 * One news card for the current post in the loop: image, pillar tag, short
 * name (linked; the link covers the card), full title, place and date, level,
 * theme, topic and "Read more". Used by the homepage carousel and the news
 * page, so both always look the same.
 *
 * @param string $heading_tag h2 or h3, to fit the page's heading outline.
 */
function apt_news_card_html( $heading_tag = 'h3' ) {
	$heading_tag = in_array( $heading_tag, array( 'h2', 'h3', 'h4' ), true ) ? $heading_tag : 'h3';
	$latest = array();
	$latest['title'] = get_the_title();
	$latest['id'] = get_the_ID();
	$curcats =  get_the_category();
	$catspluck =  wp_list_pluck($curcats, 'term_id');
	$latest['category'] = get_category(akseki_get_descat($catspluck));
	$latest['permalink'] = get_the_permalink();
	$latest['shortname'] = akseki_get_shortname($latest["title"],$latest["id"],0);

	// Level
	$levobj=get_category(akseki_get_level_from_id($latest['id'],'post'));
	if($levobj){
		$ll='<div class="news-meta-item"><dt class="meta-label">Level:</dt> <dd><a href="'.get_category_link($levobj->term_id).'">'.$levobj->name.'</a></dd></div>';
	}else{
		$ll='';
	}

	// Pillar
	$pilobj=get_category(akseki_get_level_from_id($latest['id'],'post','pillar'));
	if($pilobj && '' !== trim($pilobj->name)){
		$lp='<a class="apt-tag news-card-tag pillar-'.apt_pillar_key($pilobj->name).'" href="'.get_category_link($pilobj->term_id).'">'.$pilobj->name.'</a>';
	}else{
		$lp='';
	}

	// Theme
	$thmid = akseki_get_theme_from_id($latest['id'],'post');
	if(!empty($thmid)){
		$thmobj=get_post(akseki_get_theme_from_id($latest['id'],'post'));
		$lt='<div class="news-meta-item"><dt class="meta-label">Theme:</dt> <dd><a href="'.get_permalink($thmobj->ID).'">'.$thmobj->post_title.'</a></dd></div>';
	}else{
		$lt='';
	}

	// Read more button
	// Cards with content get a heading link (stretched over the card) and a
	// "Read more" whose accessible name says what it is about.
	$card_title = akseki_get_shortname($latest["title"],$latest["id"],0);
	if(!has_tag("hasnocontent")){
		$heading = '<a class="card-link" href="'.esc_url($latest["permalink"]).'">'.$card_title.'</a>';
		$readmore = '<footer class="news-card-cta"><a class="news-cta-btn apt-arrow" href="'.esc_url($latest["permalink"]).'">Read more<span class="screen-reader-text"> about '.esc_html(wp_strip_all_tags($latest["title"])).'</span></a></footer>';
	}else{
		$heading = $card_title;
		$readmore = '';
	}

	$card_class = 'news-card-default';
	if ( $levobj && 'Ministerial-level Statements' === $levobj->name ) {
	$card_class = 'news-card-ministerial';
	} elseif ( $levobj && 'Summit Level Statements' === $levobj->name ) {
	$card_class = 'news-card-summit';
	}
	$st = ( $latest['shortname'] !== $latest['title'] ) ? '<p class="news-card-subtitle">' . $latest['title'] . '</p>' : '';
	// Backdrop (blurred): the small thumbnail is enough. Photo: responsive; no photo: the flags image.
	$thethumb = has_post_thumbnail( $latest['id'] ) ? get_the_post_thumbnail_url( $latest['id'], 'thumbnail' ) : wp_get_attachment_image_url( 1007, 'thumbnail' );
	$apt_img = apt_post_image_html( $latest['id'], 'medium_large', '(max-width: 600px) 92vw, 380px', array( 'class' => 'news-card-image' ) );
	$topic = ( $latest['category'] && ! is_wp_error( $latest['category'] ) )
	? '<div class="news-meta-item"><dt class="meta-label">Topic:</dt> <dd><a href="' . esc_url( get_category_link( $latest['category'] ) ) . '">' . $latest['category']->description . '</a></dd></div>'
	: '';
	return '<article class="news-card ' . $card_class . '" id="card_com-' . get_the_ID() . '"' . ( $thethumb ? ' style="--card-img: url(&quot;' . esc_url( $thethumb ) . '&quot;)"' : '' ) . '>'
	. $apt_img
	. '<header class="news-card-header">' . $lp . '<' . $heading_tag . ' class="news-card-title">' . $heading . '</' . $heading_tag . '>' . $st
	. '<p class="news-card-date">' . akseki_get_the_meta( $latest['id'] ) . '</p></header>'
	. '<dl class="news-meta-grid">' . $ll . $lt . $topic . '</dl>'
	. $readmore
	. '</article>';
}

/**
 * Flag line-up: ASEAN Member States in alphabetical order with the ASEAN flag
 * on the right (ASEAN Flag Guidelines, para 16), then China, Japan and the
 * Republic of Korea. Official names are in alt/title only. Used by the home
 * page hero and the About page.
 * Flags: flag-icons (MIT, img/flags/1x1/LICENSE-flag-icons); ASEAN flag:
 * img/flags/asean.svg.
 *
 * @param string $class Class of the wrapper (layout differs per page).
 */
function apt_flag_lineup_html( $class = 'apt-hero-flags' ) {
	$dir    = get_stylesheet_directory_uri() . '/img/flags/';
	$groups = array(
		array(
			'bn'    => 'Brunei Darussalam',
			'kh'    => 'Kingdom of Cambodia',
			'id'    => 'Republic of Indonesia',
			'la'    => "Lao People's Democratic Republic",
			'my'    => 'Malaysia',
			'mm'    => 'Republic of the Union of Myanmar',
			'ph'    => 'Republic of the Philippines',
			'sg'    => 'Republic of Singapore',
			'th'    => 'Kingdom of Thailand',
			'tl'    => 'Democratic Republic of Timor-Leste',
			'vn'    => 'Socialist Republic of Viet Nam',
			'asean' => 'Association of Southeast Asian Nations (ASEAN)',
		),
		array(
			'cn' => "People's Republic of China",
			'jp' => 'Japan',
			'kr' => 'Republic of Korea',
		),
	);
	$html = '<div class="' . esc_attr( $class ) . '" role="group" aria-label="' . esc_attr__( 'ASEAN Member States, ASEAN, and China, Japan and the Republic of Korea', 'akseki-vi' ) . '">';
	foreach ( $groups as $flags ) {
		$html .= '<ul class="apt-flag-group">';
		foreach ( $flags as $code => $name ) {
			$src   = $dir . ( 'asean' === $code ? 'asean.svg' : '1x1/' . $code . '.svg' );
			$html .= '<li' . ( 'asean' === $code ? ' class="is-asean"' : '' ) . '><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $name ) . '" title="' . esc_attr( $name ) . '" width="26" height="26" decoding="async"></li>';
		}
		$html .= '</ul>';
	}
	return $html . '</div>';
}

/**
 * One statement row: the meeting's short name and year on the left, the full
 * title (linked unless upcoming) and place/date on the right. Used by meeting
 * pages (listings-list.php), the Statements page and the level pages.
 *
 * @param int    $id          Post ID.
 * @param string $heading_tag Heading for the title, to fit the page outline.
 */
function apt_statement_row_html( $id, $heading_tag = 'h2' ) {
	$heading_tag = in_array( $heading_tag, array( 'h2', 'h3', 'h4' ), true ) ? $heading_tag : 'h2';
	$sm      = akseki_get_sm( $id );
	$sc      = akseki_get_sc_raw( $id );
	$future  = get_the_date( 'Y-m-d H:i:s', $id ) > current_time( 'Y-m-d H:i:s' );
	$label   = ! empty( $sm ) ? 'Extra' : akseki_get_shortname( get_the_title( $id ), $id );
	$year    = ( $future && 'SC_nodate' === $sc ) ? '<em>tbd</em>' : get_the_date( 'Y', $id );
	$title   = get_the_title( $id );
	if ( $future ) {
		$link = '<span class="apt-tag">Upcoming</span> ' . $title;
	} elseif ( has_tag( 'hasnocontent', $id ) ) {
		$link = $title; // listed for the record; there is no page to open
	} else {
		$link = '<a href="' . esc_url( get_permalink( $id ) ) . '">' . $title . '</a>';
	}
	$meta    = akseki_get_the_meta( $id );
	if ( $future && 'SC_year' === $sc ) {
		$meta = preg_replace( '/<span class="meta-date">.+?<\/span>/', akseki_get_sc( $id ), $meta );
	} elseif ( $future && 'SC_month' === $sc ) {
		$meta = preg_replace( '/<span class="meta-date">.+?<\/span>/', get_the_date( 'F Y', $id ), $meta );
	} elseif ( $future && 'SC_nodate' === $sc ) {
		$meta = '<em>tbd</em>';
	}
	return '<li class="list-group-item apt-statement-row' . ( $future ? ' thefuturepost is-upcoming' : '' ) . '">'
		. '<div class="apt-statement-when"><span class="apt-statement-short">' . $label . '</span><span class="apt-statement-year">' . $year . '</span></div>'
		. '<div class="apt-statement-main"><' . $heading_tag . ' class="apt-statement-title">' . $link . '</' . $heading_tag . '>'
		. '<p class="akseki-meta">' . $meta . '</p></div></li>';
}

/**
 * Where a document was adopted: [ label, category ID ] — "Summit Level
 * Statements", or "Ministerial Level › AMAF+3" — or [ '', 0 ] if neither.
 */
/**
 * Public "Source:" line under a statement: the web links (http/https) of its
 * "source" custom field, each shown as the website's name (asean.org) and
 * opening the original in a new tab. Anything else in the field (plain-text
 * notes, intranet or local addresses, Limited Access files) stays in the
 * editors-only Sources box.
 *
 * @param array|null $sources Values of the "source" custom field.
 */
function apt_public_sources_html( $sources ) {
	$links = array();
	foreach ( (array) $sources as $source ) {
		$url  = trim( (string) $source );
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( ! preg_match( '#^https?://#i', $url ) || '' === $host
			|| false === strpos( $host, '.' )                       // intranet names, localhost
			|| filter_var( $host, FILTER_VALIDATE_IP )              // bare IP addresses
			|| false !== strpos( $url, '/uploads/private/' ) ) {    // Limited Access files
			continue;
		}
		$links[ $url ] = preg_replace( '/^www\./', '', $host );
	}
	if ( ! $links ) {
		return '';
	}
	// The same website more than once: add the page's last part so the links differ.
	$counts = array_count_values( $links );
	$items  = array();
	foreach ( $links as $url => $name ) {
		if ( $counts[ $name ] > 1 ) {
			$last = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
			$name = $last ? $name . ' – ' . rawurldecode( $last ) : $name;
		}
		$items[] = '<a class="apt-ext" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $name ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'akseki-vi' ) . '</span></a>';
	}
	$label = count( $items ) > 1 ? __( 'Sources:', 'akseki-vi' ) : __( 'Source:', 'akseki-vi' );
	return '<p class="apt-post-source">' . esc_html( $label ) . ' ' . implode( ', ', $items ) . '</p>';
}

function apt_document_source( $id ) {
	if ( in_category( 41, $id ) ) {
		return array( 'Summit Level Statements', 41 );
	}
	if ( akseki_post_is_in_a_subcategory( 43, $id ) ) {
		foreach ( akseki_get_ministrial_categories() as $slug ) {
			$cat = get_term_by( 'slug', $slug, 'category' );
			if ( $cat && in_category( $cat->term_id, $id ) ) {
				return array( "Ministerial Level \u{203A} " . $cat->name, $cat->term_id );
			}
		}
	}
	return array( '', 0 );
}

/**
 * One document row (agreements, work plans, other documents): the title
 * linked, place and date, and where it was adopted.
 *
 * @param bool $with_source Show "Adopted at …" (agreements); off for work plans.
 */
function apt_document_row_html( $id, $with_source = true ) {
	$sm    = akseki_get_sm( $id );
	$title = get_the_title( $id );
	$out   = '<article class="apt-doc" id="post-' . (int) $id . '">';
	$out  .= '<h2 class="apt-doc-title"><a href="' . esc_url( get_permalink( $id ) ) . '">' . ( $sm ? esc_html( $sm ) : $title ) . '</a></h2>';
	if ( $sm ) {
		$out .= '<p class="apt-doc-full">' . $title . '</p>';
	}
	$out .= '<p class="akseki-meta">' . akseki_get_the_meta( $id ) . '</p>';
	if ( $with_source ) {
		list( $label, $cat ) = apt_document_source( $id );
		if ( $cat ) {
			$out .= '<p class="apt-doc-source">' . esc_html__( 'Adopted at', 'akseki-vi' ) . ': <a href="' . esc_url( get_category_link( $cat ) ) . '">' . esc_html( $label ) . '</a></p>';
		}
	}
	return $out . '</article>';
}

/** Years that have statements (newest first), for the Archives page. Cached; cleared when a post is saved. */
function apt_archive_years() {
	$years = get_transient( 'apt_archive_years' );
	if ( is_array( $years ) ) {
		return $years;
	}
	$ids   = get_posts( array(
		'cat'         => get_term_children( 7, 'category' ),
		'post_status' => 'publish',
		'numberposts' => -1,
		'fields'      => 'ids',
	) );
	$years = array();
	foreach ( $ids as $id ) {
		$years[ (int) get_the_date( 'Y', $id ) ] = true;
	}
	$years = array_keys( $years );
	rsort( $years );
	set_transient( 'apt_archive_years', $years, 12 * HOUR_IN_SECONDS );
	return $years;
}

/**
 * <img> for a statement: its photo, or the site's default image (the
 * countries' flags, attachment 1007) when it has none. Responsive srcset/sizes.
 */
function apt_post_image_html( $post_id, $size, $sizes, $attr = array() ) {
	$attr  = array_merge( array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => $sizes ), $attr );
	$image = has_post_thumbnail( $post_id ) ? get_post_thumbnail_id( $post_id ) : 1007;
	return wp_get_attachment_image( $image, $size, false, $attr );
}

// WordPress 6.7+ "auto sizes": lazy images get sizes="auto" plus a style that
// reserves 3000x1500px until they load, which stretched the news cards. The
// theme gives every image its own sizes, so the feature is turned off.
add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

/*
 * Loading performance (from the live Lighthouse reports)
 */

// The home page has no share buttons: skip the social-sharing plugin's files there.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_front_page() ) {
		return;
	}
	foreach ( array( wp_scripts(), wp_styles() ) as $deps ) {
		foreach ( (array) $deps->queue as $handle ) {
			$src = isset( $deps->registered[ $handle ] ) ? (string) $deps->registered[ $handle ]->src : '';
			if ( false !== strpos( $src, 'sassy-social-share' ) ) {
				$deps === wp_scripts() ? wp_dequeue_script( $handle ) : wp_dequeue_style( $handle );
			}
		}
	}
}, 100 );

// Start downloading the (self-hosted) Latin font file with the page, instead
// of only once the stylesheet has been read. The extended-Latin file is only
// fetched on pages that need it.
add_action( 'wp_head', function () {
	echo '<link rel="preload" href="' . esc_url( get_stylesheet_directory_uri() . '/assets/fonts/outfit-latin.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}, 1 );

/*
 * Overview of ASEAN Plus Three Cooperation page.
 *
 * The section shortcuts at the top of the page (#subsectionPolitical …) and
 * the page layout (assets/css/apt-design.css) rely on IDs on the four section
 * headings. When the page text is replaced (e.g. pasted in from the yearly
 * Word file), those IDs are easily lost, so the theme adds them itself: each
 * heading is recognised by its wording, at any heading level (h2–h4).
 */
function apt_is_overview_page( $post = null ) {
	$post = get_post( $post );
	return $post && 'page' === $post->post_type && 'overview-of-asean-plus-three-cooperation' === $post->post_name;
}

function apt_overview_section_ids( $content ) {
	if ( ! apt_is_overview_page() ) {
		return $content;
	}
	$sections = array(
		'Political-Security'   => 'subsectionPolitical',
		'Economic and Finance' => 'subsectionEconomic',
		'Socio-Cultural'       => 'subsectionSocio',
		'Track 1.5'            => 'subsectionTracks',
	);
	return preg_replace_callback(
		'#<(h[2-4])((?:\s[^>]*)?)>(.*?)</\1>#is',
		function ( $m ) use ( $sections ) {
			$text = trim( html_entity_decode( wp_strip_all_tags( $m[3] ), ENT_QUOTES, 'UTF-8' ), " \t\n\r\0\x0B\xC2\xA0" );
			foreach ( $sections as $start => $id ) {
				if ( 0 !== stripos( $text, $start ) ) {
					continue;
				}
				// The shortcuts point at these IDs, so they replace any other ID.
				$attrs = preg_replace( '/\sid\s*=\s*(["\'])[^"\']*\1/i', '', $m[2] ) . ' id="' . $id . '"';
				if ( preg_match( '/\sclass\s*=\s*(["\'])(.*?)\1/i', $attrs, $c ) ) {
					if ( ! preg_match( '/(^|\s)subsection(\s|$)/', $c[2] ) ) {
						$attrs = str_replace( $c[0], ' class=' . $c[1] . trim( $c[2] . ' subsection' ) . $c[1], $attrs );
					}
				} else {
					$attrs .= ' class="subsection"';
				}
				return '<' . $m[1] . $attrs . '>' . $m[3] . '</' . $m[1] . '>';
			}
			return $m[0];
		},
		$content
	);
}
add_filter( 'the_content', 'apt_overview_section_ids', 20 );

/*
 * "How to update this page": a box in the editor sidebar, shown only when
 * editing the Overview page. Same text as docs/how-to-update-overview.md.
 */
add_action( 'add_meta_boxes_page', function ( $post ) {
	if ( ! apt_is_overview_page( $post ) ) {
		return;
	}
	add_meta_box( 'apt-overview-help', __( 'How to update this page', 'akseki-vi' ), 'apt_overview_help_box', 'page', 'side', 'high' );
} );

function apt_overview_help_box() {
	?>
	<ol style="margin:0 0 0 1.2em;padding:0">
		<li><strong>Ask for the Word file</strong> of the new Overview, not only the PDF. Text pasted from Word keeps its paragraphs and links; text copied from a PDF does not.</li>
		<li><strong>Keep the text exactly as written</strong> by the ASEAN Secretariat. Do not reword or correct it.</li>
		<li><strong>Replace the paragraphs</strong> one section at a time. Keep the four section headings worded as they are now: <em>Political-Security Cooperation</em>, <em>Economic and Finance Cooperation</em>, <em>Socio-Cultural Cooperation</em>, <em>Track 1.5 and Track 2 Cooperation</em>. The site finds the sections by these words.</li>
		<li><strong>Leave the shortcodes</strong> (text in square brackets, such as <code>[akseki_get_recent_posts …]</code> and <code>[akseki_cat_children …]</code>) where they are. They show the Summit and mechanism cards and update by themselves.</li>
		<li><strong>Add the links</strong> to the related statements: search the site for the meeting, copy its address, select the words in the text and add the link.</li>
		<li><strong>New PDF:</strong> upload it under Media, then change the link of the <em>Download Overview in PDF</em> button to the new file.</li>
		<li><strong>Preview</strong>, check each section shortcut at the top jumps to its section, then <strong>Update</strong>.</li>
	</ol>
	<p style="margin:10px 0 0">If something looks wrong, use <em>Revisions</em> (in the Page panel) to go back to the previous version.</p>
	<?php
}
