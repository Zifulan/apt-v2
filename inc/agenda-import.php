<?php
/**
 * Posts → Import agenda: turn the ASEAN notional calendar (Excel) into
 * agenda entries.
 *
 * 1. Upload the calendar (.xlsx; .csv also works). The visible sheet is read
 *    (the file keeps every revision as a hidden sheet); the header row is
 *    found by its "Meeting" and "Date" columns.
 * 2. Only ASEAN Plus Three meetings are kept ("ASEAN+3", "ASEAN Plus
 *    Three", "Plus 3", "APT", "…+3").
 * 3. A preview shows each meeting with the date read from the calendar
 *    ("5-9 October 2026" → 5 October; "March 2026" → month only; "Q4" →
 *    year only; "TBC" or empty → date to be decided), the meeting series
 *    (guessed from the categories, and remembered when corrected), the
 *    city, and its status: new, already in the agenda, date changed,
 *    already published, past or cancelled.
 * 4. Ticked rows become "Pending Review" posts, exactly like agenda entries
 *    made by hand: the meeting date as the post date, the series as the
 *    category, the city as the Location and an SC_ tag when the date is
 *    not exact. "Date changed" rows update the existing entry instead.
 *
 * Nothing is published: the agenda shows pending posts dated today or later.
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Reading the file ---------- */

/**
 * The rows of an .xlsx file's visible sheet (the one with most rows if
 * several are visible), as arrays of cell values by column letter.
 * Returns [ 'title' => sheet name, 'rows' => [...] ] or a WP_Error.
 */
function apt_ai_read_xlsx( $file ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'zip', __( 'This server cannot open .xlsx files. Save the calendar as CSV (File → Save As → CSV UTF-8) and upload that.', 'akseki-vi' ) );
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $file ) ) {
		return new WP_Error( 'open', __( 'The file could not be opened. Is it an Excel (.xlsx) file?', 'akseki-vi' ) );
	}
	$xml = function ( $name ) use ( $zip ) {
		$s = $zip->getFromName( $name );
		if ( false === $s ) {
			return null;
		}
		$prev = libxml_use_internal_errors( true );
		$x    = simplexml_load_string( $s, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE );
		libxml_use_internal_errors( $prev );
		return $x ? $x : null;
	};
	$wb   = $xml( 'xl/workbook.xml' );
	$rels = $xml( 'xl/_rels/workbook.xml.rels' );
	if ( ! $wb || ! $rels ) {
		$zip->close();
		return new WP_Error( 'format', __( 'This does not look like an Excel workbook.', 'akseki-vi' ) );
	}
	$targets = array();
	foreach ( $rels->Relationship as $r ) {
		$targets[ (string) $r['Id'] ] = ltrim( preg_replace( '#^/?xl/#', '', (string) $r['Target'] ), '/' );
	}
	$strings = array();
	$ss      = $xml( 'xl/sharedStrings.xml' );
	if ( $ss ) {
		foreach ( $ss->si as $si ) {
			$t = '';
			if ( isset( $si->t ) ) {
				$t = (string) $si->t;
			}
			foreach ( $si->r as $run ) {
				$t .= (string) $run->t;
			}
			$strings[] = $t;
		}
	}
	$best = null;
	foreach ( $wb->sheets->sheet as $sh ) {
		$state = (string) $sh['state'];
		if ( 'hidden' === $state || 'veryHidden' === $state ) {
			continue;
		}
		$rid  = (string) $sh->attributes( 'http://schemas.openxmlformats.org/officeDocument/2006/relationships' )->id;
		$path = isset( $targets[ $rid ] ) ? 'xl/' . $targets[ $rid ] : '';
		$sx   = $path ? $xml( $path ) : null;
		if ( ! $sx ) {
			continue;
		}
		$rows = array();
		foreach ( $sx->sheetData->row as $row ) {
			$cells = array();
			foreach ( $row->c as $c ) {
				$col = preg_replace( '/\d+/', '', (string) $c['r'] );
				$t   = (string) $c['t'];
				if ( 's' === $t ) {
					$v = isset( $strings[ (int) $c->v ] ) ? $strings[ (int) $c->v ] : '';
				} elseif ( 'inlineStr' === $t ) {
					$v = (string) $c->is->t;
				} else {
					$v = (string) $c->v;
				}
				$cells[ $col ] = $v;
			}
			$rows[] = $cells;
		}
		if ( ! $best || count( $rows ) > count( $best['rows'] ) ) {
			$best = array( 'title' => (string) $sh['name'], 'rows' => $rows );
		}
	}
	$zip->close();
	return $best ? $best : new WP_Error( 'empty', __( 'No visible sheet with data was found.', 'akseki-vi' ) );
}

/** The rows of a CSV file, keyed by column letter like the .xlsx reader. */
function apt_ai_read_csv( $file ) {
	$h = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $h ) {
		return new WP_Error( 'open', __( 'The file could not be opened.', 'akseki-vi' ) );
	}
	$rows = array();
	while ( false !== ( $r = fgetcsv( $h, 0, ',', '"', '\\' ) ) ) {
		$cells = array();
		foreach ( $r as $i => $v ) {
			$cells[ apt_ai_col( $i ) ] = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $v );
		}
		$rows[] = $cells;
	}
	fclose( $h ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return array( 'title' => '', 'rows' => $rows );
}

/** 0 → A, 25 → Z, 26 → AA. */
function apt_ai_col( $i ) {
	$s = '';
	for ( $i = (int) $i + 1; $i > 0; $i = intdiv( $i - 1, 26 ) ) {
		$s = chr( 65 + ( $i - 1 ) % 26 ) . $s;
	}
	return $s;
}

