<?php
/**
 * Appearance → Home page content: the parts of the home page, the Contact
 * page and the footer that are not taken from posts.
 *
 * - About: the intro paragraph.
 * - Figures: the facts-and-figures cards in the About section.
 * - Cooperation cards: "Cooperation in Practice" (intro, cards, source line).
 * - Contact: the ASEAN Secretariat's address, phones, email and map.
 *
 * Everything is stored in one option, "apt_home_content", one key per tab.
 * A tab that was never saved, or whose data is missing or broken, shows the
 * original text (apt_home_content_defaults()), so the site never shows
 * blanks. "Restore original text" deletes a tab's saved data.
 *
 * Continuity: each figure has the date of its source edition ("As of") and
 * each card the date its text was last checked. Anything older than 12
 * months, or without a date, is listed at the top of the screen and under
 * Tools → Content check.
 *
 * Editors and administrators (edit_pages) can use the screen.
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** How old (in months) a figure or card may be before it is listed as due. */
const APT_HC_DUE_MONTHS = 12;

/** The icons a figure can have: key => [label, SVG inner markup]. */
function apt_hc_icons() {
	return array(
		'investment' => array( __( 'Investment', 'akseki-vi' ), '<path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/><path d="M9 10h6M9 14h6" stroke="currentColor" stroke-width="2"/>' ),
		'trade'      => array( __( 'Trade', 'akseki-vi' ), '<path d="M3 3h18v18H3z" stroke="currentColor" stroke-width="2"/><path d="M8 8h8M8 12h8M8 16h4" stroke="currentColor" stroke-width="2"/>' ),
		'people'     => array( __( 'People', 'akseki-vi' ), '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2"/>' ),
		'economy'    => array( __( 'Economy', 'akseki-vi' ), '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 6v6l4 4" stroke="currentColor" stroke-width="2"/>' ),
		'travel'     => array( __( 'Travel', 'akseki-vi' ), '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' ),
	);
}

/**
 * The original text (as written in the theme until this screen existed).
 * Sources, checked 26 November 2026:
 * - Trade and FDI: ASEAN Secretariat, "Overview of ASEAN Plus Three
 *   Cooperation" (as of 14 November 2025), para 8.
 * - Population, GDP, visitor arrivals: ASEANstats, ASEAN Statistical
 *   Highlights 2025 (2024 data). Its publication date is not filled in:
 *   add it on the screen.
 * - About intro: the site's own About page (/about-apt/).
 * - Cards: see the "Source of the text" field of each card.
 */
