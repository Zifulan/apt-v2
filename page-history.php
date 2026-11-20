<?php
/**
 * History (page slug "history"): the page's own content, in the new page
 * design, with links to the related About pages.
 */
get_header();
?>
	<main id="main" class="site-main">
		<div class="container" id="avi-content">
			<?php while ( have_posts() ) : the_post(); ?>
			<header class="apt-page-head">
				<h1><?php the_title(); ?></h1>
			</header>

			<div class="entry-content apt-history"><?php the_content(); ?></div>

			<nav class="apt-related-links" aria-label="<?php esc_attr_e( 'More about ASEAN Plus Three', 'akseki-vi' ); ?>">
				<a class="apt-pill apt-arrow" href="<?php echo esc_url( home_url( '/about-apt/' ) ); ?>"><?php esc_html_e( 'About ASEAN Plus Three', 'akseki-vi' ); ?></a>
				<a class="apt-pill apt-arrow" href="<?php echo esc_url( home_url( '/about-apt/areas-of-cooperation/' ) ); ?>"><?php esc_html_e( 'Areas of Cooperation', 'akseki-vi' ); ?></a>
			</nav>
			<?php endwhile; ?>
		</div>
	</main>
<?php
get_footer();