/** Whether a meeting title is an ASEAN Plus Three meeting. */
function apt_ai_is_apt( $title ) {
	return (bool) preg_match( '/ASEAN\s*\+\s*3|ASEAN\s+Plus\s+Three|Plus\s*(Three|3)\b|\bAPT\b|\+\s*3\b/i', (string) $title );
}

/**
 * The date of a calendar cell: [ 'Y-m-d', mode ], mode '' (exact),
 * 'SC_month', 'SC_year' or 'SC_nodate'.
 */
function apt_ai_parse_date( $v, $year ) {
	$v = trim( preg_replace( '/\s+/u', ' ', str_replace( array( "\xC2\xA0", '–', '—' ), array( ' ', '-', '-' ), (string) $v ) ) );
	if ( '' === $v || preg_match( '/^(tbc|tbd|tba|n\/?a|-)$/i', $v ) ) {
		return array( $year . '-12-31', 'SC_nodate' );
	}
	if ( is_numeric( $v ) && $v > 20000 && $v < 80000 ) { // Excel date number
		return array( gmdate( 'Y-m-d', (int) round( ( $v - 25569 ) * 86400 ) ), '' );
	}
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $v, $m ) ) {
		return array( "$m[1]-$m[2]-$m[3]", '' );
	}
	$months = '(jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)[a-z]*\.?';
	// "5-9 October 2026", "10 July 2026", "29 October - 2 November 2026"
	if ( preg_match( '/\b(\d{1,2})(?:\s*-\s*\d{1,2})?\s+' . $months . '(?:.*?\b(20\d\d)\b)?/i', $v, $m ) ) {
		$y = ! empty( $m[3] ) ? $m[3] : $year;
		$t = strtotime( $m[1] . ' ' . $m[2] . ' ' . $y );
		if ( $t ) {
			return array( gmdate( 'Y-m-d', $t ), '' );
		}
	}
	// "October 29-30, 2026"
	if ( preg_match( '/\b' . $months . '\s+(\d{1,2})\b(?:.*?\b(20\d\d)\b)?/i', $v, $m ) ) {
		$y = ! empty( $m[3] ) ? $m[3] : $year;
		$t = strtotime( $m[2] . ' ' . $m[1] . ' ' . $y );
		if ( $t ) {
			return array( gmdate( 'Y-m-d', $t ), '' );
		}
	}
	// "March 2026", "January (3 days)"
	if ( preg_match( '/\b' . $months . '(?:.*?\b(20\d\d)\b)?/i', $v, $m ) ) {
		$y = ! empty( $m[2] ) ? $m[2] : $year;
		$t = strtotime( '1 ' . $m[1] . ' ' . $y );
		if ( $t ) {
			return array( gmdate( 'Y-m-d', $t ), 'SC_month' );
		}
	}
	// "Q4", "Q4 2026"
	if ( preg_match( '/\bQ([1-4])\b(?:.*?\b(20\d\d)\b)?/i', $v, $m ) ) {
		$y = ! empty( $m[2] ) ? $m[2] : $year;
		return array( sprintf( '%d-%02d-01', $y, ( (int) $m[1] - 1 ) * 3 + 1 ), 'SC_year' );
	}
	if ( preg_match( '/\b(20\d\d)\b/', $v, $m ) ) {
		return array( $m[1] . '-01-01', 'SC_year' );
	}
	return array( $year . '-12-31', 'SC_nodate' );
}

/** The city for the Location field: "Seoul, ROK" → Seoul. */
function apt_ai_city( $venue ) {
	$v = trim( preg_replace( '/\s+/u', ' ', (string) $venue ) );
	if ( '' === $v || preg_match( '/^(tbc|tbd|tba|none|n\/?a|-)$/i', $v ) ) {
		return '';
	}
	if ( preg_match( '/virtual|video ?conference|online/i', $v ) ) {
		return 'Online Conference';
	}
	$city = trim( preg_replace( '/^the\s+/i', '', explode( ',', $v )[0] ) );
	$city = preg_replace( '/^Metro Manila$/i', 'Manila', $city );
	return $city;
}

/** Whether the theme knows the city (so it shows a country and flag). */
function apt_ai_city_known( $city ) {
	if ( '' === $city || ! function_exists( 'get_the_country' ) ) {
		return '' === $city;
	}
	$c = get_the_country( $city );
	return '' !== $c['country'];
}

/* ---------- Countries of locations ---------- */

/**
 * The countries a location can have: name as shown on the site => flag file
 * (img/flags/<flag>.svg). The same names and flags as get_the_country().
 */
function apt_location_countries() {
	return array(
		'Brunei Darussalam' => 'br', 'Cambodia' => 'kh', 'Indonesia' => 'id', 'Laos' => 'la', 'Malaysia' => 'ml',
		'Myanmar' => 'mm', 'Philippines' => 'ph', 'Singapore' => 'sg', 'Thailand' => 'th', 'Timor-Leste' => 'tl',
		'Vietnam' => 'vn', 'China' => 'ch', 'Japan' => 'jp', 'Korea' => 'kr',
		'Azerbaijan' => 'az', 'Fiji' => 'fj', 'Georgia' => 'ge', 'Germany' => 'de', 'India' => 'in', 'Italy' => 'it',
		'Kazakhstan' => 'kz', 'Spain' => 'es', 'Sri Lanka' => 'sl', 'Sweden' => 'sw', 'Turkey' => 'tr',
		'USA' => 'us', 'Uzbekistan' => 'uz',
	);
}