function apt_home_content_defaults() {
	$overview = 'https://aseanplusthree.asean.org/wp-content/uploads/2026/01/Overview-of-APT-Cooperation-as-of-14-November-2025.pdf';
	$ash      = 'https://www.aseanstats.org/publication/ash2025/';
	return array(
		'about'   => array(
			'text' => 'ASEAN Plus Three (APT) consists of the 11 ASEAN Member States and the People&rsquo;s Republic of China, Japan and the Republic of Korea. The APT Cooperation process began in December 1997 and since than has evolve as the main vehicle to promote East Asian Cooperation towards the long-term goal of building an East Asian Community, with ASEAN as the driving force. The APT has become one of the most comprehensive cooperation frameworks in the region, and APT cooperation continue to be broadened and deepened in a wide range of areas, including political-security, trade and investment, finance, energy, tourism, agriculture and forestry, environment, education, health, culture and arts, etc, among others. The APT also supports the implementation of the ASEAN Community Vision 2045.',
		),
		'figures' => array(
			array( 'value' => 'US$44.4 billion', 'text' => 'of foreign direct investment into ASEAN came from China, Japan and the Republic of Korea', 'note' => '2024, preliminary', 'source' => 'ASEAN Secretariat, APT Cooperation Overview (Nov 2025)', 'source_url' => $overview, 'as_of' => '2025-11-14', 'icon' => 'investment' ),
			array( 'value' => 'US$1.2 trillion', 'text' => 'two-way trade in goods between ASEAN and China, Japan and the Republic of Korea', 'note' => '2024, preliminary', 'source' => 'ASEAN Secretariat, APT Cooperation Overview (Nov 2025)', 'source_url' => $overview, 'as_of' => '2025-11-14', 'icon' => 'trade' ),
			array( 'value' => '2,277.5 million', 'text' => 'people live in the ASEAN Plus Three countries, 28.1% of the world&rsquo;s population', 'note' => '2024', 'source' => 'ASEANstats, Statistical Highlights 2025', 'source_url' => $ash, 'as_of' => '', 'icon' => 'people' ),
			array( 'value' => 'US$28.6 trillion', 'text' => 'combined GDP of the ASEAN Plus Three countries, 25.9% of the world economy', 'note' => '2024', 'source' => 'ASEANstats, Statistical Highlights 2025', 'source_url' => $ash, 'as_of' => '', 'icon' => 'economy' ),
			array( 'value' => '26.7%', 'text' => 'of the 127.1 million visitor arrivals in ASEAN came from China, Japan and the Republic of Korea', 'note' => '2024', 'source' => 'ASEANstats, Statistical Highlights 2025', 'source_url' => $ash, 'as_of' => '', 'icon' => 'travel' ),
		),
		'coop'    => array(
			'intro'   => 'The APT framework has become a cornerstone of East Asian regionalism, fostering cooperation in political-security, economic, and socio-cultural areas. The APT process has also developed into a full-fledged cooperation framework with 59 mechanisms coordinating various initiatives.',
			'cards'   => array(
				array(
					'name' => 'Chiang Mai Initiative Multilateralisation (CMIM)',
					'text' => 'With a size of <strong>US$240 billion</strong>, the CMIM is a multilateral currency swap arrangement among ASEAN+3 members, established under the agreement signed by the ASEAN+3 Finance Ministers and Central Bank Governors that came into effect on March 24, 2010.',
					'url' => '/about-cmim/', 'label' => 'About the CMIM',
					'ext' => 'https://amro-asia.org/about-amro/amro-and-the-cmim/', 'ext_label' => 'CMIM on amro-asia.org',
					'source' => '"About CMIM" page on this site (first sentence)', 'checked' => '2026-11-26',
				),
				array(
					'name' => 'ASEAN+3 Macroeconomic Research Office (AMRO)',
					'text' => 'Established in 2011 by ASEAN+3 Finance Ministers, the ASEAN+3 Macroeconomic Research Office (AMRO) is an international organization that aims to contribute to the macroeconomic and financial resilience and stability in the ASEAN+3 region.',
					'url' => '/about-amro-working-committee/', 'label' => 'About AMRO',
					'ext' => 'https://amro-asia.org/', 'ext_label' => 'amro-asia.org',
					'source' => 'AMRO, "Who we are" (first sentence)', 'checked' => '2026-11-26',
				),
				array(
					'name' => 'ASEAN Plus Three Emergency Rice Reserve (APTERR)',
					'text' => 'A key element of APT food security cooperation is the ASEAN Plus Three Emergency Rice Reserve (APTERR), established under the APTERR Agreement signed in October 2011 in Jakarta. APTERR aims to enhance food security, alleviate poverty, and eradicate malnutrition among members without disrupting normal trade.',
					'url' => '/category/statements-and-declarations/director-general-level/economic-cooperations-director-general-level/apterr/', 'label' => 'APTERR documents',
					'ext' => 'https://www.apterr.org/', 'ext_label' => 'apterr.org',
					'source' => 'ASEAN Secretariat, Overview of ASEAN Plus Three Cooperation (as of 14 November 2025), para 13', 'checked' => '2026-11-26',
				),
				array(
					'name' => 'Asian Bond Markets Initiative (ABMI)',
					'text' => 'The ABMI was launched in 2003 to develop local-currency denominated bond markets, and develop more accessible and well-functioning regional bond markets both for issuers and investors. In May 2026, the ASEAN+3 Finance Ministers and Central Bank Governors agreed &ldquo;to evolve the ABMI into the Asian Bond and Financial Markets Initiative (ABFMI) under the next roadmap&rdquo;.',
					'url' => '/wp-content/uploads/2026/05/20260502-Joint-Statement_29th-AFMGM3_final.pdf#page=4', 'label' => '29th AFMGM+3 Joint Statement (PDF)',
					'ext' => 'https://asianbondsonline.adb.org/abmi.php', 'ext_label' => 'AsianBondsOnline (ADB)',
					'source' => 'ASEAN Secretariat, Finance Cooperation (ASEAN+3), ABMI entry; 29th AFMGM+3 Joint Statement, 3 May 2026, para 14. Rename the card when the ABFMI is endorsed (expected 2027).', 'checked' => '2026-11-26',
				),
			),
			'sources' => 'Descriptions are quoted from the APT pages linked above, the ASEAN Secretariat&rsquo;s <a href="https://asean.org/our-communities/economic-community/finance-integration/finance-cooperation-asean-3/">Finance Cooperation (ASEAN+3)</a> page, AMRO&rsquo;s <a href="https://www.amro-asia.org/about-amro/who-we-are/">Who we are</a> page, the <a href="' . esc_url( home_url( '/wp-content/uploads/2026/05/20260502-Joint-Statement_29th-AFMGM3_final.pdf' ) ) . '">Joint Statement of the 29th ASEAN+3 Finance Ministers&rsquo; and Central Bank Governors&rsquo; Meeting</a> (3 May 2026) and the ASEAN Secretariat&rsquo;s <a href="' . $overview . '">Overview of ASEAN Plus Three Cooperation</a> (as of 14 November 2025).',
		),
		'contact' => array(
			'name'    => 'The ASEAN Secretariat',
			'address' => "70A Jalan Sisingamangaraja\nJakarta 12110, Indonesia",
			'phones'  => "(+6221) 7262991, 7243372\n(+6221) 7398234, 7243504",
			'email'   => 'public@asean.org',
			'map'     => 'https://maps.google.com/maps?q=asean%20secretariat%20jakarta&t=m&z=15&output=embed',
			'route'   => 'https://www.google.com/maps/dir/?api=1&destination=ASEAN+Secretariat%2C+70A+Jalan+Sisingamangaraja%2C+Jakarta',
		),
	);
}

