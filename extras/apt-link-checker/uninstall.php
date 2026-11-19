<?php
/**
 * Remove stored results and the background check when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'apt_link_checker_results' );
delete_option( 'apt_link_checker_progress' );
wp_clear_scheduled_hook( 'apt_lc_cron' );