/** A country as written in the calendar, in the site's spelling, or ''. */
function apt_location_country_name( $text ) {
	$t       = trim( preg_replace( '/\(.*?\)|^the\s+/i', '', (string) $text ) );
	$aliases = array(
		'republic of korea' => 'Korea', 'rok' => 'Korea', 'south korea' => 'Korea', 'korea' => 'Korea',
		'viet nam' => 'Vietnam', 'lao pdr' => 'Laos', "lao people's democratic republic" => 'Laos',
		'brunei' => 'Brunei Darussalam', "people's republic of china" => 'China', 'prc' => 'China',
		'united states' => 'USA', 'united states of america' => 'USA', 'us' => 'USA', 'timor leste' => 'Timor-Leste',
	);
	$l = strtolower( $t );
	if ( isset( $aliases[ $l ] ) ) {
		return $aliases[ $l ];
	}
	foreach ( array_keys( apt_location_countries() ) as $name ) {
		if ( strtolower( $name ) === $l ) {
			return $name;
		}
	}
	return '';
}

/** The country set for a location under Posts → Locations, or ''. */
function apt_location_country( $city ) {
	static $cache = array();
	if ( ! isset( $cache[ $city ] ) ) {
		$term           = '' !== (string) $city ? get_term_by( 'name', $city, 'mec_location' ) : false;
		$cache[ $city ] = $term ? (string) get_term_meta( $term->term_id, 'apt_country', true ) : '';
	}
	return $cache[ $city ];
}

