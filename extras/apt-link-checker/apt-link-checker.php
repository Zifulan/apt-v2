<?php
/**
 * Plugin Name:       APT Link Checker
 * Description:       Finds broken links, missing files and broken PDFs in published content and lists them for editors (Tools > Link checker). Re-checks weekly.
 * Version:           1.0.0
 * Requires at least: 5.5
 * Requires PHP:      7.4
 * Author:            ASEAN Plus Three website
 * License:           GPL-2.0-or-later
 * Text Domain:       apt-link-checker
 *
 * What it checks, in each published post and page:
 * - Files in this site's uploads folder: exist, not empty, and PDFs really are
 *   PDFs (checked on disk, no web requests).
 * - Other links to this site: resolve to published content or answer 200.
 * - Links to other websites: answer without an error (HEAD, then GET).
 * - Malformed addresses (e.g. HTML pasted into a link, spaces).
 * Results are visible to administrators only; nothing is changed in content.
 */

defined( 'ABSPATH' ) || exit;

define( 'APT_LC_RESULTS', 'apt_link_checker_results' ); // url => result
define( 'APT_LC_PROGRESS', 'apt_link_checker_progress' );
define( 'APT_LC_BATCH', 6 );          // posts per request
define( 'APT_LC_TIMEOUT', 8 );        // seconds per external request
define( 'APT_LC_RECHECK', WEEK_IN_SECONDS );

require_once __DIR__ . '/includes/scan.php';
require_once __DIR__ . '/includes/admin.php';
