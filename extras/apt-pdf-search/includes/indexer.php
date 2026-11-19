<?php
/**
 * Finding, reading and storing the text of the PDFs a post links to.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Words that mark restricted content. A post, its parent pages, its categories
 * or a PDF path containing one of these is never indexed.
 *
 * @return string[]
 */
function apt_pdf_search_excluded_keywords() {
	return (array) apply_filters( 'apt_pdf_search_excluded_keywords', array( 'limited' ) );
}

/**
 * Whether a string contains one of the excluded keywords (case-insensitive).
 */
function apt_pdf_search_has_excluded_keyword( $text ) {
	foreach ( apt_pdf_search_excluded_keywords() as $word ) {
		if ( '' !== $word && false !== stripos( (string) $text, $word ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Whether a post's PDFs may be indexed: published, no password, a public post
 * type, and nothing restricted about it or its parents and categories.
 */
function apt_pdf_search_is_indexable( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	$ok = 'publish' === $post->post_status
		&& '' === $post->post_password
		&& 'attachment' !== $post->post_type
		&& is_post_type_viewable( $post->post_type );

	if ( $ok ) {
		$checks = array( $post->post_name, $post->post_title );
		foreach ( get_post_ancestors( $post ) as $ancestor_id ) {
			$ancestor = get_post( $ancestor_id );
			if ( $ancestor ) {
				// A child of a protected or private page is restricted too.
				if ( '' !== $ancestor->post_password || 'publish' !== $ancestor->post_status ) {
					$ok = false;
					break;
				}
				$checks[] = $ancestor->post_name;
				$checks[] = $ancestor->post_title;
			}
		}
		if ( $ok && is_object_in_taxonomy( $post->post_type, 'category' ) ) {
			foreach ( get_the_category( $post->ID ) as $cat ) {
				$checks[] = $cat->slug;
				$checks[] = $cat->name;
			}
		}
		if ( $ok ) {
			foreach ( $checks as $text ) {
				if ( apt_pdf_search_has_excluded_keyword( $text ) ) {
					$ok = false;
					break;
				}
			}
		}
	}

	return (bool) apply_filters( 'apt_pdf_search_is_indexable', $ok, $post );
}

/**
 * Why a post is not indexable, in plain words, or '' when it is.
 */
function apt_pdf_search_why_not_indexable( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 'Post not found.';
	}
	if ( 'publish' !== $post->post_status ) {
		return 'Not published (status: ' . $post->post_status . ').';
	}
	if ( '' !== $post->post_password ) {
		return 'Password-protected.';
	}
	if ( 'attachment' === $post->post_type || ! is_post_type_viewable( $post->post_type ) ) {
		return 'Post type "' . $post->post_type . '" is not public.';
	}
	foreach ( get_post_ancestors( $post ) as $ancestor_id ) {
		$ancestor = get_post( $ancestor_id );
		if ( $ancestor && ( '' !== $ancestor->post_password || 'publish' !== $ancestor->post_status ) ) {
			return 'Inside a protected or unpublished parent page ("' . $ancestor->post_title . '").';
		}
		if ( $ancestor && ( apt_pdf_search_has_excluded_keyword( $ancestor->post_name ) || apt_pdf_search_has_excluded_keyword( $ancestor->post_title ) ) ) {
			return 'Parent page "' . $ancestor->post_title . '" is marked as restricted.';
		}
	}
	if ( apt_pdf_search_has_excluded_keyword( $post->post_name ) || apt_pdf_search_has_excluded_keyword( $post->post_title ) ) {
		return 'Slug or title is marked as restricted (' . implode( ', ', apt_pdf_search_excluded_keywords() ) . ').';
	}
	if ( is_object_in_taxonomy( $post->post_type, 'category' ) ) {
		foreach ( get_the_category( $post->ID ) as $cat ) {
			if ( apt_pdf_search_has_excluded_keyword( $cat->slug ) || apt_pdf_search_has_excluded_keyword( $cat->name ) ) {
				return 'In a restricted category ("' . $cat->name . '").';
			}
		}
	}
	return apt_pdf_search_is_indexable( $post ) ? '' : 'Excluded by the apt_pdf_search_is_indexable filter.';
}

/**
 * Every .pdf link in a post's content with what happened to it, for the
 * "Check a statement" tool: URL => reason it is skipped ('' when used).
 */
function apt_pdf_search_explain_links( $post ) {
	$post = get_post( $post );
	$used = $post ? apt_pdf_search_find_pdfs( $post ) : array();
	$out  = array();
	if ( ! $post ) {
		return $out;
	}
	$uploads = wp_upload_dir( null, false );
	$base    = (string) wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );
	foreach ( apt_pdf_search_candidate_urls( $post ) as $url ) {
		if ( isset( $used[ $url ] ) ) {
			$out[ $url ] = '';
			continue;
		}
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$path = rawurldecode( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		if ( $host && strcasecmp( preg_replace( '/^www\./i', '', $host ), preg_replace( '/^www\./i', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) !== 0 ) {
			$out[ $url ] = 'On another website (' . $host . ').';
		} elseif ( apt_pdf_search_has_excluded_keyword( $path ) ) {
			$out[ $url ] = 'Path is marked as restricted.';
		} elseif ( '' === $base || 0 !== strpos( $path, $base . '/' ) ) {
			$out[ $url ] = 'Not under the uploads URL (' . $uploads['baseurl'] . ').';
		} else {
			$out[ $url ] = 'File not found on the server at ' . $uploads['basedir'] . substr( $path, strlen( $base ) ) . '.';
		}
	}
	return $out;
}

/**
 * Every PDF address a post refers to, however it is attached: plain links,
 * shortcode attributes (e.g. [pdf-embedder url="…"]), block settings (saved
 * as https:\/\/…), file blocks by attachment ID, and PDFs uploaded to the post.
 *
 * @return string[] URLs (absolute or site-relative), unique.
 */
function apt_pdf_search_candidate_urls( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$content = (string) apply_filters( 'apt_pdf_search_source_html', $post->post_content, $post );
	$content = html_entity_decode( str_replace( '\\/', '/', $content ), ENT_QUOTES, 'UTF-8' );
	$urls    = array();

	// Absolute or protocol-relative addresses ending in .pdf.
	if ( preg_match_all( '#(?:https?:)?//[^\s"\'<>\[\]()]+?\.pdf(?=[\s"\'<>\[\]()?\#&]|$)#i', $content, $m ) ) {
		$urls = array_merge( $urls, $m[0] );
	}
	// Site-relative addresses (/wp-content/uploads/…pdf) after a quote, = or space.
	if ( preg_match_all( '#(?<=["\'=\s(])/(?!/)[^\s"\'<>\[\]()]+?\.pdf(?=[\s"\'<>\[\]()?\#&]|$)#i', $content, $m ) ) {
		$urls = array_merge( $urls, $m[0] );
	}
	// File / PDF blocks that store the attachment ID.
	if ( preg_match_all( '#<!-- wp:[\w/-]*(?:file|pdf)[\w/-]* (\{.*?\}) #i', $content, $m ) ) {
		foreach ( $m[1] as $json ) {
			$attrs = json_decode( $json, true );
			if ( ! empty( $attrs['id'] ) && 'application/pdf' === get_post_mime_type( (int) $attrs['id'] ) ) {
				$urls[] = wp_get_attachment_url( (int) $attrs['id'] );
			}
		}
	}
	// PDFs uploaded to this post (Media "Uploaded to" this post).
	foreach ( get_attached_media( 'application/pdf', $post->ID ) as $attachment ) {
		$urls[] = wp_get_attachment_url( $attachment->ID );
	}

	$urls = array_values( array_unique( array_filter( array_map( function ( $u ) {
		$u = (string) $u;
		return 0 === strpos( $u, '//' ) ? 'https:' . $u : $u;
	}, $urls ) ) ) );
	return (array) apply_filters( 'apt_pdf_search_candidate_urls', $urls, $post );
}

/**
 * The PDFs a post refers to that live in this site's uploads folder.
 *
 * @return array URL => absolute file path.
 */
function apt_pdf_search_find_pdfs( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$m = array( 1 => apt_pdf_search_candidate_urls( $post ) );
	if ( ! $m[1] ) {
		return array();
	}

	$uploads   = wp_upload_dir( null, false );
	$base_path = wp_parse_url( $uploads['baseurl'], PHP_URL_PATH ); // e.g. /wp-content/uploads
	$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
	$base_dir  = realpath( $uploads['basedir'] );
	$found     = array();

	foreach ( $m[1] as $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! $path || ( $host && strcasecmp( preg_replace( '/^www\./i', '', $host ), preg_replace( '/^www\./i', '', (string) $site_host ) ) !== 0 ) ) {
			continue; // another site's PDF
		}
		$path = rawurldecode( $path );
		if ( ! $base_path || 0 !== strpos( $path, $base_path . '/' ) || apt_pdf_search_has_excluded_keyword( $path ) ) {
			continue;
		}
		$file = realpath( $uploads['basedir'] . substr( $path, strlen( $base_path ) ) );
		// Must exist, be a file, and stay inside the uploads folder.
		if ( ! $file || ! $base_dir || 0 !== strpos( $file, $base_dir . DIRECTORY_SEPARATOR ) || ! is_file( $file ) ) {
			continue;
		}
		$found[ $url ] = $file;
	}
	return $found;
}

/**
 * Plain text of one PDF, or a WP_Error.
 */
function apt_pdf_search_extract( $file ) {
	$size = @filesize( $file );
	if ( false === $size ) {
		return new WP_Error( 'unreadable', 'File cannot be read.' );
	}
	if ( $size > APT_PDF_SEARCH_MAX_FILE ) {
		return new WP_Error( 'too_large', 'File is larger than the limit.' );
	}
	if ( ! class_exists( '\Smalot\PdfParser\Parser' ) ) {
		require_once dirname( __DIR__ ) . '/vendor/autoload.php';
	}
	$head = (string) @file_get_contents( $file, false, null, 0, 1024 );
	if ( false === strpos( $head, '%PDF-' ) ) {
		return new WP_Error( 'not_pdf', 'Not a PDF file (empty, or a web page saved under a .pdf name).' );
	}

	wp_raise_memory_limit( 'admin' );
	$error = '';
	try {
		$parser = new \Smalot\PdfParser\Parser();
		$text   = apt_pdf_search_clean_text( $parser->parseFile( $file )->getText() );
	} catch ( \Throwable $e ) {
		$text  = '';
		$error = $e->getMessage();
	}
	// Some PDFs the bundled parser cannot read are readable by poppler's
	// pdftotext; use it when the server has it (many shared hosts do not).
	if ( mb_strlen( $text ) < 50 ) {
		$fallback = apt_pdf_search_pdftotext( $file );
		if ( mb_strlen( $fallback ) > mb_strlen( $text ) ) {
			$text = $fallback;
		}
	}
	if ( mb_strlen( $text ) < 50 ) {
		return new WP_Error( 'no_text', $error ? $error : 'No text found (probably a scanned image; it would need OCR).' );
	}
	return $text;
}

/** Drop control characters, collapse whitespace, join "food -security". */
function apt_pdf_search_clean_text( $text ) {
	$text = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', (string) $text );
	$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	// PDF layout often leaves a space before a hyphen ("food -security"); join
	// it back so hyphenated terms match. Only letter-space-hyphen-letter.
	return (string) preg_replace( '/(\p{L}) -(\p{L})/u', '$1-$2', $text );
}

/** Text from poppler's pdftotext, or '' when it is unavailable or fails. */
function apt_pdf_search_pdftotext( $file ) {
	if ( ! function_exists( 'exec' ) || in_array( 'exec', array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) ), true ) ) {
		return '';
	}
	$bin = (string) apply_filters( 'apt_pdf_search_pdftotext_path', '' );
	if ( '' === $bin ) {
		foreach ( array( '/usr/bin/pdftotext', '/usr/local/bin/pdftotext', '/opt/homebrew/bin/pdftotext' ) as $candidate ) {
			if ( @is_executable( $candidate ) ) {
				$bin = $candidate;
				break;
			}
		}
	}
	if ( '' === $bin ) {
		return '';
	}
	$out  = array();
	$code = 1;
	@exec( escapeshellarg( $bin ) . ' -enc UTF-8 -q ' . escapeshellarg( $file ) . ' - 2>/dev/null', $out, $code );
	return 0 === $code ? apt_pdf_search_clean_text( implode( "\n", $out ) ) : '';
}

/**
 * Index (or clear) one post. Unchanged PDFs are not read again unless $force.
 *
 * @return string excluded|none|unchanged|indexed|failed
 */
function apt_pdf_search_index_post( $post_id, $force = false ) {
	$post = get_post( $post_id );
	if ( ! $post || ! apt_pdf_search_is_indexable( $post ) ) {
		apt_pdf_search_clear_post( $post_id );
		return 'excluded';
	}
	$pdfs = apt_pdf_search_find_pdfs( $post );
	if ( ! $pdfs ) {
		apt_pdf_search_clear_post( $post_id );
		return 'none';
	}

	$old   = get_post_meta( $post->ID, APT_PDF_SEARCH_STATE, true );
	$old   = is_array( $old ) ? $old : array();
	$state = array();
	foreach ( $pdfs as $url => $file ) {
		$state[ $url ] = array( 'sig' => filesize( $file ) . '-' . filemtime( $file ) );
	}
	$same = ! $force && array_keys( $old ) === array_keys( $state );
	if ( $same ) {
		foreach ( $state as $url => $s ) {
			if ( ! isset( $old[ $url ]['sig'] ) || $old[ $url ]['sig'] !== $s['sig'] ) {
				$same = false;
				break;
			}
		}
	}
	if ( $same ) {
		return 'unchanged';
	}

	$texts = array();
	foreach ( $pdfs as $url => $file ) {
		$text = apt_pdf_search_extract( $file );
		if ( is_wp_error( $text ) ) {
			$state[ $url ]['error'] = $text->get_error_message();
			continue;
		}
		$state[ $url ]['chars'] = mb_strlen( $text );
		$texts[]                = $text;
	}

	update_post_meta( $post->ID, APT_PDF_SEARCH_STATE, $state );
	if ( $texts ) {
		$all = mb_substr( implode( "\n\n", $texts ), 0, APT_PDF_SEARCH_MAX_TEXT );
		update_post_meta( $post->ID, APT_PDF_SEARCH_TEXT, wp_slash( $all ) );
		return 'indexed';
	}
	delete_post_meta( $post->ID, APT_PDF_SEARCH_TEXT );
	return 'failed';
}

/** Remove a post's PDF text (e.g. it became private or password-protected). */
function apt_pdf_search_clear_post( $post_id ) {
	delete_post_meta( $post_id, APT_PDF_SEARCH_TEXT );
	delete_post_meta( $post_id, APT_PDF_SEARCH_STATE );
}

/** Posts and pages whose content links to a PDF, newest first (batchable). */
function apt_pdf_search_candidate_ids( $offset = 0, $limit = 10 ) {
	global $wpdb;
	return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
		apt_pdf_search_candidate_sql( 'p.ID' ) . ' ORDER BY p.ID DESC LIMIT %d OFFSET %d',
		'%' . $wpdb->esc_like( '.pdf' ) . '%',
		'%' . $wpdb->esc_like( 'wp:file' ) . '%',
		$limit,
		$offset
	) ) );
}

