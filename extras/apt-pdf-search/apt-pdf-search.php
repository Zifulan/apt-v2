<?php
/**
 * Plugin Name:       APT PDF Search
 * Description:       Lets the site search find statements by the text inside their attached PDFs. Reads PDFs on this server only; skips password-protected, private and limited-access content.
 * Version:           1.0.0
 * Requires at least: 5.5
 * Requires PHP:      7.4
 * Author:            ASEAN Plus Three website
 * License:           GPL-2.0-or-later
 * Text Domain:       apt-pdf-search
 *
 * How it works
 * - When a post or page is saved, the PDFs it links to that are stored in this
 *   site's uploads folder are read (smalot/pdfparser, bundled in vendor/) and
 *   their text is saved in the post's meta field _apt_pdf_text.
 * - The front-end search also matches that text, so a statement is found by a
 *   phrase that only appears in its PDF. Results are still the statement pages.
 * - Tools > PDF search indexes the existing posts in small batches.
 *
 * Privacy
 * - Only published content without a password is indexed. Anything whose slug,
 *   title, category or parent page contains "limited" (filter:
 *   apt_pdf_search_excluded_keywords), and any PDF whose path contains it, is
 *   skipped, so text from the Limited Access Documents never reaches search.
 * - PDFs are read on this server; nothing is sent elsewhere.
 */

defined( 'ABSPATH' ) || exit;

define( 'APT_PDF_SEARCH_TEXT', '_apt_pdf_text' );   // extracted text of all linked PDFs
define( 'APT_PDF_SEARCH_STATE', '_apt_pdf_state' ); // per-PDF signature, length, error
define( 'APT_PDF_SEARCH_MAX_FILE', 25 * 1024 * 1024 ); // skip PDFs larger than this
define( 'APT_PDF_SEARCH_MAX_TEXT', 300000 );        // characters kept per post

require_once __DIR__ . '/includes/indexer.php';
require_once __DIR__ . '/includes/search.php';
require_once __DIR__ . '/includes/admin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/includes/cli.php';
}