/** The "Country" field on Posts → Locations. */
function apt_location_country_select( $current ) {
	$out = '<select name="apt_country" id="apt-country"><option value="">' . esc_html__( '— Not set —', 'akseki-vi' ) . '</option>';
	foreach ( array_keys( apt_location_countries() ) as $name ) {
		$out .= '<option value="' . esc_attr( $name ) . '"' . selected( $current, $name, false ) . '>' . esc_html( $name ) . '</option>';
	}
	return $out . '</select>';
}
add_action( 'mec_location_add_form_fields', function () {
	wp_nonce_field( 'apt_country', 'apt_country_nonce' );
	echo '<div class="form-field"><label for="apt-country">' . esc_html__( 'Country', 'akseki-vi' ) . '</label>' . apt_location_country_select( '' ) // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside
		. '<p>' . esc_html__( 'Shows the country and its flag next to this city on statements and in the agenda. Not needed for cities the theme already knows.', 'akseki-vi' ) . '</p></div>';
} );
add_action( 'mec_location_edit_form_fields', function ( $term ) {
	wp_nonce_field( 'apt_country', 'apt_country_nonce' );
	$known = get_the_country( $term->name );
	echo '<tr class="form-field"><th scope="row"><label for="apt-country">' . esc_html__( 'Country', 'akseki-vi' ) . '</label></th><td>'
		. apt_location_country_select( (string) get_term_meta( $term->term_id, 'apt_country', true ) ) // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside
		. '<p class="description">' . esc_html( '' !== $known['country'] && '' === (string) get_term_meta( $term->term_id, 'apt_country', true ) ? sprintf( /* translators: %s: country */ __( 'The theme already knows this city: %s.', 'akseki-vi' ), $known['country'] ) : __( 'Shows the country and its flag next to this city on statements and in the agenda.', 'akseki-vi' ) ) . '</p></td></tr>';
} );
$apt_save_country = function ( $term_id ) {
	if ( isset( $_POST['apt_country'], $_POST['apt_country_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['apt_country_nonce'] ) ), 'apt_country' ) && current_user_can( 'edit_others_posts' ) ) {
		$c = sanitize_text_field( wp_unslash( $_POST['apt_country'] ) );
		if ( isset( apt_location_countries()[ $c ] ) ) {
			update_term_meta( $term_id, 'apt_country', $c );
		} else {
			delete_term_meta( $term_id, 'apt_country' );
		}
	}
};
add_action( 'created_mec_location', $apt_save_country );
add_action( 'edited_mec_location', $apt_save_country );

/* ---------- Meeting series ---------- */

/** The agenda's meeting categories (under Statements 7 and 45): id => category. */
function apt_ai_series() {
	$ids  = array_unique( array_merge( (array) get_term_children( 7, 'category' ), (array) get_term_children( 45, 'category' ) ) );
	$cats = array();
	foreach ( $ids as $id ) {
		$c = get_category( $id );
		if ( $c && ! is_wp_error( $c ) ) {
			$cats[ (int) $id ] = $c;
		}
	}
	uasort( $cats, function ( $a, $b ) { return strcasecmp( $a->name, $b->name ); } );
	return $cats;
}

/** A name reduced for comparing: "25th SOM AMAF Plus Three" → SOMAMAF+3. */
function apt_ai_key( $s ) {
	$s = strtoupper( html_entity_decode( wp_strip_all_tags( (string) $s ), ENT_QUOTES, 'UTF-8' ) );
	$s = preg_replace( '/\bPLUS\s*(THREE|3)\b/', '+3', $s );
	$s = preg_replace( '/\s*\+\s*3\b/', '+3', $s );
	$s = preg_replace( '/\b\d+(ST|ND|RD|TH)?\b|\b(THE|OF|MEETINGS?|CONSULTATION|AND RELATED EVENTS?|PREP(ARATORY)?|SPEC(IAL)?)\b/', ' ', $s );
	return preg_replace( '/[^A-Z0-9+&]/', '', $s );
}

/** The short forms in a meeting title: "(AFMGM+3)", "AMEM+3", "SLOM+3"… */
function apt_ai_title_keys( $title ) {
	$keys = array();
	if ( preg_match_all( '/\(([^)]{2,40})\)/', $title, $m ) ) {
		foreach ( $m[1] as $x ) {
			$keys[] = apt_ai_key( $x );
		}
	}
	if ( preg_match_all( '/\b[A-Z][A-Za-z&-]*[A-Z]\s*(?:\+\s*3|Plus\s*(?:Three|3))/', $title, $m ) ) {
		foreach ( $m[0] as $x ) {
			$keys[] = apt_ai_key( $x ); // left to right
		}
	}
	$keys[] = apt_ai_key( $title );
	return array_values( array_unique( array_filter( $keys, function ( $k ) { return strlen( $k ) >= 3; } ) ) );
}

/** The likely meeting series of a title: category ID or 0. */
function apt_ai_guess_series( $title, $series ) {
	$map = get_option( 'apt_ai_map', array() );
	$tk  = apt_ai_key( $title );
	if ( isset( $map[ $tk ], $series[ $map[ $tk ] ] ) ) {
		return (int) $map[ $tk ]; // corrected before: remembered
	}
	$keys = apt_ai_title_keys( $title ); // in order: the first named meeting wins
	$best = array( 0, 0 );
	foreach ( $series as $id => $c ) {
		foreach ( array( $c->name, $c->description, str_replace( '-', ' ', $c->slug ) ) as $cand ) {
			$ck = apt_ai_key( $cand );
			if ( strlen( $ck ) < 3 ) {
				continue;
			}
			$score = 0;
			$pos = array_search( $ck, $keys, true );
			if ( false !== $pos ) {
				$score = 200 - 20 * $pos + strlen( $ck );
			} elseif ( strlen( $ck ) >= 5 && false !== strpos( $tk, $ck ) ) {
				$score = 50 + strlen( $ck );
			} elseif ( strlen( $ck ) >= 12 ) {
				similar_text( $ck, $tk, $pct );
				$score = $pct >= 85 ? $pct - 40 : 0;
			}
			if ( $score > $best[1] ) {
				$best = array( (int) $id, $score );
			}
		}
	}
	return $best[0];
}

/* ---------- From the file to preview rows ---------- */

/** Rows of APT meetings from a parsed sheet. */
function apt_ai_rows( $sheet ) {
	$rows   = $sheet['rows'];
	$year   = (int) gmdate( 'Y' );
	$cols   = array();
	$start  = 0;
	foreach ( $rows as $i => $r ) {
		$joined = implode( ' ', $r );
		if ( ! $cols && preg_match( '/\b(20\d\d)\b/', $joined, $m ) && preg_match( '/calendar/i', $joined ) ) {
			$year = (int) $m[1];
		}
		$found = array();
		foreach ( $r as $col => $v ) {
			$v = strtolower( trim( (string) $v ) );
			if ( ! isset( $found['title'] ) && preg_match( '/meeting|activity|title/', $v ) && ! preg_match( '/^no\b|venue|number/', $v ) ) {
				$found['title'] = $col;
			} elseif ( preg_match( '/^date/', $v ) ) {
				$found['date'] = $col;
			} elseif ( preg_match( '/venue|modality/', $v ) ) {
				$found['venue'] = $col;
			} elseif ( preg_match( '/^level/', $v ) ) {
				$found['level'] = $col;
			} elseif ( preg_match( '/remark/', $v ) ) {
				$found['remarks'] = $col;
			}
		}
		if ( isset( $found['title'], $found['date'] ) ) {
			$cols  = $found;
			$start = $i + 1;
			break;
		}
	}
	if ( ! $cols ) {
		return new WP_Error( 'header', __( 'The heading row (with "Meeting" and "Date" columns) was not found.', 'akseki-vi' ) );
	}
	if ( preg_match( '/\b(20\d\d)\b/', $sheet['title'], $m ) && ! $year ) {
		$year = (int) $m[1];
	}
	$series = apt_ai_series();
	$out    = array();
	$get    = function ( $r, $k ) use ( $cols ) {
		return isset( $cols[ $k ], $r[ $cols[ $k ] ] ) ? trim( preg_replace( '/\s+/u', ' ', str_replace( "\xC2\xA0", ' ', (string) $r[ $cols[ $k ] ] ) ) ) : '';
	};
	for ( $i = $start; $i < count( $rows ); $i++ ) {
		$title = $get( $rows[ $i ], 'title' );
		if ( '' === $title || ! apt_ai_is_apt( $title ) ) {
			continue;
		}
		list( $date, $mode ) = apt_ai_parse_date( $get( $rows[ $i ], 'date' ), $year );
		$venue = $get( $rows[ $i ], 'venue' );
		$out[] = array(
			'title'   => $title,
			'level'   => $get( $rows[ $i ], 'level' ),
			'raw'     => $get( $rows[ $i ], 'date' ),
			'date'    => $date,
			'mode'    => $mode,
			'venue'   => $venue,
			'city'    => apt_ai_city( $venue ),
			'country' => false !== strpos( $venue, ',' ) ? apt_location_country_name( substr( $venue, strrpos( $venue, ',' ) + 1 ) ) : apt_location_country_name( $venue ),
			'cat'     => apt_ai_guess_series( $title, $series ),
			'cancel'  => (bool) preg_match( '/cancel/i', $get( $rows[ $i ], 'remarks' ) ),
		);
	}
	return $out;
}

/**
 * What is already on the site for a row: [ status, post ID, old date ].
 * Status: new, have, moved (in the agenda with another date), published,
 * past, cancelled.
 */
function apt_ai_status( $row ) {
	$today = current_time( 'Y-m-d' );
	$found = null;
	if ( $row['cat'] ) {
		$t      = strtotime( $row['date'] );
		$window = 'SC_nodate' === $row['mode'] ? 300 : 120;
		$posts  = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => array( 'pending', 'publish', 'future', 'draft' ),
			'cat'            => $row['cat'],
			'posts_per_page' => 20,
			'date_query'     => array( array( 'after' => gmdate( 'Y-m-d', $t - $window * DAY_IN_SECONDS ), 'before' => gmdate( 'Y-m-d', $t + $window * DAY_IN_SECONDS ), 'inclusive' => true ) ),
		) );
		foreach ( $posts as $p ) {
			if ( ! $found || abs( strtotime( $p->post_date ) - $t ) < abs( strtotime( $found->post_date ) - $t ) ) {
				$found = $p;
			}
		}
	}
	if ( ! $found ) {
		$want = apt_ai_key( $row['title'] );
		foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => array( 'pending', 'draft' ), 'posts_per_page' => 200 ) ) as $p ) {
			if ( apt_ai_key( $p->post_title ) === $want && substr( $p->post_date, 0, 4 ) === substr( $row['date'], 0, 4 ) ) {
				$found = $p;
				break;
			}
		}
	}
	if ( $found && 'publish' === $found->post_status ) {
		return array( 'published', $found->ID, '' );
	}
	if ( $row['cancel'] ) {
		return array( 'cancelled', $found ? $found->ID : 0, '' );
	}
	if ( $found ) {
		$same = substr( $found->post_date, 0, 10 ) === $row['date'];
		return array( $same ? 'have' : 'moved', $found->ID, substr( $found->post_date, 0, 10 ) );
	}
	if ( 'SC_nodate' !== $row['mode'] && $row['date'] < $today && ! ( 'SC_month' === $row['mode'] && substr( $row['date'], 0, 7 ) === substr( $today, 0, 7 ) ) ) {
		return array( 'past', 0, '' );
	}
	return array( 'new', 0, '' );
}

