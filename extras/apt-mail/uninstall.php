<?php
/**
 * Remove the plugin's settings when it is deleted. The App Password lives in
 * wp-config.php: remove the APT_SMTP_PASSWORD line there by hand.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'apt_mail_settings' );
delete_option( 'apt_mail_last_error' );
delete_option( 'apt_mail_last_ok' );
