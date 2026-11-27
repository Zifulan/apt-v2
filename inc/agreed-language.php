<?php
/**
 * Agreed Language finder (in the Members page).
 *
 * Finds wording already agreed in ASEAN Plus Three statements, paragraph by
 * paragraph, with the source, date and PDF page, a ready citation, and a
 * "drafting basket" that downloads as a Word document. Members can include
 * the Limited Access documents.
 *
 * Index: its own table (wp_apt_paragraphs), one row per paragraph, built in
 * short batches (Users → Members → Agreed Language) and kept up to date when
 * posts are saved:
 * - every published post's text, split at paragraphs, list items and headings;
 * - the PDFs each post links to (the files the APT PDF Search plugin finds),
 *   page by page, with the page number;
 * - the Limited Access PDFs linked from the Limited Access pages, marked
 *   "limited" and shown to members only.
 * PDFs are read with the PDF reader bundled in the APT PDF Search plugin
 * (smalot/pdfparser); without that plugin only the posts' own text is indexed.
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const APT_ALF_VERSION = 1;

function apt_alf_table() {
	global $wpdb;
	return $wpdb->prefix . 'apt_paragraphs';
}

add_action( 'init', function () {
	if ( (int) get_option( 'apt_alf_version' ) < APT_ALF_VERSION ) {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( 'CREATE TABLE ' . apt_alf_table() . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			source varchar(10) NOT NULL DEFAULT 'html',
			file varchar(255) NOT NULL DEFAULT '',
			page smallint(5) unsigned NOT NULL DEFAULT 0,
			pos int(10) unsigned NOT NULL DEFAULT 0,
			para varchar(12) NOT NULL DEFAULT '',
			text mediumtext NOT NULL,
			year smallint(5) unsigned NOT NULL DEFAULT 0,
			day date DEFAULT NULL,
			level varchar(40) NOT NULL DEFAULT '',
			title varchar(255) NOT NULL DEFAULT '',
			short varchar(120) NOT NULL DEFAULT '',
			limited tinyint(1) NOT NULL DEFAULT 0,
			hash char(32) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY year (year),
			KEY limited (limited)
		) " . $wpdb->get_charset_collate() . ';' );
		update_option( 'apt_alf_version', APT_ALF_VERSION );
		update_option( 'apt_alf_queue', array( 'all' ), false ); // build everything once
	}
} );

/* ---------- Reading texts into paragraphs ---------- */

/** Load the PDF reader bundled in the APT PDF Search plugin. */
function apt_alf_pdf_reader_ready() {
	if ( class_exists( '\Smalot\PdfParser\Parser' ) ) {
		return true;
	}
	if ( function_exists( 'apt_pdf_search_extract' ) ) {
		$f        = ( new ReflectionFunction( 'apt_pdf_search_extract' ) )->getFileName();
		$autoload = dirname( dirname( $f ) ) . '/vendor/autoload.php';
		if ( is_readable( $autoload ) ) {
			require_once $autoload;
		}
	}
	return class_exists( '\Smalot\PdfParser\Parser' );
}

