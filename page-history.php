<?php
/**
 * History (page slug "history"): the page's own paragraphs, unchanged, shown
 * as a timeline. Each paragraph is marked with the first year it mentions
 * (1997, 1999, 2007 …), so editors keep editing the page as usual and the
 * timeline follows. Paragraphs without a year continue the previous entry.
 * Images in the page content are not shown here (decorative only).
 */
get_header();

/** Split rendered content into paragraphs: [ [ 'year' => '1997', 'html' => '<p>…</p>' ], … ] */
function apt_history_entries( $html ) {
	$entries = array();
	if ( ! preg_match_all( '#<p\b[^>]*>(.*?)</p>#is', $html, $m ) ) {
		return $entries;
	}
	foreach ( $m[1] as $inner ) {
		$text = trim( wp_strip_all_tags( $inner ) );
		if ( '' === $text ) {
			continue;
		}
		$year = preg_match( '/\b(19|20)\d{2}\b/', $text, $y ) ? $y[0] : '';
		if ( '' === $year && $entries ) {
			$entries[ count( $entries ) - 1 ]['html'] .= '<p>' . $inner . '</p>';
			continue;
		}
		$entries[] = array( 'year' => $year, 'html' => '<p>' . $inner . '</p>' );
	}
	return $entries;
}
?>
	<main id="main" class="site-main">
		<div class="container" id="avi-content">
			<?php
			while ( have_posts() ) :
				the_post();
				$entries = apt_history_entries( apply_filters( 'the_content', get_the_content() ) );
				?>
			<header class="apt-page-head">
				<h1><?php the_title(); ?></h1>
			</header>

			<?php if ( $entries ) : ?>
			<ol class="apt-timeline">
				<?php foreach ( $entries as $entry ) : ?>
				<li class="apt-timeline-item">
					<?php if ( $entry['year'] ) : ?>
					<p class="apt-timeline-year"><?php echo esc_html( $entry['year'] ); ?></p>
					<?php endif; ?>
					<div class="apt-timeline-body"><?php echo wp_kses_post( $entry['html'] ); ?></div>
				</li>
				<?php endforeach; ?>
			</ol>
			<?php else : ?>
			<div class="entry-content"><?php the_content(); ?></div>
			<?php endif; ?>

			<nav class="apt-related-links" aria-label="<?php esc_attr_e( 'More about ASEAN Plus Three', 'akseki-vi' ); ?>">
				<a class="apt-pill apt-arrow" href="<?php echo esc_url( home_url( '/about-apt/' ) ); ?>"><?php esc_html_e( 'About ASEAN Plus Three', 'akseki-vi' ); ?></a>
				<a class="apt-pill apt-arrow" href="<?php echo esc_url( home_url( '/about-apt/areas-of-cooperation/' ) ); ?>"><?php esc_html_e( 'Areas of Cooperation', 'akseki-vi' ); ?></a>
			</nav>
			<?php endwhile; ?>
		</div>
	</main>
<?php
get_footer();
