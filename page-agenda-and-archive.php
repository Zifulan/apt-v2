<?php
/**
 * Agenda and Archive (page slug "agenda-and-archive"): upcoming meetings,
 * then statements by year.
 */
get_header();
?>
<main id="main" class="site-main">
	<div class="container" id="avi-content">
		<header class="apt-page-head">
			<h1><?php esc_html_e( 'Agenda', 'akseki-vi' ); ?></h1>
		</header>
	</div>
	<?php get_template_part( 'template-parts/sidebar-agenda_bento', null, array( 'in_page' => true ) ); ?>
	<div class="container">
		<?php get_template_part( 'template-parts/sidebar-archives' ); ?>
	</div>
</main>
<?php
get_footer();
