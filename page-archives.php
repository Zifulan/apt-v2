<?php
/**
 * Yearly Archives (page slug "archives"): statements by year
 * (template-parts/sidebar-archives.php).
 */
get_header();
?>
<main id="main" class="site-main">
	<div class="container" id="avi-content">
		<header class="apt-page-head">
			<h1><?php esc_html_e( 'Yearly Archives', 'akseki-vi' ); ?></h1>
		</header>
		<?php get_template_part( 'template-parts/sidebar-archives' ); ?>
	</div>
</main>
<?php
get_footer();
