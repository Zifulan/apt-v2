<?php get_header(); ?>


	<main id="main" class="site-main search-listing">
		<div class="container" id="avi-content">
		<header class="apt-page-head">
			<h1><?php esc_html_e( 'Search results', 'akseki-vi' ); ?></h1>
			<?php global $wp_query; ?>
			<p><?php
				printf(
					/* translators: 1: number of results, 2: search terms */
					esc_html( _n( '%1$s result for “%2$s”', '%1$s results for “%2$s”', (int) $wp_query->found_posts, 'akseki-vi' ) ),
					esc_html( number_format_i18n( (int) $wp_query->found_posts ) ),
					esc_html( get_search_query() )
				);
			?></p>
		</header>
		<?php
		if ( have_posts() ) {

			// Load posts loop.
			while ( have_posts() ) {
				the_post();		
				get_template_part( 'template-parts/listings' );
			}
			the_posts_pagination( array( 'mid_size' => 2 ) );

		} else {

			// If no content, include the "No posts found" template.
			get_template_part( 'template-parts/notfound' );

		}
		?>
		</div>	
	</main><!-- .site-main -->


<?php get_footer(); ?>