/* ---------- Creating the entries ---------- */

/** The post date for a row (a "month only" date this month becomes its last day, so it still shows). */
function apt_ai_post_date( $date, $mode ) {
	if ( 'SC_month' === $mode && $date < current_time( 'Y-m-d' ) ) {
		$date = gmdate( 'Y-m-t', strtotime( $date ) );
	}
	return $date . ' 09:00:00';
}

/** Create a pending agenda entry, or update the date of an existing one. Returns the post ID or 0. */
function apt_ai_save_row( $row, $post_id = 0 ) {
	$date = apt_ai_post_date( $row['date'], $row['mode'] );
	if ( $post_id ) {
		$id = wp_update_post( array( 'ID' => $post_id, 'post_date' => $date, 'post_date_gmt' => get_gmt_from_date( $date ), 'edit_date' => true ), true );
	} else {
		$id = wp_insert_post( array(
			'post_type'     => 'post',
			'post_status'   => 'pending',
			'post_title'    => $row['title'],
			'post_content'  => '',
			'post_date'     => $date,
			'post_date_gmt' => get_gmt_from_date( $date ),
			'post_category' => $row['cat'] ? array( (int) $row['cat'] ) : array(),
		), true );
	}
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	// Date tags: replace any SC_ tag with the one for this row.
	$tags = array_filter( wp_get_post_tags( $id, array( 'fields' => 'names' ) ), function ( $n ) { return 0 !== strpos( $n, 'SC_' ); } );
	if ( $row['mode'] ) {
		$tags[] = $row['mode'];
	}
	wp_set_post_tags( $id, array_values( $tags ), false );
	if ( ! $post_id && '' !== $row['city'] ) {
		$terms = wp_set_object_terms( $id, $row['city'], 'mec_location', false );
		// A city the theme doesn't know: give it the country from the calendar.
		$known = get_the_country( $row['city'] );
		if ( ! is_wp_error( $terms ) && $terms && '' === $known['country'] && ! empty( $row['country'] ) && ! get_term_meta( (int) $terms[0], 'apt_country', true ) ) {
			update_term_meta( (int) $terms[0], 'apt_country', $row['country'] );
		}
	}
	return (int) $id;
}

/* ---------- The screen ---------- */

add_action( 'admin_menu', function () {
	$hook = add_posts_page( __( 'Import agenda', 'akseki-vi' ), __( 'Import agenda', 'akseki-vi' ), 'edit_others_posts', 'apt-agenda-import', 'apt_ai_page' );
	add_action( 'load-' . $hook, 'apt_ai_actions' );
} );

function apt_ai_store_key() {
	return 'apt_ai_rows_' . get_current_user_id();
}