/** One tab's content: what was saved, or the original text. */
function apt_home_content( $section ) {
	$defaults = apt_home_content_defaults();
	$saved    = get_option( 'apt_home_content', array() );
	$data     = ( is_array( $saved ) && isset( $saved[ $section ] ) && is_array( $saved[ $section ] ) ) ? $saved[ $section ] : array();
	if ( 'figures' === $section ) {
		// A list: the saved one replaces the original; never show none.
		$rows = array_values( array_filter( $data, 'is_array' ) );
		return $rows ? $rows : $defaults['figures'];
	}
	$out = array_merge( $defaults[ $section ], $data );
	if ( 'coop' === $section && empty( $out['cards'] ) ) {
		$out['cards'] = $defaults['coop']['cards'];
	}
	foreach ( $out as $k => $v ) {
		if ( is_string( $v ) && '' === trim( $v ) && isset( $defaults[ $section ][ $k ] ) ) {
			$out[ $k ] = $defaults[ $section ][ $k ];
		}
	}
	return $out;
}

/** A link field: a full URL as it is, a path on this site turned into a full URL. */
function apt_hc_link( $url ) {
	$url = trim( (string) $url );
	return preg_match( '#^https?://#i', $url ) ? $url : home_url( '/' . ltrim( $url, '/' ) );
}

/**
 * The ASEAN Secretariat's contact details, in the form the Contact page and
 * the footer use: phones as [display, tel, display, tel] per line.
 */
function apt_home_contact() {
	$c      = apt_home_content( 'contact' );
	$lines  = function ( $text ) {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) $text ) ), 'strlen' ) );
	};
	$phones = array();
	foreach ( $lines( $c['phones'] ) as $line ) {
		$parts = array_map( 'trim', explode( ',', $line, 2 ) );
		$first = $parts[0];
		$tel1  = '+' . ltrim( preg_replace( '/[^\d]/', '', $first ), '+' );
		$area  = preg_match( '/^\(?\+?([\d]+)\)/', $first, $m ) ? $m[1] : '';
		$sec   = isset( $parts[1] ) ? $parts[1] : '';
		$tel2  = '' !== $sec ? ( 0 === strpos( $sec, '+' ) || '' === $area ? '+' . ltrim( preg_replace( '/[^\d]/', '', $sec ), '+' ) : '+' . $area . preg_replace( '/[^\d]/', '', $sec ) ) : '';
		$phones[] = array( $first, $tel1, $sec, $tel2 );
	}
	return array(
		'name'    => $c['name'],
		'address' => $lines( $c['address'] ),
		'phones'  => $phones,
		'email'   => $c['email'],
		'map'     => $c['map'],
		'route'   => $c['route'],
	);
}

/* ---------- Due for a check ---------- */

