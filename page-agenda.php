<?php
/**
 * Agenda page: upcoming summits and meetings, using the same agenda block as
 * the home page (template-parts/sidebar-agenda.php showed nothing).
 */
get_header();
?>
<main id="main" class="site-main">
	<div class="topic-head" id="page-agenda">
		<div class="container">
			<h1><?php the_title(); ?></h1>
		</div>
	</div>
	<?php get_template_part( 'template-parts/sidebar-agenda_bento', null, array( 'in_page' => true ) ); ?>
	<div class="container apt-agenda-archive-link">
		<a class="apt-pill apt-pill--dark apt-arrow" href="<?php echo esc_url( home_url( '/archives/' ) ); ?>"><?php esc_html_e( 'Past meetings by year', 'akseki-vi' ); ?></a>
	</div>
</main>
<?php
get_footer();
