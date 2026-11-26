<?php
/**
 * A "Theme" dropdown for categories (Posts → Categories).
 *
 * Which theme a meeting category belongs to is stored on the theme pages
 * (the child pages of "Themes", page 5733): each has a "tpc_topics" custom
 * field, a comma-separated list of category slugs. Statements, news cards,
 * breadcrumbs and the theme pages all read it from there
 * (akseki_get_theme_from_id(), template-parts/page-themes.php).
 *
 * This file only makes it easier to edit: pick the theme on the category's
 * screen, and the slug is removed from every other theme page's list and
 * added to the chosen one's. A category is never in two themes. If a
 * category's slug is renamed, the lists are updated too (before, that broke
 * the link silently). The data stays where it was.
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The "Themes" parent page. */
const APT_THEMES_PAGE = 5733;

/** The theme pages, by title. */
function apt_theme_pages() {
	return get_pages(
		array(
			'parent'      => APT_THEMES_PAGE,
			'post_status' => array( 'publish', 'private', 'draft' ),
			'sort_column' => 'post_title',
		)
	);
}

/** A theme page's category slugs. */
function apt_theme_topics( $page_id ) {
	$raw = (string) get_post_meta( $page_id, 'tpc_topics', true );
	return array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ), 'strlen' ) );
}

/** The theme page a category slug belongs to (0 = none). */
function apt_category_theme_id( $slug ) {
	$found = 0;
	foreach ( apt_theme_pages() as $page ) {
		if ( in_array( $slug, apt_theme_topics( $page->ID ), true ) ) {
			$found = $page->ID; // the last one wins, as in akseki_get_theme_from_id()
		}
	}
	return $found;
}

/**
 * Put a category slug in one theme page's list and take it out of the
 * others. $theme_id 0 takes it out everywhere. $replace_slug: the category's
 * old slug, to replace (rename) instead of add.
 */
function apt_set_category_theme( $slug, $theme_id, $replace_slug = '' ) {
	foreach ( apt_theme_pages() as $page ) {
		if ( ! current_user_can( 'edit_post', $page->ID ) ) {
			continue;
		}
		$before = apt_theme_topics( $page->ID );
		$after  = array_values( array_diff( $before, array( $slug, $replace_slug ) ) );
		if ( (int) $page->ID === (int) $theme_id ) {
			$after[] = $slug;
		}
		if ( $after !== $before ) {
			update_post_meta( $page->ID, 'tpc_topics', implode( ',', $after ) );
		}
	}
}

/** The dropdown's options. */
function apt_theme_options_html( $current ) {
	$out = '<option value="0">' . esc_html__( 'None', 'akseki-vi' ) . '</option>';
	foreach ( apt_theme_pages() as $page ) {
		$out .= '<option value="' . (int) $page->ID . '"' . selected( (int) $current, (int) $page->ID, false ) . '>' . esc_html( get_the_title( $page ) ) . '</option>';
	}
	return $out;
}

$apt_theme_help = __( 'The theme page this meeting belongs to. Shown as "Theme" on its statements and news cards, and lists the meeting on that theme page.', 'akseki-vi' );

// Add-category form.
add_action( 'category_add_form_fields', function () use ( $apt_theme_help ) {
	wp_nonce_field( 'apt_category_theme', 'apt_category_theme_nonce' );
	echo '<div class="form-field"><label for="apt-theme">' . esc_html__( 'Theme', 'akseki-vi' ) . '</label>'
		. '<select name="apt_theme" id="apt-theme">' . apt_theme_options_html( 0 ) . '</select>' // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside
		. '<p>' . esc_html( $apt_theme_help ) . '</p></div>';
} );

// Edit-category screen.
add_action( 'category_edit_form_fields', function ( $term ) use ( $apt_theme_help ) {
	wp_nonce_field( 'apt_category_theme', 'apt_category_theme_nonce' );
	echo '<tr class="form-field"><th scope="row"><label for="apt-theme">' . esc_html__( 'Theme', 'akseki-vi' ) . '</label></th><td>'
		. '<select name="apt_theme" id="apt-theme">' . apt_theme_options_html( apt_category_theme_id( $term->slug ) ) . '</select>' // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside
		. '<p class="description">' . esc_html( $apt_theme_help ) . '</p></td></tr>';
} );

// Remember a category's slug before it is saved, to follow a rename.
add_action( 'edit_terms', function ( $term_id, $taxonomy ) {
	if ( 'category' === $taxonomy ) {
		$term = get_term( $term_id, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$GLOBALS['apt_old_category_slugs'][ $term_id ] = $term->slug;
		}
	}
}, 10, 2 );

/** After a category is added or saved: follow a slug rename, then apply the dropdown. */
function apt_save_category_theme( $term_id ) {
	$term = get_term( $term_id, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}
	$old = isset( $GLOBALS['apt_old_category_slugs'][ $term_id ] ) ? $GLOBALS['apt_old_category_slugs'][ $term_id ] : '';
	if ( $old && $old !== $term->slug ) {
		$theme = apt_category_theme_id( $old );
		if ( $theme ) {
			apt_set_category_theme( $term->slug, $theme, $old );
		}
	}
	// The dropdown is only on the full forms, not in Quick Edit.
	if ( isset( $_POST['apt_theme'], $_POST['apt_category_theme_nonce'] )
		&& wp_verify_nonce( sanitize_key( wp_unslash( $_POST['apt_category_theme_nonce'] ) ), 'apt_category_theme' ) ) {
		$chosen = absint( $_POST['apt_theme'] );
		if ( $chosen && APT_THEMES_PAGE !== (int) wp_get_post_parent_id( $chosen ) ) {
			return; // not a theme page
		}
		if ( $chosen !== (int) apt_category_theme_id( $term->slug ) ) {
			apt_set_category_theme( $term->slug, $chosen );
		}
	}
}
add_action( 'created_category', 'apt_save_category_theme' );
add_action( 'edited_category', 'apt_save_category_theme' );

// "Theme" column in the categories list.
add_filter( 'manage_edit-category_columns', function ( $columns ) {
	$columns['apt_theme'] = __( 'Theme', 'akseki-vi' );
	return $columns;
} );
add_filter( 'manage_category_custom_column', function ( $content, $column, $term_id ) {
	if ( 'apt_theme' !== $column ) {
		return $content;
	}
	static $map = null;
	if ( null === $map ) { // slug => theme page, worked out once for the whole list
		$map = array();
		foreach ( apt_theme_pages() as $page ) {
			foreach ( apt_theme_topics( $page->ID ) as $slug ) {
				$map[ $slug ] = $page;
			}
		}
	}
	$term = get_term( $term_id, 'category' );
	if ( $term && isset( $map[ $term->slug ] ) ) {
		$page = $map[ $term->slug ];
		return '<a href="' . esc_url( get_edit_post_link( $page->ID ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a>';
	}
	return '<span aria-hidden="true">&nbsp;</span>';
}, 10, 3 );
