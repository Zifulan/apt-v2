<?php
/**
 * Remove the stored PDF text when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_post_meta_by_key( '_apt_pdf_text' );
delete_post_meta_by_key( '_apt_pdf_state' );
