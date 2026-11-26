<?php
/**
 * News page (page-news-2.php): statements and news as a list grouped by
 * month, with filters by pillar, level and year.
 *
 * The filters are plain links and a GET form (?pillar=ec&level=summit&yr=2025),
 * so every filtered list is an ordinary page that works without JavaScript
 * and that search engines and AI crawlers can follow. They use the existing
 * categories, so editors do nothing new:
 * - Pillar: the meeting category's parent (its slug contains "political",
 *   "economic" or "socio"), the same rule as the pillar tag on the cards
 *   (akseki_get_level_from_id()).
 * - Level: Summit Level Statements (41) or Ministerial-level Statements (43)
 *   with their sub-categories; "Others" is everything else.
 * - Year: the publication year. The query parameter is "yr" because
 *   WordPress itself uses "year".
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Pillar filters: key => [label, slug keyword]. */
function apt_news_pillars() {
	return array(
		'ps' => array( __( 'Political-Security', 'akseki-vi' ), 'political' ),
		'ec' => array( __( 'Economic', 'akseki-vi' ), 'economic' ),
		'sc' => array( __( 'Socio-Cultural', 'akseki-vi' ), 'socio' ),
	);
}

/** Level filters: key => [label, category ID or 0 for "the rest"]. */
function apt_news_levels() {
	return array(
		'summit'      => array( __( 'Summit', 'akseki-vi' ), 41 ),
		'ministerial' => array( __( 'Ministerial', 'akseki-vi' ), 43 ),
		'others'      => array( __( 'Others', 'akseki-vi' ), 0 ),
	);
}

/** The filters in the current URL, checked against the allowed values. */
function apt_news_filters() {
	// phpcs:disable WordPress.Security.NonceVerification -- read-only filters
	$p = isset( $_GET['pillar'] ) ? sanitize_key( wp_unslash( $_GET['pillar'] ) ) : '';
	$l = isset( $_GET['level'] ) ? sanitize_key( wp_unslash( $_GET['level'] ) ) : '';
	$y = isset( $_GET['yr'] ) ? absint( $_GET['yr'] ) : 0;
	// phpcs:enable
	return array(
		'pillar' => isset( apt_news_pillars()[ $p ] ) ? $p : '',
		'level'  => isset( apt_news_levels()[ $l ] ) ? $l : '',
		'yr'     => ( $y >= 1997 && $y <= (int) gmdate( 'Y' ) + 1 ) ? $y : 0,
	);
}

/** Category IDs of a pillar: the categories whose parent's slug has the keyword, and those parents. */
function apt_news_pillar_cats( $keyword ) {
	$ids = array();
	foreach ( get_categories( array( 'hide_empty' => false ) ) as $cat ) {
		if ( false !== strpos( $cat->slug, $keyword ) ) {
			$ids[] = (int) $cat->term_id;
		}
		if ( ! $cat->parent ) {
			continue;
		}
		$parent = get_category( $cat->parent );
		if ( $parent && ! is_wp_error( $parent ) && false !== strpos( $parent->slug, $keyword ) ) {
			$ids[] = (int) $cat->term_id;
		}
	}
	return array_values( array_unique( $ids ) );
}

/** WP_Query arguments for the filters. */
function apt_news_query_args( $filters, $paged, $per_page = 12 ) {
	$args = array(
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'order'          => 'DESC',
		'orderby'        => 'date',
	);
	$tax = array();
	if ( $filters['pillar'] ) {
		$ids   = apt_news_pillar_cats( apt_news_pillars()[ $filters['pillar'] ][1] );
		$tax[] = array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $ids ? $ids : array( 0 ), 'include_children' => true );
	}
	if ( $filters['level'] ) {
		$id = apt_news_levels()[ $filters['level'] ][1];
		if ( $id ) {
			$tax[] = array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => array( $id ), 'include_children' => true );
		} else {
			$tax[] = array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => array( 41, 43 ), 'include_children' => true, 'operator' => 'NOT IN' );
		}
	}
	if ( $tax ) {
		$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	if ( $filters['yr'] ) {
		$args['year'] = $filters['yr'];
	}
	return $args;
}

