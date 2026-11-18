<?php
/**
 * Fallback template.
 *
 * WordPress only reaches this file for post lists that no more specific
 * template handles (mainly the posts page). Single posts use single.php and
 * pages use page.php, so render each post as a listing, like search.php.
 */
get_header();
?>

	<main id="main" class="site-main">
		<?php if ( is_home() && ! is_front_page() ) : ?>
		<div class="topic-head">
			<div class="container">
				<h1><?php single_post_title(); ?></h1>
			</div>
		</div>
		<?php endif; ?>

		<div class="container pt-4" id="avi-content">
		<?php
		if ( have_posts() ) {

			while ( have_posts() ) {
				the_post();
				get_template_part( 'template-parts/listings' );
			}

			the_posts_pagination( array(
				'mid_size'  => 2,
				'prev_text' => __( 'Previous', 'akseki-vi' ),
				'next_text' => __( 'Next', 'akseki-vi' ),
			) );

		} else {

			get_template_part( 'template-parts/notfound' );

		}
		?>
		</div>
	</main><!-- .site-main -->

<?php get_footer(); ?>