/** Figures and cards that are due: [ tab, label, why ]. */
function apt_home_content_due() {
	$limit = strtotime( '-' . APT_HC_DUE_MONTHS . ' months', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- compared with local dates
	$due   = array();
	$check = function ( $date ) use ( $limit ) {
		if ( '' === (string) $date ) {
			return __( 'no date: add the date of the source', 'akseki-vi' );
		}
		$t = strtotime( $date );
		/* translators: %s: date */
		return ( $t && $t < $limit ) ? sprintf( __( 'from %s, over 12 months ago', 'akseki-vi' ), wp_date( 'j M Y', $t ) ) : '';
	};
	foreach ( apt_home_content( 'figures' ) as $f ) {
		$why = $check( isset( $f['as_of'] ) ? $f['as_of'] : '' );
		if ( $why ) {
			$due[] = array( 'figures', wp_strip_all_tags( html_entity_decode( $f['value'] . ' · ' . $f['text'], ENT_QUOTES, 'UTF-8' ) ), $why );
		}
	}
	foreach ( apt_home_content( 'coop' )['cards'] as $c ) {
		$why = $check( isset( $c['checked'] ) ? $c['checked'] : '' );
		if ( $why ) {
			$due[] = array( 'coop', $c['name'], $why );
		}
	}
	return $due;
}

/* ---------- The screen ---------- */

/** The screen's tabs. */
function apt_hc_tabs() {
	return array(
		'about'   => __( 'About', 'akseki-vi' ),
		'figures' => __( 'Figures', 'akseki-vi' ),
		'coop'    => __( 'Cooperation cards', 'akseki-vi' ),
		'contact' => __( 'Contact', 'akseki-vi' ),
	);
}

add_action( 'admin_menu', function () {
	$hook = add_theme_page( __( 'Home page content', 'akseki-vi' ), __( 'Home page content', 'akseki-vi' ), 'edit_pages', 'apt-home-content', 'apt_home_content_page' );
	add_action( 'load-' . $hook, 'apt_home_content_save' );
	add_action( 'admin_print_scripts-' . $hook, function () {
		wp_enqueue_script( 'jquery-ui-sortable' );
	} );
} );

/** Keep allowed inline markup only (bold, italics, links). */
function apt_hc_kses( $html ) {
	return trim( wp_kses( (string) $html, array( 'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(), 'br' => array(), 'a' => array( 'href' => true, 'title' => true ) ) ) );
}

/** A URL or a path on this site. */
function apt_hc_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( '/' === $url[0] ) {
		return preg_replace( '/[\s"\'<>]/', '', $url );
	}
	return esc_url_raw( $url, array( 'http', 'https' ) );
}

/** A Y-m-d date, or ''. */
function apt_hc_date( $date ) {
	$date = trim( (string) $date );
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && strtotime( $date ) ? $date : '';
}

/** Clean one tab's posted data. */
function apt_hc_sanitize( $tab, $in ) {
	$in = is_array( $in ) ? $in : array();
	$t  = function ( $k, $a = null ) use ( $in ) {
		$a = null === $a ? $in : $a;
		return isset( $a[ $k ] ) ? sanitize_text_field( $a[ $k ] ) : '';
	};
	switch ( $tab ) {
		case 'about':
			return array( 'text' => apt_hc_kses( isset( $in['text'] ) ? $in['text'] : '' ) );
		case 'figures':
			$out = array();
			foreach ( (array) ( isset( $in['rows'] ) ? $in['rows'] : array() ) as $r ) {
				if ( ! is_array( $r ) || '' === trim( (string) ( isset( $r['value'] ) ? $r['value'] : '' ) ) ) {
					continue; // a row without a value is dropped
				}
				$out[] = array(
					'value'      => $t( 'value', $r ),
					'text'       => apt_hc_kses( isset( $r['text'] ) ? $r['text'] : '' ),
					'note'       => $t( 'note', $r ),
					'source'     => $t( 'source', $r ),
					'source_url' => apt_hc_url( isset( $r['source_url'] ) ? $r['source_url'] : '' ),
					'as_of'      => apt_hc_date( isset( $r['as_of'] ) ? $r['as_of'] : '' ),
					'icon'       => isset( $r['icon'], apt_hc_icons()[ $r['icon'] ] ) ? $r['icon'] : 'investment',
				);
			}
			return $out;
		case 'coop':
			$cards = array();
			foreach ( (array) ( isset( $in['cards'] ) ? $in['cards'] : array() ) as $r ) {
				if ( ! is_array( $r ) || '' === trim( (string) ( isset( $r['name'] ) ? $r['name'] : '' ) ) ) {
					continue;
				}
				$cards[] = array(
					'name'      => $t( 'name', $r ),
					'text'      => apt_hc_kses( isset( $r['text'] ) ? $r['text'] : '' ),
					'url'       => apt_hc_url( isset( $r['url'] ) ? $r['url'] : '' ),
					'label'     => $t( 'label', $r ),
					'ext'       => apt_hc_url( isset( $r['ext'] ) ? $r['ext'] : '' ),
					'ext_label' => $t( 'ext_label', $r ),
					'source'    => $t( 'source', $r ),
					'checked'   => apt_hc_date( isset( $r['checked'] ) ? $r['checked'] : '' ),
				);
			}
			return array(
				'intro'   => apt_hc_kses( isset( $in['intro'] ) ? $in['intro'] : '' ),
				'cards'   => $cards,
				'sources' => apt_hc_kses( isset( $in['sources'] ) ? $in['sources'] : '' ),
			);
		case 'contact':
			return array(
				'name'    => $t( 'name' ),
				'address' => sanitize_textarea_field( isset( $in['address'] ) ? $in['address'] : '' ),
				'phones'  => sanitize_textarea_field( isset( $in['phones'] ) ? $in['phones'] : '' ),
				'email'   => sanitize_email( isset( $in['email'] ) ? $in['email'] : '' ),
				'map'     => apt_hc_url( isset( $in['map'] ) ? $in['map'] : '' ),
				'route'   => apt_hc_url( isset( $in['route'] ) ? $in['route'] : '' ),
			);
	}
	return array();
}

/** Save or restore a tab (runs before the screen is shown). */
function apt_home_content_save() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || ! isset( $_POST['apt_hc_tab'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return;
	}
	check_admin_referer( 'apt_home_content' );
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'You are not allowed to change this.', 'akseki-vi' ) );
	}
	$tab = sanitize_key( wp_unslash( $_POST['apt_hc_tab'] ) );
	if ( ! isset( apt_hc_tabs()[ $tab ] ) ) {
		return;
	}
	$all = get_option( 'apt_home_content', array() );
	$all = is_array( $all ) ? $all : array();
	if ( isset( $_POST['apt_hc_restore'] ) ) {
		unset( $all[ $tab ] );
		$msg = 'restored';
	} else {
		$all[ $tab ] = apt_hc_sanitize( $tab, isset( $_POST['apt_hc'] ) ? wp_unslash( $_POST['apt_hc'] ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in apt_hc_sanitize()
		$msg         = 'saved';
	}
	update_option( 'apt_home_content', $all, false );
	update_option( 'apt_home_content_saved', array( 'time' => time(), 'user' => get_current_user_id() ), false );
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache(); // WP Super Cache: the home page and footer show this content
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'apt-home-content', 'tab' => $tab, 'msg' => $msg ), admin_url( 'themes.php' ) ) );
	exit;
}

