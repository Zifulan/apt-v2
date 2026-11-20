<?php
/**
 * Areas of Cooperation (page slug "areas-of-cooperation"): the page's own text,
 * then the areas grouped by pillar. The areas are the site's theme pages
 * (children of the Themes page, ID 5733), matched to a pillar by their
 * "tpc_pillar" field, so the list follows the content.
 */
get_header();

/** Theme pages for a pillar slug, [ ID => title ], alphabetical. */
function apt_areas_for_pillar( $pillar_slug ) {
	$out   = array();
	$pages = get_pages( array( 'parent' => 5733, 'post_status' => 'publish', 'sort_column' => 'post_title' ) );
	foreach ( (array) $pages as $page ) {
		$key = get_post_meta( $page->ID, 'tpc_pillar', true );
		if ( $key && 0 === strpos( $pillar_slug, $key ) ) {
			$out[ $page->ID ] = $page->post_title;
		}
	}
	return $out;
}
?>
	<main id="main" class="site-main">
		<div class="container" id="avi-content">
			<?php while ( have_posts() ) : the_post(); ?>
			<header class="apt-page-head">
				<h1><?php the_title(); ?></h1>
			</header>

			<div class="entry-content apt-areas-intro"><?php the_content(); ?></div>

			<section class="apt-areas" aria-labelledby="apt-areas-title">
				<h2 id="apt-areas-title" class="apt-areas-title"><?php esc_html_e( 'Areas by pillar', 'akseki-vi' ); ?></h2>
				<div class="apt-areas-grid">
					<?php foreach ( apt_pillars() as $key => $pillar ) :
						$areas = apt_areas_for_pillar( $pillar['slug'] );
						if ( ! $areas ) {
							continue;
						}
						?>
					<div class="apt-areas-pillar pillar-<?php echo esc_attr( $key ); ?>">
						<h3 class="apt-areas-pillar-title"><?php echo esc_html( $pillar['title'] ); ?></h3>
						<ul class="apt-areas-list">
							<?php foreach ( $areas as $id => $title ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( $title ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php endforeach; ?>
				</div>
			</section>

			<nav class="apt-related-links" aria-label="<?php esc_attr_e( 'More about ASEAN Plus Three', 'akseki-vi' ); ?>">
				<a class="apt-pill apt-arrow" href="<?php echo esc_url( home_url( '/about-apt/' ) ); ?>"><?php esc_html_e( 'About ASEAN Plus Three', 'akseki-vi' ); ?></a>
				<a class="apt-pill apt-arrow" href="<?php echo esc_url( home_url( '/about-apt/history/' ) ); ?>"><?php esc_html_e( 'History', 'akseki-vi' ); ?></a>
			</nav>
			<?php endwhile; ?>
		</div>
	</main>
<?php
get_footer();