/** The years that have posts, newest first (cached for a day). */
function apt_news_years() {
	$years = get_transient( 'apt_news_years' );
	if ( false === $years ) {
		global $wpdb;
		$years = array_map( 'intval', $wpdb->get_col( "SELECT DISTINCT YEAR(post_date) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY 1 DESC" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		set_transient( 'apt_news_years', $years, DAY_IN_SECONDS );
	}
	return $years;
}
add_action( 'save_post_post', function () {
	delete_transient( 'apt_news_years' );
} );

/** URL of the news page with these filters (page 1). */
function apt_news_filter_url( $filters ) {
	$args = array_filter( $filters );
	return add_query_arg( $args, get_permalink() );
}

// Filtered lists repeat what the full list already shows: crawlers may follow
// them but should not index them as separate pages.
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_page_template( 'page-news-2.php' ) || is_page( 'news-2' ) ) {
		if ( array_filter( apt_news_filters() ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
	}
	return $robots;
} );

/** The filter bar. */
function apt_news_filters_html( $filters ) {
	$chip = function ( $label, $key, $value, $dot = '' ) use ( $filters ) {
		$on  = $filters[ $key ] === $value;
		$url = apt_news_filter_url( array_merge( $filters, array( $key => $value ) ) );
		return '<a class="nw-chip' . ( $on ? ' is-on' : '' ) . '" href="' . esc_url( $url ) . '"' . ( $on ? ' aria-current="true"' : '' ) . '>'
			. ( $dot ? '<i class="pillar-' . esc_attr( $dot ) . '" aria-hidden="true"></i>' : '' ) . esc_html( $label ) . '</a>';
	};
	$out  = '<form class="nw-filters" method="get" action="' . esc_url( get_permalink() ) . '" aria-label="' . esc_attr__( 'Filter news', 'akseki-vi' ) . '">';
	$out .= '<div class="nw-group" role="group" aria-labelledby="nw-l-pillar"><span class="nw-label" id="nw-l-pillar">' . esc_html__( 'Pillar', 'akseki-vi' ) . '</span>' . $chip( __( 'All', 'akseki-vi' ), 'pillar', '' );
	foreach ( apt_news_pillars() as $k => $p ) {
		$out .= $chip( $p[0], 'pillar', $k, $k );
	}
	$out .= '</div><div class="nw-group" role="group" aria-labelledby="nw-l-level"><span class="nw-label" id="nw-l-level">' . esc_html__( 'Level', 'akseki-vi' ) . '</span>' . $chip( __( 'All', 'akseki-vi' ), 'level', '' );
	foreach ( apt_news_levels() as $k => $l ) {
		$out .= $chip( $l[0], 'level', $k );
	}
	$out .= '</div><div class="nw-group"><label class="nw-label" for="nw-yr">' . esc_html__( 'Year', 'akseki-vi' ) . '</label>'
		. '<select class="nw-select" id="nw-yr" name="yr" onchange="this.form.submit()"><option value="">' . esc_html__( 'All years', 'akseki-vi' ) . '</option>';
	foreach ( apt_news_years() as $y ) {
		$out .= '<option value="' . (int) $y . '"' . selected( $filters['yr'], $y, false ) . '>' . (int) $y . '</option>';
	}
	$out .= '</select>';
	foreach ( array( 'pillar', 'level' ) as $k ) { // keep the other filters when the year changes
		if ( $filters[ $k ] ) {
			$out .= '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $filters[ $k ] ) . '">';
		}
	}
	$out .= '<noscript><button type="submit" class="nw-chip">' . esc_html__( 'Show', 'akseki-vi' ) . '</button></noscript></div>';
	if ( array_filter( $filters ) ) {
		$out .= '<a class="nw-clear" href="' . esc_url( get_permalink() ) . '">' . esc_html__( 'Clear filters', 'akseki-vi' ) . '</a>';
	}
	// On phones each row scrolls sideways: bring the selected chips into view.
	$out .= '<script>document.querySelectorAll(".nw-group .is-on").forEach(function(c){var g=c.parentNode;if(g.scrollWidth>g.clientWidth){g.scrollLeft=c.offsetLeft-g.offsetLeft-60;}});</script>';
	return $out . '</form>';
}

/**
 * One row of the news list for the current post in the loop: photo, tag
 * (Summit, or the pillar), level and topic, short name (linked; the link
 * covers the row), full title, place and date.
 */
function apt_news_row_html() {
	$id        = get_the_ID();
	$title     = get_the_title();
	$short     = akseki_get_shortname( $title, $id, 0 );
	$cat       = get_category( akseki_get_descat( wp_list_pluck( get_the_category(), 'term_id' ) ) );
	$level_id  = akseki_get_level_from_id( $id, 'post' );
	$level     = $level_id ? get_category( $level_id ) : null;
	$pillar_id = akseki_get_level_from_id( $id, 'post', 'pillar' );
	$pillar    = $pillar_id ? get_category( $pillar_id ) : null;

	if ( 41 === (int) $level_id ) {
		$tag = '<a class="apt-tag nw-tag-summit" href="' . esc_url( get_category_link( 41 ) ) . '">' . esc_html__( 'Summit', 'akseki-vi' ) . '</a>';
	} elseif ( $pillar && ! is_wp_error( $pillar ) && '' !== trim( $pillar->name ) ) {
		$tag = '<a class="apt-tag pillar-' . esc_attr( apt_pillar_key( $pillar->name ) ) . '" href="' . esc_url( get_category_link( $pillar->term_id ) ) . '">' . esc_html( $pillar->name ) . '</a>';
	} else {
		$tag = '';
	}
	$kicker = array();
	if ( 43 === (int) $level_id ) {
		$kicker[] = __( 'Ministerial', 'akseki-vi' );
	} elseif ( $level && ! is_wp_error( $level ) && 41 !== (int) $level_id ) {
		$kicker[] = $level->name;
	}
	if ( $cat && ! is_wp_error( $cat ) && '' !== trim( (string) $cat->description ) ) {
		$kicker[] = wp_strip_all_tags( $cat->description );
	}
	$kicker = $kicker ? '<span>' . esc_html( implode( ' · ', $kicker ) ) . '</span>' : '';

	$heading = has_tag( 'hasnocontent' ) ? $short : '<a class="card-link" href="' . esc_url( get_permalink() ) . '">' . $short . '</a>';
	$sub     = ( $short !== $title ) ? '<p class="nw-sub">' . $title . '</p>' : '';
	$image   = apt_post_image_html( $id, 'medium', '(max-width: 599px) 96px, 220px', array( 'class' => 'nw-image' ) );

	return '<article class="nw-row">' . $image . '<div class="nw-body">'
		. ( $tag || $kicker ? '<p class="nw-kicker">' . $tag . $kicker . '</p>' : '' )
		. '<h3 class="nw-title">' . $heading . '</h3>' . $sub
		. '<p class="news-card-date">' . akseki_get_the_meta( $id ) . '</p>'
		. '</div></article>';
}
