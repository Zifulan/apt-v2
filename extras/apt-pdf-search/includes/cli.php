<?php
/**
 * WP-CLI: wp apt-pdf-search index [--force]
 */

defined( 'ABSPATH' ) || exit;

WP_CLI::add_command( 'apt-pdf-search index', function ( $args, $assoc ) {
	$force  = ! empty( $assoc['force'] );
	$offset = 0;
	$counts = array();
	do {
		$ids = apt_pdf_search_candidate_ids( $offset, 50 );
		foreach ( $ids as $id ) {
			$result            = apt_pdf_search_index_post( $id, $force );
			$counts[ $result ] = isset( $counts[ $result ] ) ? $counts[ $result ] + 1 : 1;
			WP_CLI::log( $id . ' ' . $result . ' ' . get_the_title( $id ) );
		}
		$offset += 50;
	} while ( count( $ids ) === 50 );
	WP_CLI::success( wp_json_encode( $counts ) );
} );