/** Number of posts and pages the indexer looks at. */
function apt_pdf_search_candidate_count() {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare(
		'SELECT COUNT(*) FROM (' . apt_pdf_search_candidate_sql( 'p.ID' ) . ') c',
		'%' . $wpdb->esc_like( '.pdf' ) . '%',
		'%' . $wpdb->esc_like( 'wp:file' ) . '%'
	) );
}

/**
 * Posts and pages that may refer to a PDF: ".pdf" or a file block in their
 * content, or a PDF uploaded to them. Placeholders: two LIKE patterns.
 */
function apt_pdf_search_candidate_sql( $select ) {
	global $wpdb;
	return "SELECT DISTINCT {$select} FROM {$wpdb->posts} p
		WHERE p.post_type NOT IN ('attachment','revision','nav_menu_item','wp_block','wp_template','wp_template_part','wp_navigation')
		  AND p.post_status IN ('publish','private','future','draft','pending')
		  AND ( p.post_content LIKE %s OR p.post_content LIKE %s
		        OR EXISTS ( SELECT 1 FROM {$wpdb->posts} a
		                    WHERE a.post_parent = p.ID AND a.post_type = 'attachment' AND a.post_mime_type = 'application/pdf' ) )";
}

// Re-index whenever a post is saved; clears it if it is now restricted.
add_action( 'save_post', function ( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'attachment' === $post->post_type ) {
		return;
	}
	apt_pdf_search_index_post( $post_id );
}, 20, 2 );
