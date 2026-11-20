<?php
/**
 * Work Plans and Plans of Action (category 9): the introduction, then each
 * work plan as a document row (apt_document_row_html()).
 */
get_header();
$apt_cat = get_queried_object();
?>
<main id="main" class="site-main">
	<div class="container" id="avi-content">
		<header class="apt-page-head">
			<h1><?php echo esc_html( $apt_cat->name ); ?></h1>
		</header>
		<p class="apt-catdesc catdesc">The ASEAN Plus Three (APT) Cooperation Work Plan serves as a principal guide to enhance APT cooperation over the next five years towards achieving the long-term goal of establishing an East Asia community with ASEAN as the driving force. Consistent with existing regional mechanisms, APT recognises ASEAN Centrality as the driving force in the evolving regional architecture.</p>
		<?php if ( have_posts() ) : ?>
		<div class="apt-doc-list">
			<?php
			while ( have_posts() ) {
				the_post();
				echo apt_document_row_html( get_the_ID(), false ); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in the function.
			}
			?>
		</div>
		<?php else : ?>
		<p class="apt-empty"><?php esc_html_e( 'No work plans yet.', 'akseki-vi' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
