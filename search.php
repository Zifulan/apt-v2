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

		<?php get_template_part( 'template-parts/search-form', null, array( 'id' => 'apt-search-page', 'class' => 'apt-search-form--page' ) ); ?>

		<?php if ( have_posts() ) : ?>
		<div class="apt-results">
			<?php
			while ( have_posts() ) {
				the_post();
				get_template_part( 'template-parts/search-result' );
			}
			?>
		</div>
			<?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
		<?php else : ?>
		<div class="apt-search-empty">
			<h2><?php
				/* translators: %s: search terms */
				printf( esc_html__( 'No results for “%s”', 'akseki-vi' ), esc_html( get_search_query() ) );
			?></h2>
			<p><?php esc_html_e( 'Check the spelling or try fewer or different keywords, such as a meeting name (AMAF+3) or a city. Choosing “All categories” searches everything.', 'akseki-vi' ); ?></p>
		</div>
		<?php endif; ?>
		</div>
	</main><!-- .site-main -->

<?php get_footer(); ?>