/** One field. */
function apt_hc_field( $name, $label, $value, $args = array() ) {
	$args = array_merge( array( 'type' => 'text', 'span' => 6, 'desc' => '', 'rows' => 3 ), $args );
	$id   = 'f' . substr( md5( $name ), 0, 10 );
	$out  = '<div class="apt-hc-f apt-hc-s' . (int) $args['span'] . '"><label for="' . $id . '">' . esc_html( $label ) . '</label>';
	if ( 'textarea' === $args['type'] ) {
		$out .= '<textarea id="' . $id . '" name="' . esc_attr( $name ) . '" rows="' . (int) $args['rows'] . '">' . esc_textarea( $value ) . '</textarea>';
	} elseif ( 'select' === $args['type'] ) {
		$out .= '<select id="' . $id . '" name="' . esc_attr( $name ) . '">';
		foreach ( $args['options'] as $k => $l ) {
			$out .= '<option value="' . esc_attr( $k ) . '"' . selected( $value, $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		$out .= '</select>';
	} else {
		$out .= '<input id="' . $id . '" type="' . esc_attr( $args['type'] ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
	}
	if ( $args['desc'] ) {
		$out .= '<span class="description">' . esc_html( $args['desc'] ) . '</span>';
	}
	return $out . '</div>';
}

/** The badge next to a row's title. */
function apt_hc_badge( $date, $word ) {
	if ( '' === (string) $date ) {
		return '<span class="apt-hc-badge is-due">' . esc_html__( 'no date', 'akseki-vi' ) . '</span>';
	}
	$t   = strtotime( $date );
	$old = $t < strtotime( '-' . APT_HC_DUE_MONTHS . ' months' );
	/* translators: 1: "as of" or "checked", 2: month and year */
	return '<span class="apt-hc-badge' . ( $old ? ' is-due' : '' ) . '">' . esc_html( $old ? __( 'check: over 12 months', 'akseki-vi' ) : sprintf( '%1$s %2$s', $word, wp_date( 'M Y', $t ) ) ) . '</span>';
}

/** One figure row (index "__i__" for the template of a new row). */
function apt_hc_figure_row( $i, $f ) {
	$f     = array_merge( array( 'value' => '', 'text' => '', 'note' => '', 'source' => '', 'source_url' => '', 'as_of' => '', 'icon' => 'investment' ), (array) $f );
	$n     = 'apt_hc[rows][' . $i . ']';
	$icons = array_map( function ( $x ) { return $x[0]; }, apt_hc_icons() );
	$title = '' !== $f['value'] ? $f['value'] . ' · ' . wp_strip_all_tags( html_entity_decode( $f['text'], ENT_QUOTES, 'UTF-8' ) ) : __( 'New figure', 'akseki-vi' );
	return '<details class="apt-hc-row"' . ( '__i__' === $i ? ' open' : '' ) . '><summary><span class="apt-hc-drag" title="' . esc_attr__( 'Drag to reorder', 'akseki-vi' ) . '" aria-hidden="true">⋮⋮</span><span class="apt-hc-t">' . esc_html( wp_html_excerpt( $title, 110, '…' ) ) . '</span>'
		. apt_hc_badge( $f['as_of'], __( 'as of', 'akseki-vi' ) ) . '<button type="button" class="button-link apt-hc-remove">' . esc_html__( 'Remove', 'akseki-vi' ) . '</button></summary><div class="apt-hc-grid">'
		. apt_hc_field( $n . '[value]', __( 'Value', 'akseki-vi' ), $f['value'], array( 'span' => 2, 'desc' => __( 'Exactly as published, e.g. US$44.4 billion.', 'akseki-vi' ) ) )
		. apt_hc_field( $n . '[note]', __( 'Year / note', 'akseki-vi' ), $f['note'], array( 'span' => 2, 'desc' => __( 'Shown in italics after the text.', 'akseki-vi' ) ) )
		. apt_hc_field( $n . '[as_of]', __( 'As of', 'akseki-vi' ), $f['as_of'], array( 'span' => 2, 'type' => 'date', 'desc' => __( 'Date of the source edition.', 'akseki-vi' ) ) )
		. apt_hc_field( $n . '[text]', __( 'Text', 'akseki-vi' ), $f['text'], array( 'desc' => __( 'Quote the source\'s wording. Write official names in full.', 'akseki-vi' ) ) )
		. apt_hc_field( $n . '[source]', __( 'Source', 'akseki-vi' ), $f['source'], array( 'span' => 3 ) )
		. apt_hc_field( $n . '[source_url]', __( 'Source link', 'akseki-vi' ), $f['source_url'], array( 'span' => 3 ) )
		. apt_hc_field( $n . '[icon]', __( 'Icon', 'akseki-vi' ), $f['icon'], array( 'span' => 2, 'type' => 'select', 'options' => $icons ) )
		. '</div></details>';
}

/** One card row. */
function apt_hc_card_row( $i, $c ) {
	$c = array_merge( array( 'name' => '', 'text' => '', 'url' => '', 'label' => '', 'ext' => '', 'ext_label' => '', 'source' => '', 'checked' => '' ), (array) $c );
	$n = 'apt_hc[cards][' . $i . ']';
	return '<details class="apt-hc-row"' . ( '__i__' === $i ? ' open' : '' ) . '><summary><span class="apt-hc-drag" title="' . esc_attr__( 'Drag to reorder', 'akseki-vi' ) . '" aria-hidden="true">⋮⋮</span><span class="apt-hc-t">' . esc_html( '' !== $c['name'] ? $c['name'] : __( 'New card', 'akseki-vi' ) ) . '</span>'
		. apt_hc_badge( $c['checked'], __( 'checked', 'akseki-vi' ) ) . '<button type="button" class="button-link apt-hc-remove">' . esc_html__( 'Remove', 'akseki-vi' ) . '</button></summary><div class="apt-hc-grid">'
		. apt_hc_field( $n . '[name]', __( 'Name', 'akseki-vi' ), $c['name'], array( 'desc' => __( 'The official name in full, with its abbreviation.', 'akseki-vi' ) ) )
		. apt_hc_field( $n . '[text]', __( 'Text', 'akseki-vi' ), $c['text'], array( 'type' => 'textarea', 'rows' => 4, 'desc' => __( 'Quote the source word for word. For bold, put the words between <strong> and </strong>.', 'akseki-vi' ) ) )
		. apt_hc_field( $n . '[url]', __( 'Link on this site', 'akseki-vi' ), $c['url'], array( 'span' => 3, 'desc' => __( 'A path such as /about-cmim/, or a full address.', 'akseki-vi' ) ) )
		. apt_hc_field( $n . '[label]', __( 'Link label', 'akseki-vi' ), $c['label'], array( 'span' => 3 ) )
		. apt_hc_field( $n . '[ext]', __( 'External link', 'akseki-vi' ), $c['ext'], array( 'span' => 3 ) )
		. apt_hc_field( $n . '[ext_label]', __( 'External label', 'akseki-vi' ), $c['ext_label'], array( 'span' => 3 ) )
		. apt_hc_field( $n . '[source]', __( 'Source of the text', 'akseki-vi' ), $c['source'], array( 'span' => 4, 'desc' => __( 'For editors only: where the text is quoted from. Not shown on the site.', 'akseki-vi' ) ) )
		. apt_hc_field( $n . '[checked]', __( 'Last checked', 'akseki-vi' ), $c['checked'], array( 'span' => 2, 'type' => 'date', 'desc' => __( 'When the text was last compared with the source.', 'akseki-vi' ) ) )
		. '</div></details>';
}

/** The screen. */
function apt_home_content_page() {
	$tabs = apt_hc_tabs();
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'figures'; // phpcs:ignore WordPress.Security.NonceVerification
	$tab  = isset( $tabs[ $tab ] ) ? $tab : 'figures';
	$msg  = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$data = apt_home_content( $tab );
	$due  = apt_home_content_due();
	$last = get_option( 'apt_home_content_saved' );
	$base = admin_url( 'themes.php?page=apt-home-content' );
	?>
	<div class="wrap apt-hc">
		<h1><?php esc_html_e( 'Home page content', 'akseki-vi' ); ?></h1>
		<p class="apt-hc-lead"><?php esc_html_e( 'The parts of the home page, the Contact page and the footer that are not taken from posts. Changes show on the site as soon as you save.', 'akseki-vi' ); ?></p>

		<?php if ( 'saved' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved. Check the home page to see the change.', 'akseki-vi' ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php esc_html_e( 'Open the home page', 'akseki-vi' ); ?></a></p></div>
		<?php elseif ( 'restored' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The original text of this tab is back.', 'akseki-vi' ); ?></p></div>
		<?php endif; ?>

		<?php if ( $due ) : ?>
			<div class="notice notice-warning"><p><strong><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d item is due for a check:', '%d items are due for a check:', count( $due ), 'akseki-vi' ), count( $due ) ) ); ?></strong></p>
				<ul class="apt-hc-due">
				<?php foreach ( $due as $d ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( 'tab', $d[0], $base ) ); ?>"><?php echo esc_html( $tabs[ $d[0] ] ); ?></a>: <?php echo esc_html( wp_html_excerpt( $d[1], 90, '…' ) ); ?> (<?php echo esc_html( $d[2] ); ?>)</li>
				<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $k => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'tab', $k, $base ) ); ?>" class="nav-tab<?php echo $k === $tab ? ' nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<form method="post" class="apt-hc-cols">
			<?php wp_nonce_field( 'apt_home_content' ); ?>
			<input type="hidden" name="apt_hc_tab" value="<?php echo esc_attr( $tab ); ?>">
			<div class="apt-hc-main">
			<?php if ( 'about' === $tab ) : ?>
				<div class="postbox"><h2 class="hndle"><?php esc_html_e( 'About section: introduction', 'akseki-vi' ); ?></h2><div class="inside">
					<?php echo apt_hc_field( 'apt_hc[text]', __( 'Text', 'akseki-vi' ), $data['text'], array( 'type' => 'textarea', 'rows' => 9, 'desc' => __( 'The text of the About page (/about-apt/), word for word.', 'akseki-vi' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?>
				</div></div>

			<?php elseif ( 'figures' === $tab ) : ?>
				<div class="postbox"><h2 class="hndle"><?php esc_html_e( 'Figures', 'akseki-vi' ); ?> <span class="apt-hc-muted"><?php esc_html_e( 'shown in the About section, in this order', 'akseki-vi' ); ?></span></h2><div class="inside">
					<div class="apt-hc-rows">
						<?php foreach ( array_values( $data ) as $i => $f ) { echo apt_hc_figure_row( $i, $f ); } // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?>
					</div>
					<template class="apt-hc-template"><?php echo apt_hc_figure_row( '__i__', array() ); // phpcs:ignore WordPress.Security.EscapeOutput ?></template>
					<button type="button" class="button apt-hc-add">+ <?php esc_html_e( 'Add a figure', 'akseki-vi' ); ?></button>
				</div></div>

			<?php elseif ( 'coop' === $tab ) : ?>
				<div class="postbox"><h2 class="hndle"><?php esc_html_e( 'Section introduction', 'akseki-vi' ); ?></h2><div class="inside">
					<?php echo apt_hc_field( 'apt_hc[intro]', __( 'Text', 'akseki-vi' ), $data['intro'], array( 'type' => 'textarea', 'desc' => __( 'Source: Overview of ASEAN Plus Three Cooperation, para 3. Update the number of mechanisms with each new Overview.', 'akseki-vi' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div></div>
				<div class="postbox"><h2 class="hndle"><?php esc_html_e( 'Cards', 'akseki-vi' ); ?> <span class="apt-hc-muted"><?php esc_html_e( 'drag ⋮⋮ to reorder', 'akseki-vi' ); ?></span></h2><div class="inside">
					<div class="apt-hc-rows">
						<?php foreach ( array_values( $data['cards'] ) as $i => $c ) { echo apt_hc_card_row( $i, $c ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
					<template class="apt-hc-template"><?php echo apt_hc_card_row( '__i__', array() ); // phpcs:ignore WordPress.Security.EscapeOutput ?></template>
					<button type="button" class="button apt-hc-add">+ <?php esc_html_e( 'Add a card', 'akseki-vi' ); ?></button>
				</div></div>
				<div class="postbox"><h2 class="hndle"><?php esc_html_e( 'Source line under the cards', 'akseki-vi' ); ?></h2><div class="inside">
					<?php echo apt_hc_field( 'apt_hc[sources]', __( 'Text', 'akseki-vi' ), $data['sources'], array( 'type' => 'textarea', 'rows' => 5, 'desc' => __( 'Lists where the card texts come from. Links are written as <a href="address">name</a>. Update it when a card\'s source changes.', 'akseki-vi' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div></div>

			<?php else : ?>
				<div class="postbox"><h2 class="hndle"><?php esc_html_e( 'The ASEAN Secretariat', 'akseki-vi' ); ?> <span class="apt-hc-muted"><?php esc_html_e( 'Contact page and footer', 'akseki-vi' ); ?></span></h2><div class="inside"><div class="apt-hc-grid">
					<?php
					// phpcs:disable WordPress.Security.EscapeOutput -- escaped inside apt_hc_field()
					echo apt_hc_field( 'apt_hc[name]', __( 'Name', 'akseki-vi' ), $data['name'], array( 'span' => 3 ) );
					echo apt_hc_field( 'apt_hc[email]', __( 'Email', 'akseki-vi' ), $data['email'], array( 'span' => 3, 'type' => 'email' ) );
					echo apt_hc_field( 'apt_hc[address]', __( 'Address', 'akseki-vi' ), $data['address'], array( 'span' => 3, 'type' => 'textarea', 'desc' => __( 'One line per line of the address.', 'akseki-vi' ) ) );
					echo apt_hc_field( 'apt_hc[phones]', __( 'Phone numbers', 'akseki-vi' ), $data['phones'], array( 'span' => 3, 'type' => 'textarea', 'desc' => __( 'One line each, as printed: the area code and number, a comma, the second number. E.g. (+6221) 7262991, 7243372', 'akseki-vi' ) ) );
					echo apt_hc_field( 'apt_hc[map]', __( 'Map (embed address)', 'akseki-vi' ), $data['map'], array( 'desc' => __( 'Google Maps: Share → Embed a map → the address inside src="…".', 'akseki-vi' ) ) );
					echo apt_hc_field( 'apt_hc[route]', __( '"Get directions" link', 'akseki-vi' ), $data['route'] );
					// phpcs:enable
					?>
				</div></div></div>
			<?php endif; ?>
			</div>

			<div class="apt-hc-side">
				<div class="postbox"><h2 class="hndle"><?php esc_html_e( 'Save', 'akseki-vi' ); ?></h2><div class="inside">
					<?php if ( is_array( $last ) && ! empty( $last['time'] ) ) : ?>
						<?php $u = get_userdata( (int) $last['user'] ); ?>
						<p class="apt-hc-muted"><?php echo esc_html( sprintf( /* translators: 1: date, 2: user */ __( 'Last saved %1$s by %2$s.', 'akseki-vi' ), wp_date( 'j M Y, H:i', $last['time'] ), $u ? $u->display_name : '?' ) ); ?></p>
					<?php endif; ?>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'akseki-vi' ); ?></button></p>
					<p><button type="submit" name="apt_hc_restore" value="1" class="button-link apt-hc-danger" onclick="return confirm('<?php echo esc_js( __( 'Put back the original text of this tab? Your changes to this tab will be lost.', 'akseki-vi' ) ); ?>')"><?php esc_html_e( 'Restore original text', 'akseki-vi' ); ?></button></p>
				</div></div>
				<div class="postbox"><h2 class="hndle"><?php esc_html_e( 'How to update', 'akseki-vi' ); ?></h2><div class="inside">
					<p><strong><?php esc_html_e( 'When a new source edition comes out', 'akseki-vi' ); ?></strong> <?php esc_html_e( '(Overview of ASEAN Plus Three Cooperation, ASEAN Statistical Highlights):', 'akseki-vi' ); ?></p>
					<ol>
						<li><?php esc_html_e( 'Change the value, text and year exactly as published.', 'akseki-vi' ); ?></li>
						<li><?php esc_html_e( 'Update the source, its link and the "As of" date.', 'akseki-vi' ); ?></li>
						<li><?php esc_html_e( 'Save, then check the home page.', 'akseki-vi' ); ?></li>
					</ol>
					<p><?php esc_html_e( 'Cards: when you compare a card\'s text with its source, set "Last checked" to today.', 'akseki-vi' ); ?></p>
					<p><?php esc_html_e( 'Anything older than 12 months, or without a date, is listed at the top of this screen and under Tools → Content check.', 'akseki-vi' ); ?></p>
					<p><?php esc_html_e( 'Write "ASEAN Plus Three" and "the Republic of Korea" in full, and quote sources word for word.', 'akseki-vi' ); ?></p>
				</div></div>
			</div>
		</form>
	</div>
	<style>
		.apt-hc-lead { max-width: 860px; font-size: 14px; }
		.apt-hc-cols { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 20px; max-width: 1240px; margin-top: 16px; }
		@media (max-width: 1100px) { .apt-hc-cols { grid-template-columns: 1fr; } }
		.apt-hc .postbox .hndle { font-size: 14px; padding: 10px 12px; margin: 0; border-bottom: 1px solid #c3c4c7; }
		.apt-hc-muted { color: #646970; font-weight: 400; }
		.apt-hc-due { margin: 0 0 8px 18px; list-style: disc; }
		.apt-hc-row { border: 1px solid #dcdcde; border-radius: 4px; margin: 0 0 10px; background: #fff; }
		.apt-hc-row > summary { display: flex; align-items: center; gap: 10px; padding: 8px 10px; background: #f6f7f7; cursor: pointer; font-weight: 600; list-style: none; }
		.apt-hc-row > summary::-webkit-details-marker { display: none; }
		.apt-hc-row[open] > summary { border-bottom: 1px solid #dcdcde; }
		.apt-hc-drag { color: #8c8f94; cursor: move; letter-spacing: -2px; }
		.apt-hc-t { flex: 1; min-width: 0; }
		.apt-hc-badge { font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 10px; background: #edfaef; color: #00450c; border: 1px solid #68de7c; white-space: nowrap; }
		.apt-hc-badge.is-due { background: #fcf0d1; color: #6e4b00; border-color: #f0c33c; }
		.apt-hc-remove, .apt-hc-danger { color: #b32d2e !important; }
		.apt-hc-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px 14px; padding: 12px; }
		.apt-hc .inside > .apt-hc-f { padding: 0; }
		.apt-hc-f { display: flex; flex-direction: column; gap: 4px; grid-column: span 6; }
		.apt-hc-f label { font-weight: 600; }
		.apt-hc-f input, .apt-hc-f textarea, .apt-hc-f select { width: 100%; max-width: none; }
		.apt-hc-s2 { grid-column: span 2; } .apt-hc-s3 { grid-column: span 3; } .apt-hc-s4 { grid-column: span 4; }
		@media (max-width: 782px) { .apt-hc-grid > .apt-hc-f { grid-column: span 6; } }
		.apt-hc-placeholder { border: 2px dashed #c3c4c7; border-radius: 4px; margin: 0 0 10px; height: 40px; }
	</style>
	<script>
	jQuery( function ( $ ) {
		var n = 1000;
		$( '.apt-hc-add' ).on( 'click', function () {
			var box = $( this ).closest( '.inside' );
			var html = box.find( '.apt-hc-template' ).html().replace( /__i__/g, 'n' + ( n++ ) );
			box.find( '.apt-hc-rows' ).append( html ).children().last().find( 'input, textarea' ).first().trigger( 'focus' );
		} );
		$( document ).on( 'click', '.apt-hc-remove', function ( e ) {
			e.preventDefault();
			if ( window.confirm( <?php echo wp_json_encode( __( 'Remove this row? It is gone once you save.', 'akseki-vi' ) ); ?> ) ) {
				$( this ).closest( '.apt-hc-row' ).remove();
			}
		} );
		if ( $.fn.sortable ) {
			$( '.apt-hc-rows' ).sortable( { handle: '.apt-hc-drag', items: '> .apt-hc-row', placeholder: 'apt-hc-placeholder', axis: 'y' } );
		}
		// Unsaved changes: warn before leaving.
		var dirty = false;
		$( '.apt-hc-cols' ).on( 'input change', function () { dirty = true; } ).on( 'submit', function () { dirty = false; } );
		$( window ).on( 'beforeunload', function () { return dirty ? true : undefined; } );
	} );
	</script>
	<?php
}
