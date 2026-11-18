<?php get_header(); ?>
	<main id="main" class="site-main">
		<div class="container" id="avi-content">
			<header class="apt-page-head">
				<h1><?php esc_html_e( 'Page not found', 'akseki-vi' ); ?></h1>
				<p><?php esc_html_e( 'The page you are looking for may have moved, or the address may be mistyped.', 'akseki-vi' ); ?></p>
			</header>
			<div class="apt-notfound">
				<p><?php esc_html_e( 'Try one of these instead:', 'akseki-vi' ); ?></p>
				<ul class="apt-notfound-links">
					<li><a class="apt-arrow" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'akseki-vi' ); ?></a></li>
					<li><a class="apt-arrow" href="<?php echo esc_url( get_category_link( 7 ) ); ?>"><?php esc_html_e( 'Statements', 'akseki-vi' ); ?></a></li>
					<li><a class="apt-arrow" href="<?php echo esc_url( home_url( '/agenda/' ) ); ?>"><?php esc_html_e( 'Agenda', 'akseki-vi' ); ?></a></li>
					<li><a class="apt-arrow" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'akseki-vi' ); ?></a></li>
				</ul>
				<button type="button" class="apt-pill apt-pill--dark" data-toggle="modal" data-target="#searchModal"><?php esc_html_e( 'Search the site', 'akseki-vi' ); ?></button>
			</div>
		</div>
	</main><!-- .site-main -->
<?php get_footer(); ?>
