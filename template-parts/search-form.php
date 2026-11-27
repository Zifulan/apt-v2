<?php
/**
 * Site search form: keywords + category, a plain WordPress search (?s=&cat=).
 * Used by the header search dialog and the search results page.
 *
 * $args['id']        Prefix for element IDs (must be unique on the page).
 * $args['autofocus'] Focus the keyword field when shown (the dialog).
 * $args['lazy_cats'] Ship the category list inside a <template>; apt-nav.js
 *                    inserts it when the dialog opens (keeps ~80 options out
 *                    of every page's DOM).
 */
$apt_sf_id        = isset( $args['id'] ) ? $args['id'] : 'apt-search';
$apt_sf_autofocus = ! empty( $args['autofocus'] );
$apt_sf_lazy      = ! empty( $args['lazy_cats'] );
?>
<form class="apt-search-form<?php echo isset( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : ''; ?>" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<div class="apt-search-field">
		<label for="<?php echo esc_attr( $apt_sf_id ); ?>-q"><?php esc_html_e( 'Keywords', 'akseki-vi' ); ?></label>
		<input type="search" id="<?php echo esc_attr( $apt_sf_id ); ?>-q" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" enterkeyhint="search" autocomplete="off"<?php echo $apt_sf_autofocus ? ' autofocus' : ''; ?>>
	</div>
	<div class="apt-search-field">
		<label for="<?php echo esc_attr( $apt_sf_id ); ?>-cat"><?php esc_html_e( 'Category', 'akseki-vi' ); ?></label>
		<?php if ( $apt_sf_lazy ) : ?><template class="apt-cat-tpl"><?php endif; ?>
		<?php
		wp_dropdown_categories( array(
			'show_option_all' => __( 'All categories', 'akseki-vi' ),
			'hierarchical'    => 1,
			'orderby'         => 'name',
			'name'            => 'cat',
			'id'              => $apt_sf_id . '-cat',
			'selected'        => (int) get_query_var( 'cat' ),
		) );
		?>
		<?php if ( $apt_sf_lazy ) : ?></template><?php endif; ?>
	</div>
	<?php if ( is_search() && function_exists( 'apt_search_sort' ) && 'relevance' !== apt_search_sort() ) : ?>
		<input type="hidden" name="sort" value="<?php echo esc_attr( apt_search_sort() ); ?>">
	<?php endif; ?>
	<button type="submit" class="apt-search-submit"><?php esc_html_e( 'Search', 'akseki-vi' ); ?></button>
</form>
