<?php
/**
 * Statements and Declarations (category 7): the latest summits with their
 * statements, the latest ministerial-level statements, and the other levels.
 * Each section runs its own query, so it doesn't depend on the archive's
 * default page size.
 */
get_header();
$apt_cat = get_queried_object();

// Latest summits: summit statements grouped by summit. The "marker" post names
// the summit; statements from the same month belong to it.
$apt_summits  = array();
$apt_summit_q = new WP_Query( array(
	'cat'                 => 41,
	'posts_per_page'      => 40,
	'post_status'         => 'publish',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
) );
foreach ( $apt_summit_q->posts as $p ) {
	$key = get_the_date( 'Ym', $p );
	if ( ! isset( $apt_summits[ $key ] ) ) {
		$apt_summits[ $key ] = array( 'marker' => 0, 'posts' => array() );
	}
	if ( has_tag( 'marker', $p ) && ! $apt_summits[ $key ]['marker'] ) {
		$apt_summits[ $key ]['marker'] = $p->ID;
	} else {
		$apt_summits[ $key ]['posts'][] = $p->ID;
	}
}
$apt_summits = array_slice( array_filter( $apt_summits, function ( $s ) { return $s['marker']; } ), 0, 5, true );

// Latest ministerial-level statements.
$apt_min_q = new WP_Query( array(
	'cat'                 => 43,
	'posts_per_page'      => 5,
	'post_status'         => 'publish',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
) );

// Other levels: children of Statements and Declarations except Summit and Ministerial.
$apt_levels = array_filter( get_categories( array( 'parent' => 7 ) ), function ( $c ) {
	return ! in_array( (int) $c->term_id, array( 41, 43 ), true );
} );
?>
<main id="main" class="site-main">
	<div class="container" id="avi-content">
		<header class="apt-page-head">
			<h1><?php echo esc_html( $apt_cat->name ); ?></h1>
			<?php if ( trim( (string) $apt_cat->description ) ) : ?>
			<p><?php echo esc_html( wp_strip_all_tags( $apt_cat->description ) ); ?></p>
			<?php endif; ?>
		</header>

		<div class="apt-statements-home">
			<section class="apt-statements-block" aria-labelledby="apt-latest-summits">
				<div class="apt-block-head">
					<h2 id="apt-latest-summits"><?php esc_html_e( 'Latest summits', 'akseki-vi' ); ?></h2>
					<a class="apt-pill apt-arrow" href="<?php echo esc_url( get_category_link( 41 ) ); ?>"><?php esc_html_e( 'All Summit Level Statements', 'akseki-vi' ); ?></a>
				</div>
				<?php if ( $apt_summits ) : ?>
				<ol class="apt-summit-list">
					<?php foreach ( $apt_summits as $s ) :
						$m = $s['marker'];
						?>
					<li class="apt-summit">
						<div class="apt-summit-head">
							<h3 class="apt-summit-name"><a href="<?php echo esc_url( get_permalink( $m ) ); ?>"><?php echo akseki_get_shortname( get_the_title( $m ), $m, 6 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></h3>
							<p class="akseki-meta"><?php echo akseki_get_the_meta( $m ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
						</div>
						<?php if ( $s['posts'] ) : ?>
						<ul class="apt-summit-docs">
							<?php foreach ( $s['posts'] as $d ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $d ) ); ?>"><?php echo get_the_title( $d ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></li>
							<?php endforeach; ?>
						</ul>
						<?php endif; ?>
					</li>
					<?php endforeach; ?>
				</ol>
				<?php else : ?>
				<p class="apt-empty"><?php esc_html_e( 'No summit statements yet.', 'akseki-vi' ); ?></p>
				<?php endif; ?>
			</section>

			<section class="apt-statements-block" aria-labelledby="apt-latest-ministerial">
				<div class="apt-block-head">
					<h2 id="apt-latest-ministerial"><?php esc_html_e( 'Latest ministerial-level statements', 'akseki-vi' ); ?></h2>
					<a class="apt-pill apt-arrow" href="<?php echo esc_url( get_category_link( 43 ) ); ?>"><?php esc_html_e( 'All Ministerial-level Statements', 'akseki-vi' ); ?></a>
				</div>
				<?php if ( $apt_min_q->have_posts() ) : ?>
				<ul class="list-group ministerial-listing">
					<?php foreach ( $apt_min_q->posts as $p ) {
						echo apt_statement_row_html( $p->ID, 'h3' ); // phpcs:ignore WordPress.Security.EscapeOutput
					} ?>
				</ul>
				<?php endif; ?>
			</section>
		</div>

		<?php if ( $apt_levels ) : ?>
		<section class="apt-levels" aria-labelledby="apt-other-levels">
			<h2 id="apt-other-levels" class="apt-levels-title"><?php esc_html_e( 'Other levels', 'akseki-vi' ); ?></h2>
			<ul class="apt-level-grid">
				<?php foreach ( $apt_levels as $lvl ) : ?>
				<li>
					<a class="apt-level-card" href="<?php echo esc_url( get_category_link( $lvl ) ); ?>">
						<span class="apt-level-name apt-arrow"><?php echo esc_html( $lvl->name ); ?></span>
						<?php if ( trim( (string) $lvl->description ) ) : ?>
						<span class="apt-level-desc"><?php echo esc_html( wp_strip_all_tags( $lvl->description ) ); ?></span>
						<?php endif; ?>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php endif; ?>
	</div>
</main>
<?php
wp_reset_postdata();
get_footer();