function apt_ai_actions() {
	if ( empty( $_POST['apt_ai'] ) ) {
		return;
	}
	check_admin_referer( 'apt_ai' );
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'akseki-vi' ) );
	}
	$do   = sanitize_key( wp_unslash( $_POST['apt_ai'] ) );
	$back = admin_url( 'edit.php?page=apt-agenda-import' );

	if ( 'upload' === $do ) {
		$f = isset( $_FILES['calendar'] ) ? $_FILES['calendar'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$ext = $f ? strtolower( pathinfo( (string) $f['name'], PATHINFO_EXTENSION ) ) : '';
		if ( ! $f || UPLOAD_ERR_OK !== (int) $f['error'] || ! is_uploaded_file( $f['tmp_name'] ) || ! in_array( $ext, array( 'xlsx', 'csv' ), true ) || $f['size'] > 20 * MB_IN_BYTES ) {
			set_transient( apt_ai_store_key() . '_err', __( 'Choose the calendar as an Excel (.xlsx) or CSV file, under 20 MB.', 'akseki-vi' ), 300 );
			wp_safe_redirect( $back );
			exit;
		}
		$sheet = 'csv' === $ext ? apt_ai_read_csv( $f['tmp_name'] ) : apt_ai_read_xlsx( $f['tmp_name'] );
		$rows  = is_wp_error( $sheet ) ? $sheet : apt_ai_rows( $sheet );
		if ( is_wp_error( $rows ) ) {
			set_transient( apt_ai_store_key() . '_err', $rows->get_error_message(), 300 );
			wp_safe_redirect( $back );
			exit;
		}
		set_transient( apt_ai_store_key(), array( 'file' => sanitize_file_name( $f['name'] ), 'sheet' => is_array( $sheet ) ? $sheet['title'] : '', 'rows' => $rows ), 2 * HOUR_IN_SECONDS );
		wp_safe_redirect( $back );
		exit;
	}

	if ( 'import' === $do ) {
		$store = get_transient( apt_ai_store_key() );
		$in    = isset( $_POST['row'] ) ? (array) wp_unslash( $_POST['row'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field below
		$series = apt_ai_series();
		$map    = get_option( 'apt_ai_map', array() );
		$done   = array( 'added' => 0, 'updated' => 0, 'failed' => 0 );
		foreach ( $in as $i => $r ) {
			if ( empty( $r['on'] ) || ! isset( $store['rows'][ (int) $i ] ) ) {
				continue;
			}
			$orig = $store['rows'][ (int) $i ];
			$cat  = isset( $r['cat'] ) ? absint( $r['cat'] ) : 0;
			$row  = array(
				'title' => '' !== trim( (string) $r['title'] ) ? sanitize_text_field( $r['title'] ) : $orig['title'],
				'date'  => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $r['date'] ) ? $r['date'] : $orig['date'],
				'mode'  => in_array( (string) $r['mode'], array( '', 'SC_month', 'SC_year', 'SC_nodate' ), true ) ? (string) $r['mode'] : $orig['mode'],
				'city'  => sanitize_text_field( isset( $r['city'] ) ? $r['city'] : '' ),
				'cat'   => isset( $series[ $cat ] ) ? $cat : 0,
			);
			$row['country'] = ! empty( $r['country'] ) ? apt_location_country_name( sanitize_text_field( $r['country'] ) ) : '';
			if ( $row['cat'] && $row['cat'] !== (int) $orig['cat'] ) {
				$map[ apt_ai_key( $orig['title'] ) ] = $row['cat']; // remember the correction
			}
			$update = ! empty( $r['update'] ) ? absint( $r['update'] ) : 0;
			$id     = apt_ai_save_row( $row, $update && current_user_can( 'edit_post', $update ) ? $update : 0 );
			$done[ $id ? ( $update ? 'updated' : 'added' ) : 'failed' ]++;
		}
		update_option( 'apt_ai_map', $map, false );
		delete_transient( apt_ai_store_key() );
		wp_safe_redirect( add_query_arg( $done, $back ) );
		exit;
	}

	if ( 'reset' === $do ) {
		delete_transient( apt_ai_store_key() );
		wp_safe_redirect( $back );
		exit;
	}
}

function apt_ai_page() {
	$store  = get_transient( apt_ai_store_key() );
	$err    = get_transient( apt_ai_store_key() . '_err' );
	delete_transient( apt_ai_store_key() . '_err' );
	$series = apt_ai_series();
	$labels = array(
		'new'       => array( 'is-new', __( 'New', 'akseki-vi' ) ),
		'moved'     => array( 'is-moved', __( 'Date changed', 'akseki-vi' ) ),
		'have'      => array( 'is-have', __( 'Already in the agenda', 'akseki-vi' ) ),
		'published' => array( 'is-have', __( 'Already published', 'akseki-vi' ) ),
		'past'      => array( 'is-past', __( 'Past', 'akseki-vi' ) ),
		'cancelled' => array( 'is-past', __( 'Cancelled', 'akseki-vi' ) ),
	);
	$modes  = array(
		''          => __( 'Full date', 'akseki-vi' ),
		'SC_month'  => __( 'Month only', 'akseki-vi' ),
		'SC_year'   => __( 'Year only', 'akseki-vi' ),
		'SC_nodate' => __( 'To be decided', 'akseki-vi' ),
	);
	?>
	<div class="wrap apt-ai">
		<h1><?php esc_html_e( 'Import agenda', 'akseki-vi' ); ?></h1>
		<p class="apt-ai-lead"><?php esc_html_e( 'Upload the ASEAN notional calendar. The ASEAN Plus Three meetings in it are listed below; tick the ones to add, check them, and import. Each becomes a Pending Review post, as when you add a meeting by hand, and appears in the agenda.', 'akseki-vi' ); ?></p>

		<?php if ( isset( $_GET['added'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>
				<?php
				// phpcs:disable WordPress.Security.NonceVerification
				printf(
					/* translators: 1: added, 2: updated */
					esc_html__( '%1$d meetings added to the agenda, %2$d dates updated.', 'akseki-vi' ),
					absint( $_GET['added'] ),
					absint( isset( $_GET['updated'] ) ? $_GET['updated'] : 0 )
				);
				if ( ! empty( $_GET['failed'] ) ) {
					echo ' ' . esc_html( sprintf( /* translators: %d: count */ __( '%d could not be saved.', 'akseki-vi' ), absint( $_GET['failed'] ) ) );
				}
				// phpcs:enable
				?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_status=pending&post_type=post' ) ); ?>"><?php esc_html_e( 'See the pending posts', 'akseki-vi' ); ?></a>
			</p></div>
		<?php endif; ?>
		<?php if ( $err ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $err ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! $store ) : ?>
			<form method="post" enctype="multipart/form-data" class="postbox apt-ai-upload"><div class="inside">
				<?php wp_nonce_field( 'apt_ai' ); ?>
				<input type="hidden" name="apt_ai" value="upload">
				<p><label for="apt-ai-file"><strong><?php esc_html_e( 'Notional calendar (.xlsx)', 'akseki-vi' ); ?></strong></label><br>
					<input type="file" id="apt-ai-file" name="calendar" accept=".xlsx,.csv" required></p>
				<p><button class="button button-primary"><?php esc_html_e( 'Read the calendar', 'akseki-vi' ); ?></button></p>
				<p class="description"><?php esc_html_e( 'The visible sheet is read (the hidden sheets with older revisions are skipped). Nothing is saved until you import.', 'akseki-vi' ); ?></p>
			</div></form>
		<?php else : ?>
			<?php
			$rows = array();
			foreach ( $store['rows'] as $i => $r ) {
				list( $st, $pid, $old ) = apt_ai_status( $r );
				$rows[ $i ] = $r + array( 'status' => $st, 'pid' => $pid, 'old' => $old );
			}
			$count = array_count_values( wp_list_pluck( $rows, 'status' ) );
			?>
			<p class="apt-ai-file">
				<?php echo esc_html( sprintf( /* translators: 1: file, 2: sheet, 3: count */ __( '%1$s, sheet "%2$s": %3$d ASEAN Plus Three meetings.', 'akseki-vi' ), $store['file'], $store['sheet'], count( $rows ) ) ); ?>
				<?php foreach ( $labels as $k => $l ) { if ( ! empty( $count[ $k ] ) ) { echo ' <span class="apt-ai-tag ' . esc_attr( $l[0] ) . '">' . esc_html( $l[1] ) . ': ' . (int) $count[ $k ] . '</span>'; } } ?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'apt_ai' ); ?>
				<p class="apt-ai-tools">
					<label><input type="checkbox" id="apt-ai-showpast"> <?php esc_html_e( 'Show past, cancelled and already-entered meetings', 'akseki-vi' ); ?></label>
				</p>
				<table class="widefat apt-ai-table">
					<thead><tr>
						<th class="check-column"><input type="checkbox" id="apt-ai-all" aria-label="<?php esc_attr_e( 'Tick all shown', 'akseki-vi' ); ?>"></th>
						<th><?php esc_html_e( 'Meeting', 'akseki-vi' ); ?></th>
						<th style="width:250px"><?php esc_html_e( 'Date', 'akseki-vi' ); ?></th>
						<th style="width:240px"><?php esc_html_e( 'Meeting series (category)', 'akseki-vi' ); ?></th>
						<th style="width:170px"><?php esc_html_e( 'Location', 'akseki-vi' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $rows as $i => $r ) : ?>
						<?php
						$on   = in_array( $r['status'], array( 'new', 'moved' ), true );
						$hide = in_array( $r['status'], array( 'past', 'cancelled', 'have', 'published' ), true );
						$n    = 'row[' . (int) $i . ']';
						?>
						<tr class="apt-ai-<?php echo esc_attr( $r['status'] ); ?><?php echo $hide ? ' apt-ai-hidden' : ''; ?>">
							<th class="check-column"><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[on]" value="1" <?php checked( $on ); ?> <?php disabled( 'published' === $r['status'] ); ?>></th>
							<td>
								<input type="text" class="apt-ai-title" name="<?php echo esc_attr( $n ); ?>[title]" value="<?php echo esc_attr( $r['title'] ); ?>">
								<div class="apt-ai-meta">
									<span class="apt-ai-tag <?php echo esc_attr( $labels[ $r['status'] ][0] ); ?>"><?php echo esc_html( $labels[ $r['status'] ][1] ); ?></span>
									<?php echo esc_html( $r['level'] ); ?>
									<?php if ( 'moved' === $r['status'] ) : ?>
										· <?php echo esc_html( sprintf( /* translators: %s: date */ __( 'in the agenda for %s; importing updates its date', 'akseki-vi' ), wp_date( 'j M Y', strtotime( $r['old'] ) ) ) ); ?>
										<input type="hidden" name="<?php echo esc_attr( $n ); ?>[update]" value="<?php echo (int) $r['pid']; ?>">
									<?php endif; ?>
									<?php if ( $r['pid'] && in_array( $r['status'], array( 'have', 'published', 'moved' ), true ) ) : ?>
										· <a href="<?php echo esc_url( get_edit_post_link( $r['pid'] ) ); ?>"><?php esc_html_e( 'open', 'akseki-vi' ); ?></a>
									<?php endif; ?>
								</div>
							</td>
							<td>
								<input type="date" name="<?php echo esc_attr( $n ); ?>[date]" value="<?php echo esc_attr( $r['date'] ); ?>">
								<select name="<?php echo esc_attr( $n ); ?>[mode]"><?php foreach ( $modes as $k => $l ) { echo '<option value="' . esc_attr( $k ) . '"' . selected( $r['mode'], $k, false ) . '>' . esc_html( $l ) . '</option>'; } ?></select>
								<div class="apt-ai-meta"><?php echo esc_html( sprintf( /* translators: %s: date as written */ __( 'Calendar: %s', 'akseki-vi' ), '' !== $r['raw'] ? ( is_numeric( $r['raw'] ) ? wp_date( 'j F Y', strtotime( $r['date'] ) ) : $r['raw'] ) : __( '(empty)', 'akseki-vi' ) ) ); ?></div>
							</td>
							<td>
								<select name="<?php echo esc_attr( $n ); ?>[cat]" class="apt-ai-cat<?php echo $r['cat'] ? '' : ' is-missing'; ?>">
									<option value="0"><?php esc_html_e( '— Choose —', 'akseki-vi' ); ?></option>
									<?php foreach ( $series as $id => $c ) { echo '<option value="' . (int) $id . '"' . selected( (int) $r['cat'], (int) $id, false ) . '>' . esc_html( $c->name . ( $c->description && $c->description !== $c->name ? ' – ' . wp_html_excerpt( wp_strip_all_tags( $c->description ), 45, '…' ) : '' ) ) . '</option>'; } ?>
								</select>
								<?php if ( ! $r['cat'] ) : ?><div class="apt-ai-meta apt-ai-warn"><?php esc_html_e( 'Not recognised: choose the series.', 'akseki-vi' ); ?></div><?php endif; ?>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $n ); ?>[city]" value="<?php echo esc_attr( $r['city'] ); ?>" placeholder="<?php esc_attr_e( 'TBC', 'akseki-vi' ); ?>">
								<div class="apt-ai-meta"><?php echo esc_html( $r['venue'] ? sprintf( /* translators: %s: venue */ __( 'Calendar: %s', 'akseki-vi' ), $r['venue'] ) : __( 'No venue in the calendar', 'akseki-vi' ) ); ?></div>
								<?php if ( '' !== $r['city'] && ! apt_ai_city_known( $r['city'] ) && '' === apt_location_country( $r['city'] ) ) : ?>
									<div class="apt-ai-meta"><label><?php esc_html_e( 'New city; its country:', 'akseki-vi' ); ?>
										<select name="<?php echo esc_attr( $n ); ?>[country]"><option value=""><?php esc_html_e( '— choose —', 'akseki-vi' ); ?></option><?php foreach ( array_keys( apt_location_countries() ) as $cn ) { echo '<option' . selected( isset( $r['country'] ) ? $r['country'] : '', $cn, false ) . '>' . esc_html( $cn ) . '</option>'; } ?></select></label></div>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p class="apt-ai-actions">
					<button class="button button-primary" name="apt_ai" value="import"><?php esc_html_e( 'Import ticked meetings', 'akseki-vi' ); ?></button>
					<button class="button" name="apt_ai" value="reset" formnovalidate><?php esc_html_e( 'Start again with another file', 'akseki-vi' ); ?></button>
				</p>
				<p class="description"><?php esc_html_e( 'A corrected meeting series is remembered for the next calendar. Meetings dated "To be decided" are listed under the agenda\'s undated meetings until 31 December.', 'akseki-vi' ); ?></p>
			</form>
		<?php endif; ?>
	</div>
	<style>
		.apt-ai-lead { max-width: 900px; font-size: 14px; }
		.apt-ai-upload { max-width: 640px; margin-top: 12px; }
		.apt-ai-file { font-size: 14px; }
		.apt-ai-table td, .apt-ai-table th { vertical-align: top; }
		.apt-ai-table input[type=text], .apt-ai-table select { width: 100%; max-width: none; }
		.apt-ai-title { font-weight: 600; }
		.apt-ai-meta { margin-top: 4px; color: #646970; font-size: 12px; }
		.apt-ai-warn { color: #996800; font-weight: 600; }
		.apt-ai-cat.is-missing { border-color: #dba617; }
		.apt-ai-tag { display: inline-block; font-size: 11px; font-weight: 600; padding: 0 7px; border-radius: 9px; border: 1px solid; margin-right: 4px; }
		.apt-ai-tag.is-new { background: #edfaef; color: #00450c; border-color: #68de7c; }
		.apt-ai-tag.is-moved { background: #fcf0d1; color: #6e4b00; border-color: #f0c33c; }
		.apt-ai-tag.is-have { background: #f0f6fc; color: #0a4b78; border-color: #72aee6; }
		.apt-ai-tag.is-past { background: #f0f0f1; color: #50575e; border-color: #c3c4c7; }
		.apt-ai-hidden { display: none; }
		.apt-ai-showall .apt-ai-hidden { display: table-row; opacity: .75; }
		.apt-ai-actions { margin-top: 14px; }
	</style>
	<script>
	( function () {
		var wrap = document.querySelector( '.apt-ai' ), past = document.getElementById( 'apt-ai-showpast' ), all = document.getElementById( 'apt-ai-all' );
		if ( past ) { past.addEventListener( 'change', function () { wrap.classList.toggle( 'apt-ai-showall', past.checked ); } ); }
		if ( all ) {
			all.addEventListener( 'change', function () {
				wrap.querySelectorAll( '.apt-ai-table tbody tr' ).forEach( function ( tr ) {
					var box = tr.querySelector( 'input[type=checkbox]' );
					if ( box && ! box.disabled && tr.offsetParent !== null ) { box.checked = all.checked; }
				} );
			} );
		}
	} )();
	</script>
	<?php
}