/** The text of each page of a PDF: [ page number => text ], or [] if unreadable. */
function apt_alf_pdf_pages( $file ) {
	if ( ! apt_alf_pdf_reader_ready() || ! is_readable( $file ) || filesize( $file ) > 8 * MB_IN_BYTES ) {
		return array(); // very large PDFs (usually scans or photo-heavy) are skipped
	}
	// Not when memory is already more than half used: reading could run out of it.
	$limit = wp_convert_hr_to_bytes( (string) ini_get( 'memory_limit' ) );
	if ( $limit > 0 && memory_get_usage( true ) > 0.5 * $limit ) {
		return array();
	}
	$head = (string) file_get_contents( $file, false, null, 0, 1024 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( false === strpos( $head, '%PDF-' ) ) {
		return array();
	}
	wp_raise_memory_limit( 'admin' );
	try {
		$config = null;
		if ( class_exists( '\Smalot\PdfParser\Config' ) ) {
			$config = new \Smalot\PdfParser\Config();
			if ( method_exists( $config, 'setRetainImageContent' ) ) {
				$config->setRetainImageContent( false ); // text only: much less memory
			}
			if ( method_exists( $config, 'setDecodeMemoryLimit' ) ) {
				$config->setDecodeMemoryLimit( 64 * MB_IN_BYTES );
			}
		}
		$parser = $config ? new \Smalot\PdfParser\Parser( array(), $config ) : new \Smalot\PdfParser\Parser();
		$pdf    = $parser->parseFile( $file );
		$pages = array();
		foreach ( $pdf->getPages() as $i => $page ) {
			$pages[ $i + 1 ] = (string) $page->getText();
		}
		return $pages;
	} catch ( \Throwable $e ) {
		return array();
	}
}

/** Whether a line starts a numbered or lettered paragraph: "12.", "(a)", "b)", "1.1", "•". */
function apt_alf_is_numbered( $line ) {
	return (bool) preg_match( '/^\s*(\d{1,3}(\.\d{1,2})*\.?\s|\(?[a-z]\)\s|\(?(i|ii|iii|iv|v|vi|vii|viii|ix|x|xi|xii)\)\s|[•▪●◦\x{2022}\-–]\s)/u', $line );
}

/**
 * Paragraphs of a PDF: [ [ page, text ], … ]. Lines are joined back into
 * paragraphs: a new one starts at a blank line, a numbered/lettered item, or
 * after a short line ending a sentence. Lines repeated on most pages
 * (headers, footers, "FINAL") and page numbers are dropped; a paragraph cut
 * by a page break is joined to the next page.
 */
function apt_alf_pdf_paragraphs( $pages ) {
	if ( ! $pages ) {
		return array();
	}
	// Lines that repeat on half the pages or more are headers/footers.
	$seen = array();
	foreach ( $pages as $t ) {
		foreach ( array_unique( array_map( 'trim', preg_split( '/\R/u', $t ) ) ) as $l ) {
			if ( '' !== $l ) {
				$seen[ $l ] = ( isset( $seen[ $l ] ) ? $seen[ $l ] : 0 ) + 1;
			}
		}
	}
	$repeat = count( $pages ) >= 3 ? max( 2, (int) ceil( count( $pages ) / 2 ) ) : 99;

	$paras = array();
	foreach ( $pages as $pno => $t ) {
		$lines = array();
		foreach ( preg_split( '/\R/u', str_replace( "\t", ' ', $t ) ) as $l ) {
			$l = rtrim( preg_replace( '/[ \x{00A0}]+/u', ' ', $l ) );
			$tl = trim( $l );
			if ( '' !== $tl && ( ( isset( $seen[ $tl ] ) && $seen[ $tl ] >= $repeat ) || preg_match( '/^(page\s*)?\d{1,3}(\s*(of|\/)\s*\d{1,3})?$/i', $tl ) || preg_match( '/^(final|draft|adopted)$/i', $tl ) ) ) {
				continue;
			}
			$lines[] = $l;
		}
		$lens   = array_filter( array_map( function ( $l ) { return mb_strlen( trim( $l ) ); }, $lines ) );
		sort( $lens );
		$median = $lens ? $lens[ (int) floor( count( $lens ) / 2 ) ] : 80;
		$cur    = '';
		$flush  = function () use ( &$cur, &$paras, $pno ) {
			$c = trim( $cur );
			if ( '' !== $c ) {
				$paras[] = array( $pno, $c );
			}
			$cur = '';
		};
		$prev_end = false;
		foreach ( $lines as $l ) {
			$tl = trim( $l );
			if ( '' === $tl ) {
				$flush();
				$prev_end = false;
				continue;
			}
			if ( '' !== $cur && ( apt_alf_is_numbered( $tl ) || $prev_end ) ) {
				$flush();
			}
			// A hyphenated word split at the end of a line ("non-" + "discriminatory"):
			// join without a space, keeping the hyphen (official texts hyphenate
			// compounds, rarely ordinary words).
			if ( '' !== $cur && preg_match( '/\p{L}-$/u', $cur ) && preg_match( '/^\p{Ll}/u', $tl ) ) {
				$cur .= $tl;
			} else {
				$cur .= ( '' === $cur ? '' : ' ' ) . $tl;
			}
			$prev_end = (bool) preg_match( '/[.!?:;]["”’)]?$/u', $tl ) && mb_strlen( $tl ) < 0.8 * $median;
		}
		$flush();
	}
	// A paragraph cut by a page break: join it to the next page's first one.
	$out = array();
	foreach ( $paras as $p ) {
		$last = count( $out ) - 1;
		if ( $last >= 0 && $out[ $last ][0] === $p[0] - 1 && ! preg_match( '/[.!?:;]["”’)]?$/u', $out[ $last ][1] ) && preg_match( '/^\p{Ll}/u', $p[1] ) ) {
			$out[ $last ][1] .= ' ' . $p[1];
			continue;
		}
		$out[] = $p;
	}
	return $out;
}

/** Paragraphs of a post's own text (HTML): paragraphs, list items, table cells, headings. */
function apt_alf_html_paragraphs( $html ) {
	$html = strip_shortcodes( (string) $html );
	$html = preg_replace( '#<(script|style)\b.*?</\1>#is', '', $html );
	$html = preg_replace( '#</?(p|li|h[1-6]|td|th|blockquote|div|br|tr)\b[^>]*>#i', "\n\n", $html );
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
	$out  = array();
	foreach ( preg_split( '/\n\s*\n/u', $text ) as $p ) {
		$p = trim( preg_replace( '/\s+/u', ' ', $p ) );
		if ( '' !== $p ) {
			$out[] = array( 0, $p );
		}
	}
	return $out;
}

/** Long paragraphs are cut at sentence ends into pieces of about 1,500 characters. */
function apt_alf_cut( $text ) {
	if ( mb_strlen( $text ) <= 2500 ) {
		return array( $text );
	}
	$pieces = array();
	$cur    = '';
	foreach ( preg_split( '/(?<=[.;])\s+(?=[A-Z(\d])/u', $text ) as $s ) {
		if ( '' !== $cur && mb_strlen( $cur ) + mb_strlen( $s ) > 1500 ) {
			$pieces[] = $cur;
			$cur      = '';
		}
		$cur .= ( '' === $cur ? '' : ' ' ) . $s;
	}
	if ( '' !== $cur ) {
		$pieces[] = $cur;
	}
	return $pieces;
}

/* ---------- Building the index ---------- */

/** Level label for a post: Summit, Ministerial, or its level category's name. */
function apt_alf_level( $post_id ) {
	if ( ! function_exists( 'akseki_get_level_from_id' ) ) {
		return '';
	}
	$id = (int) akseki_get_level_from_id( $post_id, 'post', 'level' );
	if ( 41 === $id ) {
		return 'Summit';
	}
	if ( 43 === $id ) {
		return 'Ministerial';
	}
	$c = $id ? get_category( $id ) : null;
	return ( $c && ! is_wp_error( $c ) ) ? preg_replace( '/\s*(level\s*)?statements?$/i', '', $c->name ) : '';
}

/** Write a document's paragraphs (replacing what was stored for it). */
function apt_alf_store( $where, $rows ) {
	global $wpdb;
	$wpdb->delete( apt_alf_table(), $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( $rows as $r ) {
		$wpdb->insert( apt_alf_table(), $r ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}

/** Index one published post: its text and its PDFs. */
function apt_alf_index_post( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password ) {
		apt_alf_store( array( 'post_id' => (int) $post_id, 'limited' => 0 ), array() );
		return;
	}
	$base = array(
		'post_id' => (int) $post->ID,
		'year'    => (int) get_the_date( 'Y', $post ),
		'day'     => get_the_date( 'Y-m-d', $post ),
		'level'   => apt_alf_level( $post->ID ),
		'title'   => mb_substr( html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' ), 0, 255 ),
		'short'   => function_exists( 'akseki_get_shortname' ) ? mb_substr( html_entity_decode( wp_strip_all_tags( akseki_get_shortname( get_the_title( $post ), $post->ID ) ), ENT_QUOTES, 'UTF-8' ), 0, 120 ) : '',
		'limited' => 0,
	);
	$rows = array();
	$seen = array();
	$pos  = 0;
	$add  = function ( $source, $file, $page, $text ) use ( &$rows, &$seen, &$pos, $base ) {
		foreach ( apt_alf_cut( $text ) as $piece ) {
			if ( mb_strlen( $piece ) < 40 ) {
				continue;
			}
			$hash = md5( mb_strtolower( preg_replace( '/[^\p{L}\p{N}]+/u', '', $piece ) ) );
			if ( isset( $seen[ $hash ] ) ) {
				continue; // the same paragraph in the page and its PDF: keep one
			}
			$seen[ $hash ] = 1;
			preg_match( '/^\s*(\d{1,3}(\.\d{1,2})*|\(?[a-z]\))[.)]?\s/u', $piece, $m );
			$rows[] = $base + array( 'source' => $source, 'file' => $file, 'page' => (int) $page, 'pos' => $pos++, 'para' => isset( $m[1] ) ? trim( $m[1], '()' ) : '', 'text' => $piece, 'hash' => $hash );
		}
	};
	// PDFs first: their paragraphs come with a page number.
	if ( function_exists( 'apt_pdf_search_find_pdfs' ) ) {
		$up = wp_upload_dir( null, false );
		foreach ( apt_pdf_search_find_pdfs( $post ) as $url => $file ) {
			$rel = ltrim( substr( $file, strlen( realpath( $up['basedir'] ) ) ), '/\\' );
			foreach ( apt_alf_pdf_paragraphs( apt_alf_pdf_pages( $file ) ) as $p ) {
				$add( 'pdf', $rel, $p[0], $p[1] );
			}
		}
	}
	foreach ( apt_alf_html_paragraphs( $post->post_content ) as $p ) {
		$add( 'html', '', 0, $p[1] );
	}
	apt_alf_store( array( 'post_id' => (int) $post->ID, 'limited' => 0 ), $rows );
}

/**
 * The Limited Access PDFs: [ relative path => [ title, section, year ] ],
 * from links on the Limited Access pages (link text as the title, the heading
 * above as the section).
 */
function apt_alf_limited_files() {
	if ( ! function_exists( 'apt_limited_dir' ) ) {
		return array();
	}
	$files = array();
	foreach ( get_pages( array( 'post_status' => 'publish,private' ) ) as $page ) {
		if ( ! apt_limited_is_limited_post( $page ) ) {
			continue;
		}
		$section = '';
		foreach ( preg_split( '#(<h[2-4][^>]*>.*?</h[2-4]>)#is', $page->post_content, -1, PREG_SPLIT_DELIM_CAPTURE ) as $chunk ) {
			if ( preg_match( '#^<h[2-4][^>]*>(.*?)</h[2-4]>$#is', $chunk, $h ) ) {
				$section = trim( html_entity_decode( wp_strip_all_tags( $h[1] ), ENT_QUOTES, 'UTF-8' ) );
				continue;
			}
			if ( ! preg_match_all( '#<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is', $chunk, $links, PREG_SET_ORDER ) ) {
				continue;
			}
			foreach ( $links as $l ) {
				$href = html_entity_decode( $l[1] );
				$rel  = '';
				if ( preg_match( '#[?&]apt_file=([^&\#]+)#', $href, $g ) ) {
					$rel = rawurldecode( $g[1] );
				} elseif ( preg_match( '#/uploads/' . preg_quote( APT_LIMITED_FOLDER, '#' ) . '/([^?\#]+)#', $href, $g ) ) {
					$rel = rawurldecode( $g[1] );
				}
				if ( '' === $rel || false !== strpos( $rel, '..' ) || ! preg_match( '/\.pdf$/i', $rel ) ) {
					continue;
				}
				$title = trim( html_entity_decode( wp_strip_all_tags( $l[2] ), ENT_QUOTES, 'UTF-8' ) );
				$year  = preg_match( '/\b(19|20)\d\d\b/', $title . ' ' . $rel, $y ) ? (int) $y[0] : 0;
				$files[ $rel ] = array( $title, $section, $year );
			}
		}
	}
	return $files;
}

/** Index one Limited Access PDF. */
function apt_alf_index_limited( $rel, $info ) {
	$file = apt_limited_dir() . '/' . $rel;
	$rows = array();
	$seen = array();
	$pos  = 0;
	foreach ( apt_alf_pdf_paragraphs( apt_alf_pdf_pages( $file ) ) as $p ) {
		foreach ( apt_alf_cut( $p[1] ) as $piece ) {
			$hash = md5( mb_strtolower( preg_replace( '/[^\p{L}\p{N}]+/u', '', $piece ) ) );
			if ( mb_strlen( $piece ) < 40 || isset( $seen[ $hash ] ) ) {
				continue;
			}
			$seen[ $hash ] = 1;
			preg_match( '/^\s*(\d{1,3}(\.\d{1,2})*|\(?[a-z]\))[.)]?\s/u', $piece, $m );
			$rows[] = array(
				'post_id' => 0, 'source' => 'limited', 'file' => mb_substr( $rel, 0, 255 ), 'page' => (int) $p[0], 'pos' => $pos++,
				'para' => isset( $m[1] ) ? trim( $m[1], '()' ) : '', 'text' => $piece, 'year' => (int) $info[2],
				'day' => preg_match( '/\b(\d{4}-\d{2}-\d{2})\b/', $info[0], $d ) ? $d[1] : null,
				'level' => 'Limited Access', 'title' => mb_substr( $info[0], 0, 255 ), 'short' => mb_substr( $info[1], 0, 120 ), 'limited' => 1, 'hash' => $hash,
			);
		}
	}
	apt_alf_store( array( 'file' => $rel, 'limited' => 1 ), $rows );
}

/** Queue work: 'all', a post ID, or 'limited'. */
function apt_alf_enqueue( $item ) {
	$q   = (array) get_option( 'apt_alf_queue', array() );
	$q[] = $item;
	update_option( 'apt_alf_queue', array_values( array_unique( $q, SORT_REGULAR ) ), false );
}
add_action( 'save_post_post', function ( $post_id ) {
	if ( ! wp_is_post_revision( $post_id ) ) {
		apt_alf_enqueue( (int) $post_id );
	}
} );
add_action( 'save_post_page', function ( $post_id ) {
	$p = get_post( $post_id );
	if ( $p && function_exists( 'apt_limited_is_limited_post' ) && apt_limited_is_limited_post( $p ) ) {
		apt_alf_enqueue( 'limited' );
	}
} );
add_action( 'deleted_post', function ( $post_id ) {
	global $wpdb;
	$wpdb->delete( apt_alf_table(), array( 'post_id' => (int) $post_id, 'limited' => 0 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
} );

/** A queue item as words, for the "could not be read" list. */
function apt_alf_item_label( $item ) {
	if ( is_array( $item ) && 'L' === $item[0] ) {
		return 'Limited Access: ' . $item[1];
	}
	if ( is_numeric( $item ) ) {
		return get_the_title( (int) $item ) . ' (post ' . (int) $item . ')';
	}
	return (string) $item;
}

/** Note an item that could not be read; it is skipped, not retried. */
function apt_alf_skip( $item, $why ) {
	$s   = (array) get_option( 'apt_alf_skipped', array() );
	$s[] = array( 'item' => apt_alf_item_label( $item ), 'why' => $why, 'time' => time() );
	update_option( 'apt_alf_skipped', array_slice( $s, -50 ), false );
}

/**
 * Work through the queue for about $seconds. 'all' expands to every
 * published post plus 'limited'; 'limited' expands to each Limited Access PDF.
 * Crash-proof: the queue is saved without an item before the item is read,
 * and a marker remembers the item; if the request dies while reading it
 * (time or memory limit, which can't be caught), the next run finds the
 * marker and skips that item instead of crashing on it again.
 * Returns the number of items left.
 */
function apt_alf_run( $seconds = 8 ) {
	global $wpdb;
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 60 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- not allowed on every host
	}
	$start = microtime( true );
	$died  = get_option( 'apt_alf_current' );
	if ( $died ) {
		apt_alf_skip( $died, 'the server stopped while reading it (too long or too much memory)' );
		delete_option( 'apt_alf_current' );
	}
	$q = (array) get_option( 'apt_alf_queue', array() );
	while ( $q && microtime( true ) - $start < $seconds ) {
		$item = array_shift( $q );
		update_option( 'apt_alf_queue', array_values( $q ), false ); // saved first
		if ( 'all' === $item ) {
			$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC" ); // phpcs:ignore WordPress.DB
			$q   = array_merge( array_map( 'intval', $ids ), array( 'limited' ), $q );
		} elseif ( 'limited' === $item ) {
			$files = apt_alf_limited_files();
			$known = $wpdb->get_col( 'SELECT DISTINCT file FROM ' . apt_alf_table() . ' WHERE limited = 1' ); // phpcs:ignore WordPress.DB
			foreach ( array_diff( $known, array_keys( $files ) ) as $gone ) { // no longer linked
				$wpdb->delete( apt_alf_table(), array( 'file' => $gone, 'limited' => 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
			foreach ( $files as $rel => $info ) {
				$q[] = array( 'L', $rel, $info );
			}
		} else {
			update_option( 'apt_alf_current', $item, false );
			try {
				if ( is_array( $item ) && 'L' === $item[0] ) {
					apt_alf_index_limited( $item[1], $item[2] );
				} else {
					apt_alf_index_post( (int) $item );
				}
			} catch ( \Throwable $e ) {
				apt_alf_skip( $item, $e->getMessage() );
			}
			delete_option( 'apt_alf_current' );
		}
		update_option( 'apt_alf_queue', array_values( $q ), false );
	}
	update_option( 'apt_alf_last', time(), false );
	return count( $q );
}

// In the background too (WP-Cron), every 10 minutes while there is work.
add_filter( 'cron_schedules', function ( $s ) {
	$s['apt_ten_minutes'] = array( 'interval' => 600, 'display' => 'Every 10 minutes' );
	return $s;
} );
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'apt_alf_cron' ) ) {
		wp_schedule_event( time() + 120, 'apt_ten_minutes', 'apt_alf_cron' );
	}
} );
add_action( 'apt_alf_cron', function () {
	if ( get_option( 'apt_alf_queue' ) || get_option( 'apt_alf_current' ) ) {
		apt_alf_run( 8 );
	}
} );

/* ---------- Searching ---------- */

/** The query's terms: quoted phrases stay whole, other words separately. */
function apt_alf_terms( $q ) {
	$terms = array();
	if ( preg_match_all( '/"([^"]{2,})"|(\S{2,})/u', str_replace( array( '“', '”' ), '"', (string) $q ), $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $t ) {
			$terms[] = '' !== $t[1] ? trim( $t[1] ) : trim( $t[2] );
		}
	}
	return array_slice( array_values( array_unique( array_filter( $terms ) ) ), 0, 6 );
}

/**
 * Search the paragraphs. $args: q, level, from, to, limited (bool; members).
 * Returns [ 'rows' => [...], 'total' => n, 'variants' => [...], 'years' => [...] ].
 */
function apt_alf_search( $args ) {
	global $wpdb;
	$terms = apt_alf_terms( $args['q'] );
	if ( ! $terms ) {
		return array( 'rows' => array(), 'total' => 0, 'variants' => array(), 'years' => array() );
	}
	$where = array();
	foreach ( $terms as $t ) {
		$where[] = $wpdb->prepare( 'text LIKE %s', '%' . $wpdb->esc_like( $t ) . '%' );
	}
	if ( empty( $args['limited'] ) ) {
		$where[] = 'limited = 0';
	}
	if ( ! empty( $args['level'] ) ) {
		$where[] = $wpdb->prepare( 'level = %s', $args['level'] );
	}
	if ( ! empty( $args['from'] ) ) {
		$where[] = $wpdb->prepare( 'year >= %d', $args['from'] );
	}
	if ( ! empty( $args['to'] ) ) {
		$where[] = $wpdb->prepare( 'year <= %d', $args['to'] );
	}
	$sql  = 'FROM ' . apt_alf_table() . ' WHERE ' . implode( ' AND ', $where );
	$rows = $wpdb->get_results( "SELECT * $sql ORDER BY year DESC, day DESC, post_id DESC, pos ASC LIMIT 400" ); // phpcs:ignore WordPress.DB

	// Whole words only ("APT" is not "adapt"; "measure" still finds "measures").
	$rows = array_values( array_filter( $rows, function ( $r ) use ( $terms ) {
		foreach ( $terms as $t ) {
			if ( ! preg_match( '/(?<![\p{L}\p{N}])' . preg_quote( $t, '/' ) . '/iu', $r->text ) ) {
				return false;
			}
		}
		return true;
	} ) );

	// Wording over time: how the first term continues ("unnecessary barriers to trade").
	$variants = array();
	$years    = array();
	$lead     = $terms[0];
	foreach ( $rows as $r ) {
		$years[ $r->year ] = ( isset( $years[ $r->year ] ) ? $years[ $r->year ] : 0 ) + 1;
		if ( preg_match( '/(?<![\p{L}\p{N}])(' . preg_quote( $lead, '/' ) . '[\p{L}\p{N}\'’-]*(?:\s+[\p{L}\p{N}\'’()-]+){0,3})/iu', $r->text, $m ) ) {
			$phrase = mb_strtolower( $m[1] );
			$head   = mb_substr( $phrase, 0, mb_strlen( $lead ) );
			$rest   = preg_replace( '/[,;:.].*$/u', '', mb_substr( $phrase, mb_strlen( $lead ) ) );
			// Stop the words after the search at filler words:
			// "unnecessary measures that may" → "unnecessary measures".
			$rest   = preg_replace( '/^((?:\s+[^\s]+)*?)\s+(that|which|who|whose|may|might|could|should|would|will|shall|and|or|in|on|for|by|with|as|from|at|is|are|was|were|be)\b.*$/u', '$1', $rest );
			$phrase = trim( $head . $rest );
			if ( ! isset( $variants[ $phrase ] ) ) {
				// Shown with the capitals of its first appearance ("WTO", not "wto").
				$variants[ $phrase ] = array( 'n' => 0, 'min' => 9999, 'max' => 0, 'label' => mb_substr( $m[1], 0, mb_strlen( $phrase ) ) );
			}
			$variants[ $phrase ]['n']++;
			$variants[ $phrase ]['min'] = min( $variants[ $phrase ]['min'], (int) $r->year );
			$variants[ $phrase ]['max'] = max( $variants[ $phrase ]['max'], (int) $r->year );
		}
	}
	uasort( $variants, function ( $a, $b ) { return $b['n'] <=> $a['n']; } );
	ksort( $years );
	return array( 'rows' => $rows, 'total' => count( $rows ), 'variants' => array_slice( $variants, 0, 6, true ), 'years' => $years, 'terms' => $terms );
}

/** Highlight the terms in an escaped paragraph. */
function apt_alf_highlight( $text, $terms ) {
	$html = esc_html( $text );
	foreach ( $terms as $t ) {
		$html = preg_replace( '/(?<![\p{L}\p{N}])(' . preg_quote( esc_html( $t ), '/' ) . ')/iu', '<mark>$1</mark>', $html );
	}
	return $html;
}

/**
 * The quote shown for a result: the whole paragraph when it is short;
 * otherwise only the sentences with the search words ("…" between them),
 * with the whole paragraph one click away. Copying always takes it whole.
 */
function apt_alf_quote_html( $text, $terms ) {
	if ( mb_strlen( $text ) <= 700 ) {
		return '<blockquote>' . apt_alf_highlight( $text, $terms ) . '</blockquote>';
	}
	$keep = array();
	foreach ( preg_split( '/(?<=[.;])\s+(?=[A-Z(\d])/u', $text ) as $sentence ) {
		foreach ( $terms as $t ) {
			if ( false !== mb_stripos( $sentence, $t ) ) {
				$keep[] = $sentence;
				break;
			}
		}
	}
	if ( ! $keep ) {
		return '<blockquote>' . apt_alf_highlight( $text, $terms ) . '</blockquote>';
	}
	$excerpt = implode( ' … ', array_slice( $keep, 0, 3 ) );
	$lead    = 0 === mb_strpos( $text, $keep[0] ) ? '' : '… ';
	return '<blockquote>' . apt_alf_highlight( $lead . $excerpt . ' …', $terms ) . '</blockquote>'
		. '<details class="apt-alf-whole"><summary>' . esc_html__( 'Show the whole paragraph', 'akseki-vi' ) . '</summary><blockquote>' . apt_alf_highlight( $text, $terms ) . '</blockquote></details>';
}

/** Where a row's document opens (at its page for PDFs). */
function apt_alf_link( $r ) {
	if ( 'limited' === $r->source ) {
		$url = function_exists( 'apt_limited_gate_url' ) ? apt_limited_gate_url( $r->file ) : '';
	} elseif ( 'pdf' === $r->source ) {
		$up  = wp_upload_dir( null, false );
		$url = trailingslashit( $up['baseurl'] ) . implode( '/', array_map( 'rawurlencode', explode( '/', str_replace( '\\', '/', $r->file ) ) ) );
	} else {
		$url = get_permalink( $r->post_id );
	}
	return $url && $r->page ? $url . '#page=' . (int) $r->page : $url;
}

/** The citation of a row, as plain text. */
function apt_alf_citation( $r ) {
	$date = $r->day ? wp_date( 'j F Y', strtotime( $r->day ) ) : ( $r->year ? (string) $r->year : '' );
	$bits = array( $r->title );
	if ( $r->short && $r->short !== $r->title ) {
		$bits[] = $r->short;
	}
	if ( $date ) {
		$bits[] = $date;
	}
	if ( '' !== $r->para ) {
		$bits[] = 'para ' . $r->para;
	} elseif ( $r->page ) {
		$bits[] = 'p. ' . $r->page;
	}
	return implode( ', ', $bits ) . '. ' . apt_alf_link( $r ) . ( $r->limited ? ' [Limited Access]' : '' );
}

/* ---------- The finder (in the Members page) ---------- */

function apt_alf_finder_html() {
	// phpcs:disable WordPress.Security.NonceVerification -- read-only search
	$q       = isset( $_GET['aq'] ) ? sanitize_text_field( wp_unslash( $_GET['aq'] ) ) : '';
	$level   = isset( $_GET['al'] ) ? sanitize_text_field( wp_unslash( $_GET['al'] ) ) : '';
	$from    = isset( $_GET['af'] ) ? absint( $_GET['af'] ) : 0;
	$to      = isset( $_GET['at'] ) ? absint( $_GET['at'] ) : 0;
	$limited = isset( $_GET['aq'] ) ? ! empty( $_GET['alim'] ) : true; // ticked by default
	// phpcs:enable
	global $wpdb;
	$count  = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . apt_alf_table() ); // phpcs:ignore WordPress.DB
	$levels = $wpdb->get_col( 'SELECT DISTINCT level FROM ' . apt_alf_table() . " WHERE level <> '' ORDER BY level" ); // phpcs:ignore WordPress.DB
	$years  = $wpdb->get_col( 'SELECT DISTINCT year FROM ' . apt_alf_table() . ' WHERE year > 0 ORDER BY year DESC' ); // phpcs:ignore WordPress.DB
	$yopt   = function ( $sel ) use ( $years ) {
		$o = '<option value="">' . esc_html__( 'Any', 'akseki-vi' ) . '</option>';
		foreach ( $years as $y ) {
			$o .= '<option' . selected( (int) $sel, (int) $y, false ) . '>' . (int) $y . '</option>';
		}
		return $o;
	};

	$out  = '<section class="apt-mem-card apt-alf" id="agreed-language"><h2>' . esc_html__( 'Agreed Language', 'akseki-vi' ) . '</h2>';
	$out .= '<p class="apt-alf-lead">' . esc_html__( 'Find wording already agreed in ASEAN Plus Three statements and documents, paragraph by paragraph, with a ready citation. Use quotes for an exact phrase: "consistent with WTO rules".', 'akseki-vi' ) . '</p>';
	if ( ! $count ) {
		return $out . apt_mem_notice( __( 'The index is being built. Please come back a little later.', 'akseki-vi' ), 'info' ) . '</section>';
	}
	$out .= '<form method="get" action="' . esc_url( apt_mem_page_url() ) . '#agreed-language" class="apt-alf-form" role="search">'
		. '<p class="apt-alf-q"><label for="aq">' . esc_html__( 'Words or phrase', 'akseki-vi' ) . '</label><input id="aq" name="aq" value="' . esc_attr( $q ) . '" required></p>'
		. '<p><label for="al">' . esc_html__( 'Level', 'akseki-vi' ) . '</label><select id="al" name="al"><option value="">' . esc_html__( 'All levels', 'akseki-vi' ) . '</option>';
	foreach ( $levels as $l ) {
		$out .= '<option' . selected( $level, $l, false ) . '>' . esc_html( $l ) . '</option>';
	}
	$out .= '</select></p><p><label for="af">' . esc_html__( 'From', 'akseki-vi' ) . '</label><select id="af" name="af">' . $yopt( $from ) . '</select></p>'
		. '<p><label for="at">' . esc_html__( 'To', 'akseki-vi' ) . '</label><select id="at" name="at">' . $yopt( $to ) . '</select></p>'
		. '<p><button class="apt-mem-btn" type="submit">' . esc_html__( 'Search', 'akseki-vi' ) . '</button></p>'
		. '<p class="apt-alf-lim"><label><input type="checkbox" name="alim" value="1"' . checked( $limited, true, false ) . '> ' . esc_html__( 'Include Limited Access documents', 'akseki-vi' ) . '</label></p></form>';

	if ( '' === $q ) {
		return $out . '</section>';
	}
	$res = apt_alf_search( array( 'q' => $q, 'level' => $level, 'from' => $from, 'to' => $to, 'limited' => $limited ) );
	$lim = count( array_filter( $res['rows'], function ( $r ) { return (int) $r->limited; } ) );
	if ( $lim && function_exists( 'apt_mem_log' ) ) {
		apt_mem_log( 'search: ' . $q . ' (' . $lim . ' Limited Access paragraphs)' );
	}
	if ( ! $res['total'] ) {
		return $out . '<p class="apt-alf-count">' . esc_html__( 'No paragraphs found. Try fewer words, or a shorter phrase.', 'akseki-vi' ) . '</p></section>';
	}
	$out .= '<div class="apt-alf-cols"><div><p class="apt-alf-count"><strong>' . esc_html( sprintf( /* translators: %d: count */ _n( '%d paragraph', '%d paragraphs', $res['total'], 'akseki-vi' ), $res['total'] ) ) . '</strong>'
		. ( $lim ? esc_html( sprintf( /* translators: %d: count */ __( ', of which %d in Limited Access documents', 'akseki-vi' ), $lim ) ) : '' ) . esc_html__( ', newest first.', 'akseki-vi' ) . '</p>';
	foreach ( array_slice( $res['rows'], 0, 60 ) as $r ) {
		$cite  = apt_alf_citation( $r );
		$date  = $r->day ? wp_date( 'j F Y', strtotime( $r->day ) ) : ( $r->year ? (string) $r->year : '' );
		$where = '' !== $r->para ? sprintf( /* translators: %s: paragraph number */ __( 'para %s', 'akseki-vi' ), $r->para ) : ( $r->page ? sprintf( /* translators: %d: page */ __( 'page %d', 'akseki-vi' ), $r->page ) : '' );
		$out  .= '<article class="apt-alf-hit' . ( $r->limited ? ' is-limited' : '' ) . '" data-quote="' . esc_attr( $r->text ) . '" data-cite="' . esc_attr( $cite ) . '" data-limited="' . (int) $r->limited . '">'
			. apt_alf_quote_html( $r->text, $res['terms'] )
			. '<p class="apt-alf-src">' . ( $r->level ? '<span class="apt-alf-level">' . ( $r->limited ? '🔒 ' : '' ) . esc_html( $r->level ) . '</span> ' : '' )
			. ( $r->short && $r->short !== $r->title ? '<strong>' . esc_html( $r->short ) . '</strong> · ' : '' ) . esc_html( $r->title ) . ( $date ? ' · ' . esc_html( $date ) : '' ) . ( $where ? ' · ' . esc_html( $where ) : '' ) . '</p>'
			. '<p class="apt-alf-acts"><button type="button" class="apt-alf-btn" data-act="copy">' . esc_html__( 'Copy with citation', 'akseki-vi' ) . '</button>'
			. '<button type="button" class="apt-alf-btn is-ghost" data-act="add">' . esc_html__( 'Add to basket', 'akseki-vi' ) . '</button>'
			. '<a class="apt-alf-btn is-ghost" href="' . esc_url( apt_alf_link( $r ) ) . '" target="_blank" rel="noopener">' . esc_html( $r->page ? sprintf( /* translators: %d: page */ __( 'Open at page %d', 'akseki-vi' ), $r->page ) : __( 'Open', 'akseki-vi' ) ) . '</a></p></article>';
	}
	if ( $res['total'] > 60 ) {
		$out .= '<p class="apt-alf-count">' . esc_html__( 'Showing the 60 newest. Narrow the search (level, years, or an exact phrase) to see others.', 'akseki-vi' ) . '</p>';
	}
	$out .= '</div><aside class="apt-alf-side">';
	if ( $res['variants'] ) {
		$out .= '<div class="apt-alf-box"><h3>' . esc_html__( 'Wording over time', 'akseki-vi' ) . '</h3><ul class="apt-alf-variants">';
		foreach ( $res['variants'] as $phrase => $v ) {
			$out .= '<li><a href="' . esc_url( add_query_arg( array( 'aq' => '"' . $phrase . '"', 'alim' => $limited ? 1 : 0 ), apt_mem_page_url() ) . '#agreed-language' ) . '">“' . esc_html( $v['label'] ) . '”</a><span class="apt-alf-n">' . (int) $v['n'] . '</span><span class="apt-alf-yrs">' . esc_html( $v['min'] === $v['max'] ? (string) $v['min'] : $v['min'] . '–' . $v['max'] ) . '</span></li>';
		}
		$out .= '</ul>';
		$max  = max( $res['years'] );
		$out .= '<div class="apt-alf-bars" aria-hidden="true">';
		foreach ( $res['years'] as $y => $n ) {
			$out .= '<div title="' . (int) $y . ': ' . (int) $n . '"><i style="height:' . max( 2, (int) round( 70 * $n / $max ) ) . 'px"></i><small>' . esc_html( "'" . substr( (string) $y, 2 ) ) . '</small></div>';
		}
		$out .= '</div><p class="apt-alf-note">' . esc_html__( 'Paragraphs per year.', 'akseki-vi' ) . '</p></div>';
	}
	$out .= '<div class="apt-alf-box apt-alf-basket" hidden><h3>' . esc_html__( 'Drafting basket', 'akseki-vi' ) . ' (<span class="apt-alf-bn">0</span>)</h3><ol class="apt-alf-blist"></ol>'
		. '<p><button type="button" class="apt-alf-btn" data-act="word">' . esc_html__( 'Download as Word', 'akseki-vi' ) . '</button> <button type="button" class="apt-alf-btn is-ghost" data-act="clear">' . esc_html__( 'Empty', 'akseki-vi' ) . '</button></p>'
		. '<p class="apt-alf-note">' . esc_html__( 'Kept in this browser only.', 'akseki-vi' ) . '</p></div>';
	$out .= '</aside></div></section>';
	return $out . apt_alf_script();
}

/** The buttons: copy, basket, Word download (the basket is kept in this browser). */
function apt_alf_script() {
	$key = 'apt_alf_basket_' . get_current_user_id();
	ob_start();
	?>
	<script>
	( function () {
		var KEY = <?php echo wp_json_encode( $key ); ?>, root = document.getElementById( 'agreed-language' );
		if ( ! root ) { return; }
		var box = root.querySelector( '.apt-alf-basket' ), list = root.querySelector( '.apt-alf-blist' ), n = root.querySelector( '.apt-alf-bn' );
		function load() { try { return JSON.parse( localStorage.getItem( KEY ) || '[]' ); } catch ( e ) { return []; } }
		function save( b ) { try { localStorage.setItem( KEY, JSON.stringify( b ) ); } catch ( e ) {} draw(); }
		function esc( s ) { var d = document.createElement( 'div' ); d.textContent = s; return d.innerHTML; }
		function draw() {
			var b = load();
			if ( ! box ) { return; }
			box.hidden = ! b.length; n.textContent = b.length;
			list.innerHTML = b.map( function ( it, i ) { return '<li>' + ( it.limited ? '🔒 ' : '' ) + esc( it.quote.slice( 0, 90 ) ) + '… <button type="button" data-rm="' + i + '" aria-label="Remove">×</button></li>'; } ).join( '' );
		}
		root.addEventListener( 'click', function ( e ) {
			var t = e.target.closest( 'button' ); if ( ! t ) { return; }
			var hit = t.closest( '.apt-alf-hit' ), act = t.getAttribute( 'data-act' );
			if ( act === 'copy' && hit ) {
				var text = '“' + hit.dataset.quote + '”\n' + hit.dataset.cite;
				( navigator.clipboard ? navigator.clipboard.writeText( text ) : Promise.reject() ).then( function () { t.textContent = 'Copied ✓'; setTimeout( function () { t.textContent = 'Copy with citation'; }, 1500 ); } ).catch( function () { window.prompt( 'Copy:', text ); } );
			} else if ( act === 'add' && hit ) {
				var b = load();
				if ( ! b.some( function ( it ) { return it.quote === hit.dataset.quote; } ) ) { b.push( { quote: hit.dataset.quote, cite: hit.dataset.cite, limited: hit.dataset.limited === '1' } ); save( b ); }
				t.textContent = 'In the basket ✓';
			} else if ( t.hasAttribute( 'data-rm' ) ) {
				var b2 = load(); b2.splice( +t.getAttribute( 'data-rm' ), 1 ); save( b2 );
			} else if ( act === 'clear' ) {
				if ( window.confirm( 'Empty the basket?' ) ) { save( [] ); }
			} else if ( act === 'word' ) {
				var items = load(), lim = items.some( function ( it ) { return it.limited; } );
				var html = '<html><head><meta charset="utf-8"><title>Agreed language</title></head><body style="font-family:Calibri,Arial,sans-serif;font-size:11pt">'
					+ '<h1 style="font-size:14pt">Agreed language</h1><p style="color:#666">Collected from the ASEAN Plus Three website on ' + new Date().toLocaleDateString( 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' } ) + '.</p>'
					+ ( lim ? '<p style="color:#98294b;font-weight:bold">Contains Limited Access material: for official use only.</p>' : '' )
					+ items.map( function ( it, i ) { return '<p style="margin:14pt 0 2pt">' + ( i + 1 ) + '. “' + esc( it.quote ) + '”</p><p style="margin:0 0 0 18pt;color:#555;font-size:9pt">' + esc( it.cite ) + '</p>'; } ).join( '' )
					+ '</body></html>';
				var a = document.createElement( 'a' );
				a.href = URL.createObjectURL( new Blob( [ '﻿', html ], { type: 'application/msword' } ) );
				a.download = 'agreed-language-' + new Date().toISOString().slice( 0, 10 ) + '.doc';
				document.body.appendChild( a ); a.click(); a.remove();
			}
		} );
		draw();
	} )();
	</script>
	<?php
	return ob_get_clean();
}

/* ---------- Admin: the index ---------- */

/** The "Agreed Language" box on Users → Members: index status and building. */
function apt_alf_admin_box() {
	global $wpdb;
	$q     = (array) get_option( 'apt_alf_queue', array() );
	$paras = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . apt_alf_table() ); // phpcs:ignore WordPress.DB
	$docs  = (int) $wpdb->get_var( 'SELECT COUNT(DISTINCT post_id) FROM ' . apt_alf_table() . ' WHERE limited = 0' ); // phpcs:ignore WordPress.DB
	$lim   = (int) $wpdb->get_var( 'SELECT COUNT(DISTINCT file) FROM ' . apt_alf_table() . ' WHERE limited = 1' ); // phpcs:ignore WordPress.DB
	$last  = (int) get_option( 'apt_alf_last' );
	$out   = '<h2>' . esc_html__( 'Agreed Language index', 'akseki-vi' ) . '</h2>';
	if ( ! apt_alf_pdf_reader_ready() ) {
		$out .= '<div class="notice notice-warning inline"><p>' . esc_html__( 'The APT PDF Search plugin is not active, so PDFs (including the Limited Access documents) are not indexed: only the posts\' own text. Activate it, then "Rebuild".', 'akseki-vi' ) . '</p></div>';
	}
	$out .= '<p>' . esc_html( sprintf( /* translators: 1: paragraphs, 2: posts, 3: files */ __( '%1$d paragraphs from %2$d statements and %3$d Limited Access documents.', 'akseki-vi' ), $paras, $docs, $lim ) )
		. ( $last ? ' ' . esc_html( sprintf( /* translators: %s: date */ __( 'Last worked on %s.', 'akseki-vi' ), wp_date( 'j M Y, H:i', $last ) ) ) : '' ) . '</p>';
	$skipped = (array) get_option( 'apt_alf_skipped', array() );
	if ( $skipped ) {
		$out .= '<details><summary>' . esc_html( sprintf( /* translators: %d: count */ _n( '%d document could not be read (skipped)', '%d documents could not be read (skipped)', count( $skipped ), 'akseki-vi' ), count( $skipped ) ) ) . '</summary><ul style="list-style:disc;margin-left:20px">';
		foreach ( array_reverse( $skipped ) as $sk ) {
			$out .= '<li>' . esc_html( $sk['item'] ) . ': ' . esc_html( $sk['why'] ) . '</li>';
		}
		$out .= '</ul><p class="description">' . esc_html__( 'Usually very large or scanned PDFs. The rest of the index is not affected.', 'akseki-vi' ) . '</p></details>';
	}
	if ( $q ) {
		$out .= '<p><strong>' . esc_html( sprintf( /* translators: %d: count */ __( '%d items waiting.', 'akseki-vi' ), count( $q ) ) ) . '</strong> '
			. esc_html__( 'It continues in the background every 10 minutes, or here now:', 'akseki-vi' ) . ' <a class="button button-primary" href="' . esc_url( apt_mem_admin_url( 'alf_run', array( 'tab' => 'alf' ) ) ) . '">' . esc_html__( 'Build now', 'akseki-vi' ) . '</a></p>';
	} else {
		$out .= '<p>' . esc_html__( 'Up to date. New and edited posts are added automatically.', 'akseki-vi' ) . ' <a class="button" href="' . esc_url( apt_mem_admin_url( 'alf_rebuild', array( 'tab' => 'alf' ) ) ) . '" onclick="return confirm(\'' . esc_js( __( 'Read every statement and PDF again? It takes a while (in short steps).', 'akseki-vi' ) ) . '\')">' . esc_html__( 'Rebuild', 'akseki-vi' ) . '</a></p>';
	}
	return $out;
}
